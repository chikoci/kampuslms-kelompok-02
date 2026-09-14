<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Jalankan seeder akun demo wajib
        $this->call(DemoAccountSeeder::class);

        // Ambil akun demo yang baru dibuat
        $demoLecturer = User::where('email', 'dosen@kampuslms.test')->first();
        $demoStudent = User::where('email', 'mahasiswa@kampuslms.test')->first();

        // 2. Buat dosen tambahan (total 3 dosen)
        $otherLecturers = User::factory(2)->lecturer()->create();
        $lecturers = collect([$demoLecturer])->merge($otherLecturers);

        // 3. Buat mahasiswa tambahan (total 30 mahasiswa)
        $otherStudents = User::factory(29)->student()->create();
        $students = collect([$demoStudent])->merge($otherStudents);

        // 4. Buat 5 mata kuliah aktif
        $coursesData = [
            [
                'code' => 'SI201',
                'name' => 'Pemrograman Web',
                'description' => 'Mata kuliah pengembangan aplikasi web modern menggunakan arsitektur MVC dan framework Laravel.',
                'sks' => 3,
                'lecturer_id' => $lecturers[0]->id,
                'status' => 'active',
            ],
            [
                'code' => 'SI202',
                'name' => 'Basis Data Lanjut',
                'description' => 'Mata kuliah perancangan skema relasional, optimasi kueri SQL, indexing, dan integritas data.',
                'sks' => 3,
                'lecturer_id' => $lecturers[1]->id,
                'status' => 'active',
            ],
            [
                'code' => 'SI203',
                'name' => 'Rekayasa Perangkat Lunak',
                'description' => 'Mempelajari metodologi pengembangan perangkat lunak, pemodelan sistem, dan jaminan mutu kode.',
                'sks' => 3,
                'lecturer_id' => $lecturers[2]->id,
                'status' => 'active',
            ],
            [
                'code' => 'SI204',
                'name' => 'Jaringan Komputer',
                'description' => 'Konsep protokol jaringan, subnetting, keamanan komunikasi data, dan arsitektur client-server.',
                'sks' => 3,
                'lecturer_id' => $lecturers[0]->id,
                'status' => 'active',
            ],
            [
                'code' => 'SI205',
                'name' => 'Algoritma dan Struktur Data',
                'description' => 'Analisis kompleksitas algoritma, struktur data linier dan hierarki, serta pemecahan masalah komputasi.',
                'sks' => 4,
                'lecturer_id' => $lecturers[1]->id,
                'status' => 'active',
            ],
        ];

        $courses = collect();
        foreach ($coursesData as $cData) {
            $courses->push(Course::updateOrCreate(['code' => $cData['code']], $cData));
        }

        // 5. Pendaftaran Mahasiswa ke Mata Kuliah (tiap MK >= 15 mahasiswa terdaftar)
        foreach ($courses as $course) {
            // Mahasiswa demo selalu didaftarkan ke seluruh mata kuliah
            $enrolledStudentIds = collect([$demoStudent->id]);

            // Ambil 18 - 22 mahasiswa acak lainnya untuk di-enroll
            $randomOthers = $otherStudents->random(rand(18, 22))->pluck('id');
            $allStudentIdsToEnroll = $enrolledStudentIds->merge($randomOthers)->unique();

            $pivotData = [];
            foreach ($allStudentIdsToEnroll as $sId) {
                $pivotData[$sId] = [
                    'enrolled_at' => now()->subDays(rand(15, 45)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $course->students()->syncWithoutDetaching($pivotData);
        }

        // 6. Buat Materi Perkuliahan untuk tiap Mata Kuliah (2-3 materi per MK)
        $materialTopics = [
            'Pengantar Mata Kuliah dan Kontrak Belajar',
            'Konsep Fundamental dan Lingkungan Pengembangan',
            'Implementasi Arsitektur dan Studi Kasus Terapan',
        ];

        foreach ($courses as $course) {
            foreach ($materialTopics as $idx => $topic) {
                Material::create([
                    'course_id' => $course->id,
                    'uploaded_by' => $course->lecturer_id,
                    'title' => 'Pertemuan ' . ($idx + 1) . ': ' . $topic,
                    'description' => 'Materi pembahasan pertemuan ke-' . ($idx + 1) . ' untuk mata kuliah ' . $course->name . '.',
                    'type' => 'file',
                    'file_path' => 'materials/' . strtolower($course->code) . '-pertemuan-' . ($idx + 1) . '.pdf',
                    'original_name' => strtolower($course->code) . '-materi-' . ($idx + 1) . '.pdf',
                    'file_size' => rand(500000, 2500000),
                    'mime_type' => 'application/pdf',
                ]);
            }
        }

        // 7. Buat Tugas Perkuliahan (3 tugas per MK: lewat deadline, aktif segera, dan aktif jangka panjang)
        $allAssignments = collect();
        foreach ($courses as $course) {
            // Tugas 1: Sudah lewat deadline (past due)
            $a1 = Assignment::create([
                'course_id' => $course->id,
                'created_by' => $course->lecturer_id,
                'title' => 'Tugas 1: ' . $course->name . ' - Fondasi dan Skema',
                'instructions' => 'Kerjakan modul praktikum bagian 1 dan kumpulkan laporan hasil pengujian sistem.',
                'due_at' => now()->subDays(rand(5, 10)),
                'max_score' => 100,
                'allow_late' => true,
                'status' => 'published',
            ]);

            // Tugas 2: Aktif segera (active closing soon)
            $a2 = Assignment::create([
                'course_id' => $course->id,
                'created_by' => $course->lecturer_id,
                'title' => 'Tugas 2: ' . $course->name . ' - Implementasi Fitur',
                'instructions' => 'Lengkapi kode program sesuai spesifikasi dan pastikan semua pengujian lulus.',
                'due_at' => now()->addDays(rand(3, 7)),
                'max_score' => 100,
                'allow_late' => true,
                'status' => 'published',
            ]);

            // Tugas 3: Aktif masa depan (proyek/draft)
            $a3 = Assignment::create([
                'course_id' => $course->id,
                'created_by' => $course->lecturer_id,
                'title' => 'Tugas 3: ' . $course->name . ' - Proyek Terpadu',
                'instructions' => 'Rancang bangun solusi studi kasus lengkap secara mandiri atau berkelompok.',
                'due_at' => now()->addDays(rand(12, 20)),
                'max_score' => 100,
                'allow_late' => false,
                'status' => 'draft',
            ]);

            $allAssignments->push($a1, $a2, $a3);
        }

        // 8. Buat Submissions dengan Integritas Relasi Konsisten (hanya mahasiswa terdaftar)
        // Target: >= 100 submission dengan sebagian terlambat
        $allSubmissions = collect();

        foreach ($allAssignments as $assignment) {
            $course = $assignment->course;
            $enrolledStudents = $course->students;

            $isPastDue = $assignment->due_at->isPast();

            if ($isPastDue) {
                // Untuk tugas yang sudah lewat deadline: 15 - 18 mahasiswa mengumpulkan
                $studentsToSubmit = $enrolledStudents->random(min(rand(15, 18), $enrolledStudents->count()));

                foreach ($studentsToSubmit as $student) {
                    // 25% kemungkinan terlambat
                    $isLate = (rand(1, 100) <= 25);

                    if ($isLate) {
                        $submittedAt = (clone $assignment->due_at)->addHours(rand(2, 72));
                    } else {
                        $submittedAt = (clone $assignment->due_at)->subHours(rand(4, 96));
                    }

                    $sub = Submission::create([
                        'assignment_id' => $assignment->id,
                        'user_id' => $student->id,
                        'file_path' => 'submissions/' . strtolower($course->code) . '-tugas-' . $student->id . '.zip',
                        'original_name' => 'Tugas_' . $course->code . '_' . str_replace(' ', '_', $student->name) . '.zip',
                        'file_size' => rand(150000, 3500000),
                        'note' => $isLate ? 'Mohon maaf terlambat mengumpulkan karena kendala teknis jaringan.' : 'Tugas sudah selesai dikerjakan sesuai spesifikasi.',
                        'submitted_at' => $submittedAt,
                        'is_late' => $isLate,
                    ]);

                    $allSubmissions->push($sub);
                }
            } else {
                // Untuk tugas aktif: 7 - 10 mahasiswa rajin mengumpulkan lebih awal
                $studentsToSubmit = $enrolledStudents->random(min(rand(7, 10), $enrolledStudents->count()));

                foreach ($studentsToSubmit as $student) {
                    $submittedAt = now()->subHours(rand(2, 48));

                    $sub = Submission::create([
                        'assignment_id' => $assignment->id,
                        'user_id' => $student->id,
                        'file_path' => 'submissions/' . strtolower($course->code) . '-tugas-' . $student->id . '.zip',
                        'original_name' => 'Tugas_' . $course->code . '_' . str_replace(' ', '_', $student->name) . '.zip',
                        'file_size' => rand(200000, 4000000),
                        'note' => 'Pengumpulan tugas mandiri, mohon masukannya Pak/Bu.',
                        'submitted_at' => $submittedAt,
                        'is_late' => false,
                    ]);

                    $allSubmissions->push($sub);
                }
            }
        }

        // 9. Buat Penilaian (Grades) untuk ~60% dari total Submissions
        // Penilai (graded_by) adalah dosen pengampu mata kuliah terkait
        $targetGradedCount = (int) round($allSubmissions->count() * 0.60);
        $submissionsToGrade = $allSubmissions->random($targetGradedCount);

        $gradeFeedbacks = [
            'Pengerjaan sangat baik dan rapi. Seluruh kriteria spesifikasi terpenuhi.',
            'Kode program berjalan lancar, struktur arsitektur sudah rapi.',
            'Bagus sekali, pemahaman konsep relasi database sudah sangat matang.',
            'Hasil pengujian lengkap. Perhatikan penamaan variabel agar lebih konsisten.',
            'Solusi tepat guna, dokumentasi laporan sangat mudah dipahami.',
            'Sudah memenuhi standar minimal tugas, tingkatkan lagi kerapian kode.',
        ];

        foreach ($submissionsToGrade as $submission) {
            $lecturerId = $submission->assignment->course->lecturer_id;

            // Jika terlambat, nilai maksimal sedikit disesuaikan
            $minScore = $submission->is_late ? 65 : 78;
            $maxScore = $submission->is_late ? 88 : 98;
            $score = rand($minScore * 10, $maxScore * 10) / 10;

            Grade::create([
                'submission_id' => $submission->id,
                'graded_by' => $lecturerId,
                'score' => $score,
                'feedback' => $gradeFeedbacks[array_rand($gradeFeedbacks)],
                'graded_at' => (clone $submission->submitted_at)->addHours(rand(6, 48)),
            ]);
        }
    }
}
