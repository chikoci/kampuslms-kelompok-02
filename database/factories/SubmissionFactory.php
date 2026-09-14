<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submittedAt = now()->subDays(fake()->numberBetween(1, 5));

        return [
            'assignment_id' => Assignment::factory(),
            'user_id' => User::factory()->student(),
            'file_path' => 'submissions/' . fake()->uuid() . '.zip',
            'original_name' => 'Tugas_' . fake()->lexify('????') . '.zip',
            'file_size' => fake()->numberBetween(102400, 5242880), // 100 KB - 5 MB
            'note' => fake()->optional(0.7)->randomElement([
                'Mohon izin mengumpulkan tugas praktikum.',
                'Berikut lampiran source code dan laporan pengujian.',
                'Semua instruksi dan checkpoint telah diselesaikan.',
            ]),
            'submitted_at' => $submittedAt,
            'is_late' => false,
        ];
    }

    /**
     * State untuk submission yang terlambat.
     */
    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_late' => true,
        ]);
    }
}
