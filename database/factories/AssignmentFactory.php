<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Tugas 1: Desain Skema Database dan Migrasi',
            'Tugas 2: Implementasi Eloquent Relationship',
            'Tugas 3: Pembuatan CRUD dan Validasi Request',
            'Tugas 4: Integrasi REST API dan Sanctum',
            'Tugas Proyek Tengah Semester (UTS)',
        ];

        return [
            'course_id' => Course::factory(),
            'created_by' => User::factory()->lecturer(),
            'title' => fake()->randomElement($titles),
            'instructions' => 'Kerjakan tugas sesuai spesifikasi teknis dan kumpulkan file laporan tepat waktu.',
            'due_at' => now()->addDays(fake()->numberBetween(3, 14)),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ];
    }

    /**
     * State untuk tugas yang sudah lewat tenggat waktu (expired).
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->subDays(fake()->numberBetween(3, 14)),
        ]);
    }

    /**
     * State untuk tugas aktif yang sedang berjalan.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->addDays(fake()->numberBetween(2, 7)),
        ]);
    }
}
