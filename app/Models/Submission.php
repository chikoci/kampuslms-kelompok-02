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
}
