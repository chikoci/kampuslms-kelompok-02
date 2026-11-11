<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    /** @use HasFactory<\Database\Factories\SubmissionFactory> */
    use HasFactory;

    /**
     * Kolom yang aman diisi saat mahasiswa mengumpulkan tugas.
     * Catatan: 'user_id' diisi via Auth::id(), 'is_late' dihitung server.
     *
     * @var list<string>
     */
    protected $fillable = [
        'assignment_id',
        'user_id',
        'file_path',
        'original_name',
        'file_size',
        'note',
        'submitted_at',
        'is_late',
    ];

    /**
     * Casting atribut gaya Laravel 12.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /**
     * Boot model untuk mendaftarkan event lifecycle Eloquent.
     * Menghapus berkas fisik di disk privat saat record submission dihapus.
     */
    protected static function booted(): void
    {
        static::deleting(function (Submission $submission) {
            if ($submission->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($submission->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($submission->file_path);
            }
        });
    }

    /**
     * Relasi: Pengumpulan tugas merujuk ke satu Tugas.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Relasi: Pengumpulan tugas dikirim oleh satu Mahasiswa.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi: Pengumpulan tugas menerima tepat satu Nilai (One-to-One).
     */
    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }

    /**
     * Accessor untuk menampilkan ukuran berkas dalam format yang terbaca manusia (KB, MB).
     * Dilengkapi fallback defensif agar aman pada lingkungan tanpa ekstensi intl.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '-';
        }

        if (extension_loaded('intl')) {
            return \Illuminate\Support\Number::fileSize($this->file_size);
        }

        $bytes = (float) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);
        $value = round($bytes / pow(1024, $power), 2);

        return "{$value} {$units[$power]}";
    }
}
