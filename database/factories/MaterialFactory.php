<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Material>
 */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topics = [
            'Pengantar dan Silabus Perkuliahan',
            'Konsep Dasar dan Arsitektur Sistem',
            'Implementasi Model dan Relasi Database',
            'Validasi Data dan Penanganan Formulir',
            'Manajemen State dan Session Pengguna',
            'Keamanan Aplikasi dan Pencegahan Kerentanan',
            'Optimasi Kueri dan Best Practice Produksi',
        ];

        return [
            'course_id' => Course::factory(),
            'uploaded_by' => User::factory()->lecturer(),
            'title' => 'Pertemuan ' . fake()->numberBetween(1, 14) . ': ' . fake()->randomElement($topics),
            'description' => 'Ringkasan materi perkuliahan untuk dipelajari sebelum sesi praktikum kelas dimulai.',
            'type' => 'file',
            'file_path' => 'materials/materi-minggu-' . fake()->numberBetween(1, 14) . '.pdf',
            'original_name' => 'Materi_Kuliah.pdf',
            'file_size' => fake()->numberBetween(500000, 5000000),
            'mime_type' => 'application/pdf',
        ];
    }
}
