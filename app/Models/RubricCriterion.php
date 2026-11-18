<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricCriterion extends Model
{
    use HasFactory;

    protected $table = 'rubric_criteria';

    protected $fillable = [
        'rubric_id',
        'title',
        'description',
        'weight',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'order' => 'integer',
        ];
    }

    /**
     * Relasi ke Rubrik induk.
     */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    /**
     * Relasi ke tingkatan / level performa pada kriteria ini.
     */
    public function levels(): HasMany
    {
        return $this->hasMany(RubricLevel::class)->orderBy('order');
    }

    /**
     * Relasi ke catatan penilaian pada submission mahasiswa.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(RubricAssessment::class);
    }
}

