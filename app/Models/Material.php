<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    /** @use HasFactory<\Database\Factories\MaterialFactory> */
    use HasFactory;

    /**
     * Kolom yang aman diisi dari input pengguna.
     * Catatan: 'uploaded_by' dikeluarkan dari fillable dan diisi via Auth::id().
     *
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'type',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
        'external_url',
        'uploaded_by',
    ];

    /**
     * Casting atribut gaya Laravel 12.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * Boot model untuk mendaftarkan event lifecycle Eloquent.
     * Menghapus berkas fisik materi di disk privat saat record materi dihapus.
     */
    protected static function booted(): void
    {
        static::deleting(function (Material $material) {
            if ($material->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($material->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($material->file_path);
            }
        });
    }

    /**
     * Relasi: Materi dimiliki oleh satu mata kuliah.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Relasi: Materi diunggah oleh satu Dosen.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Accessor untuk menampilkan ukuran berkas dalam format yang terbaca manusia (KB, MB).
     */
    public function getFormattedFileSizeAttribute(): string
    {
        return $this->file_size ? \Illuminate\Support\Number::fileSize($this->file_size) : '-';
    }
}
