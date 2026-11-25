<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * Uji coba login sukses mengembalikan token dan format UserResource.
     */
    public function test_login_returns_token_and_user_resource(): void
    {
        $dosen = User::where('role', 'dosen')->first();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $dosen->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role', 'nim_nip', 'created_at'],
            ]);
    }

    /**
     * Uji coba login salah mengembalikan pesan seragam (cegah user enumeration).
     */
    public function test_login_invalid_credentials_returns_uniform_error(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'tidakada@kampuslms.test',
            'password' => 'salah',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Uji coba endpoint terlindungi tanpa token mengembalikan 401.
     */
    public function test_protected_endpoint_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    /**
     * Uji coba endpoint /me dengan token mengembalikan profil pengguna.
     */
    public function test_me_endpoint_returns_authenticated_user(): void
    {
        $dosen = User::where('role', 'dosen')->first();

        $response = $this->actingAs($dosen, 'sanctum')->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $dosen->id,
                    'email' => $dosen->email,
                    'role' => 'dosen',
                ],
            ]);
    }

    /**
     * Uji coba daftar mata kuliah mengembalikan pagination dengan data dan meta.
     */
    public function test_courses_index_returns_paginated_collection(): void
    {
        $dosen = User::where('role', 'dosen')->first();

        $response = $this->actingAs($dosen, 'sanctum')->getJson('/api/v1/courses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    /**
     * Uji coba mahasiswa dilarang membuat tugas (harus 403 Forbidden).
     */
    public function test_student_cannot_create_assignment_returns_403(): void
    {
        $mahasiswa = User::where('role', 'mahasiswa')->first();
        $course = Course::first();

        $response = $this->actingAs($mahasiswa, 'sanctum')->postJson('/api/v1/assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas Ilegal',
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'max_score' => 100,
            'status' => 'published',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    /**
     * Uji coba dosen dapat membuat tugas (status 201 Created).
     */
    public function test_dosen_can_create_assignment_returns_201(): void
    {
        $dosen = User::where('role', 'dosen')->first();
        $course = Course::where('lecturer_id', $dosen->id)->first();

        $response = $this->actingAs($dosen, 'sanctum')->postJson('/api/v1/assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas Pemrograman Web API',
            'instructions' => 'Kumpulkan link repository dan dokumentasi.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'max_score' => 100,
            'status' => 'published',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'max_score', 'status', 'created_at'],
            ]);
    }

    /**
     * Uji coba penilaian tugas (upsert grade status 201 baru, 200 update).
     */
    public function test_grading_upsert_returns_201_then_200(): void
    {
        $dosen = User::where('role', 'dosen')->first();
        $assignment = Assignment::whereHas('course', fn ($q) => $q->where('lecturer_id', $dosen->id))->first();
        $submission = Submission::where('assignment_id', $assignment->id)->first();

        $this->assertNotNull($submission, 'Seeder wajib menyediakan minimal 1 submission untuk diuji.');

        // Hapus nilai lama jika ada agar bisa menguji status 201
        $submission->grade()?->delete();

        // Panggilan pertama -> 201 Created
        $response1 = $this->actingAs($dosen, 'sanctum')->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 95,
            'feedback' => 'Sangat rapi dan mematuhi spesifikasi.',
        ]);
        $response1->assertStatus(201);
        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 95,
        ]);

        // Panggilan kedua (update penilaian) -> 200 OK
        $response2 = $this->actingAs($dosen, 'sanctum')->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 98,
            'feedback' => 'Nilai diperbarui setelah evaluasi tambahan.',
        ]);
        $response2->assertStatus(200);
        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 98,
            'feedback' => 'Nilai diperbarui setelah evaluasi tambahan.',
        ]);
    }

    /**
     * Memastikan mahasiswa hanya menerima tugas berstatus published dari API assignments.
     */
    public function test_student_api_assignments_only_returns_published(): void
    {
        $mahasiswa = User::where('role', 'mahasiswa')->first();
        $course = $mahasiswa->courses()->first();

        // Buat tugas draft baru pada mata kuliah yang diikuti mahasiswa
        $draftAssignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $course->lecturer_id,
            'title' => 'Tugas Konsep Draft API Rahasia',
            'instructions' => 'Belum siap dipublikasikan.',
            'due_at' => now()->addDays(5),
            'max_score' => 100,
            'status' => 'draft',
            'allow_late' => false,
        ]);

        $response = $this->actingAs($mahasiswa, 'sanctum')->getJson("/api/v1/courses/{$course->id}/assignments");

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertFalse($titles->contains('Tugas Konsep Draft API Rahasia'));
    }
}
