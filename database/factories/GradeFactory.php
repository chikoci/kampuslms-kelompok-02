<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $feedbacks = [
            'Pengerjaan sangat rapi, struktur kode sudah mengikuti best practice.',
            'Hasil pengujian lengkap dan sesuai kriteria tugas.',
            'Bagus, namun perhatikan kembali efisiensi kueri database.',
            'Lengkap, penjelasan di laporan sangat jelas dan mudah dipahami.',
            'Cukup baik, pertahankan kualitas pengerjaannya pada materi berikutnya.',
        ];

        return [
            'submission_id' => Submission::factory(),
            'graded_by' => User::factory()->lecturer(),
            'score' => fake()->randomFloat(2, 70, 100),
            'feedback' => fake()->randomElement($feedbacks),
            'graded_at' => now(),
        ];
    }
}
