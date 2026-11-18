<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'rubric_criterion_id',
        'rubric_level_id',
        'score_awarded',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'score_awarded' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke Submission mahasiswa.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Relasi ke Kriteria yang dinilai.
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }

    /**
     * Relasi ke Level yang dipilih oleh dosen.
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(RubricLevel::class, 'rubric_level_id');
    }
}

