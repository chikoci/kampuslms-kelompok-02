<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Assignment extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentFactory> */
    use HasFactory;

    /**
     * Kolom yang aman diisi dari form pengguna.
     * Catatan: 'created_by' dikeluarkan dari fillable dan diisi via Auth::id().
     *
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'title',
        'instructions',
        'due_at',
        'max_score',
        'allow_late',
        'status',
        'created_by',
    ];

    /**
     * Cache invalidation otomatis saat data tugas berubah (BUILD Poin 6).
     */
    protected static function booted(): void
    {
        static::saved(function (Assignment $assignment) {
            \Illuminate\Support\Facades\Cache::forget("dashboard.dosen.{$assignment->created_by}.metrics");
            \Illuminate\Support\Facades\Cache::forget('dashboard.admin.metrics');
        });

        static::deleted(function (Assignment $assignment) {
            \Illuminate\Support\Facades\Cache::forget("dashboard.dosen.{$assignment->created_by}.metrics");
            \Illuminate\Support\Facades\Cache::forget('dashboard.admin.metrics');
        });
    }

    /**
     * Casting atribut gaya Laravel 12.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'max_score' => 'integer',
            'allow_late' => 'boolean',
        ];
    }

    /**
     * Relasi: Tugas dimiliki oleh satu mata kuliah.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Relasi: Tugas dibuat oleh satu Dosen.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi: Tugas memiliki banyak pengumpulan (submissions) dari mahasiswa.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Relasi: Tugas memiliki banyak nilai melalui tabel perantara submission (HasManyThrough).
     */
    public function grades(): HasManyThrough
    {
        return $this->hasManyThrough(Grade::class, Submission::class);
    }
}
