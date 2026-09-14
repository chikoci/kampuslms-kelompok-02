<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    /** @use HasFactory<\Database\Factories\GradeFactory> */
    use HasFactory;

    /**
     * Kolom yang aman diisi saat dosen memberi nilai.
     * Catatan: 'graded_by' diisi via Auth::id() dosen yang login.
     *
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'score',
        'feedback',
        'graded_at',
    ];

    /**
     * Casting atribut gaya Laravel 12.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    /**
     * Relasi: Nilai diberikan untuk satu pengumpulan tugas (submission).
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Relasi: Nilai dicatat oleh Dosen penilai (graded_by).
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
