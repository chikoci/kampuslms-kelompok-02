<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $courseNames = [
            'Pemrograman Web',
            'Basis Data Lanjut',
            'Rekayasa Perangkat Lunak',
            'Jaringan Komputer',
            'Algoritma dan Struktur Data',
            'Sistem Operasi',
            'Kecerdasan Buatan',
            'Keamanan Informasi',
            'Interaksi Manusia dan Komputer',
            'Arsitektur Enterprise',
        ];

        return [
            'code' => 'SI' . fake()->unique()->numerify('###'),
            'name' => fake()->randomElement($courseNames),
            'description' => 'Mata kuliah yang membahas topik fundamental dan praktikum terapan untuk mahasiswa.',
            'sks' => fake()->randomElement([2, 3, 4]),
            'lecturer_id' => User::factory()->lecturer(),
            'status' => 'active',
        ];
    }
}
