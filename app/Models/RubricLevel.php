<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'rubric_criterion_id',
        'title',
        'description',
        'score_percentage',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'score_percentage' => 'decimal:2',
            'order' => 'integer',
        ];
    }

    /**
     * Relasi ke kriteria induk.
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }
}

