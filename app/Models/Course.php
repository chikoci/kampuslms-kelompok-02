<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    /**
     * Kolom yang aman diisi dari input form pengguna.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'sks',
        'lecturer_id',
        'status',
    ];

    /**
     * Casting atribut gaya Laravel 12.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sks' => 'integer',
        ];
    }

    /**
     * Relasi: Mata kuliah diampu oleh satu Dosen.
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * Relasi: Mata kuliah diambil oleh banyak Mahasiswa (Many-to-Many).
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    /**
     * Relasi: Mata kuliah memiliki banyak materi pembelajaran.
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    /**
     * Relasi: Mata kuliah memiliki banyak tugas kuliah.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
