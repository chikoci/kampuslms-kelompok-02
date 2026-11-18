<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubric extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'title',
        'description',
    ];

    /**
     * Relasi ke Tugas yang memiliki rubrik ini.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Relasi ke Kriteria penilaian dalam rubrik.
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('order');
    }

    /**
     * Menghitung total bobot kriteria yang sudah terdaftar.
     */
    public function getTotalWeightAttribute(): float
    {
        return (float) $this->criteria->sum('weight');
    }

    /**
     * Memeriksa apakah total bobot kriteria sudah valid (harus 100%).
     */
    public function isValidWeight(): bool
    {
        $sum = (float) $this->criteria->sum('weight');
        return abs($sum - 100.0) < 0.01;
    }
}

