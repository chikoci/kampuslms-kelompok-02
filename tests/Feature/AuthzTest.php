<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthzTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $dosen1;
    protected User $dosen2;
    protected User $mhs1;
    protected User $mhs2;
    protected Course $course1;
    protected Course $course2;
    protected Assignment $assignment1;
    protected Material $material1;
    protected Submission $submission1;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Pengguna Pengujian
        $this->admin = User::factory()->create([
            'name' => 'Administrator Test',
            'email' => 'authz-admin@kampuslms.test',
            'role' => 'admin',
        ]);

        $this->dosen1 = User::factory()->create([
            'name' => 'Dosen Satu Test',
            'email' => 'authz-dosen1@kampuslms.test',
            'role' => 'dosen',
        ]);

        $this->dosen2 = User::factory()->create([
            'name' => 'Dosen Dua Test',
            'email' => 'authz-dosen2@kampuslms.test',
            'role' => 'dosen',
        ]);

        $this->mhs1 = User::factory()->create([
            'name' => 'Mahasiswa Satu Test',
            'email' => 'authz-mhs1@kampuslms.test',
            'role' => 'mahasiswa',
        ]);

        $this->mhs2 = User::factory()->create([
            'name' => 'Mahasiswa Dua Test',
            'email' => 'authz-mhs2@kampuslms.test',
            'role' => 'mahasiswa',
        ]);

        // 2. Setup Mata Kuliah Pengujian
        $this->course1 = Course::factory()->create([
            'code' => 'TEST101',
            'name' => 'Pemrograman Web Authz',
            'lecturer_id' => $this->dosen1->id,
            'status' => 'active',
        ]);

        $this->course2 = Course::factory()->create([
            'code' => 'TEST102',
            'name' => 'Basis Data Lanjut Authz',
            'lecturer_id' => $this->dosen2->id,
            'status' => 'active',
        ]);

        // Mhs 1 terdaftar di Course 1, Mhs 2 di Course 2
        $this->course1->students()->attach($this->mhs1->id, ['enrolled_at' => now()]);
        $this->course2->students()->attach($this->mhs2->id, ['enrolled_at' => now()]);

        // 3. Setup Materi & Tugas
        $this->material1 = Material::factory()->create([
            'course_id' => $this->course1->id,
            'title' => 'Materi Pertemuan 1 Authz',
            'uploaded_by' => $this->dosen1->id,
        ]);

        $this->assignment1 = Assignment::factory()->create([
            'course_id' => $this->course1->id,
            'title' => 'Tugas 1 - PHP Dasar Authz',
            'created_by' => $this->dosen1->id,
            'due_at' => now()->addDays(7),
            'max_score' => 100,
            'status' => 'published',
        ]);

        // 4. Setup Submission milik Mhs 1
        $this->submission1 = Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs1->id,
            'submitted_at' => now(),
            'note' => 'Jawaban Tugas 1 Authz',
        ]);
    }

    // ==========================================
    // 1. Pengujian Autentikasi Web & Sesi
    // ==========================================

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $response = $this->actingAs($this->mhs1)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    // ==========================================
    // 2. Pengujian Dashboard Per Peran
    // ==========================================

    public function test_admin_can_access_dashboard_with_admin_view(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
    }

    public function test_dosen_can_access_dashboard_with_dosen_view(): void
    {
        $response = $this->actingAs($this->dosen1)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Dosen');
    }

    public function test_mahasiswa_can_access_dashboard_with_mahasiswa_view(): void
    {
        $response = $this->actingAs($this->mhs1)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Mahasiswa');
    }

    // ==========================================
    // 3. Pengujian Penyaringan Query Tingkat Index
    // ==========================================

    public function test_course_index_is_scoped_by_role(): void
    {
        // Admin melihat kedua course
        $responseAdmin = $this->actingAs($this->admin)->get('/courses');
        $responseAdmin->assertSee('Pemrograman Web Authz');
        $responseAdmin->assertSee('Basis Data Lanjut Authz');

        // Dosen 1 hanya melihat course yang diampunya
        $responseDosen = $this->actingAs($this->dosen1)->get('/courses');
        $responseDosen->assertSee('Pemrograman Web Authz');
        $responseDosen->assertDontSee('Basis Data Lanjut Authz');

        // Mhs 1 hanya melihat course yang diikutinya
        $responseMhs = $this->actingAs($this->mhs1)->get('/courses');
        $responseMhs->assertSee('Pemrograman Web Authz');
        $responseMhs->assertDontSee('Basis Data Lanjut Authz');
    }

    // ==========================================
    // 4. Pengujian Otorisasi Mata Kuliah (CoursePolicy)
    // ==========================================

    public function test_admin_can_create_course(): void
    {
        $response = $this->actingAs($this->admin)->get('/courses/create');
        $response->assertStatus(200);
    }

    public function test_dosen_cannot_create_course(): void
    {
        $response = $this->actingAs($this->dosen1)->get('/courses/create');
        $response->assertStatus(403);
    }

    public function test_mahasiswa_cannot_create_course(): void
    {
        $response = $this->actingAs($this->mhs1)->get('/courses/create');
        $response->assertStatus(403);
    }

    public function test_dosen_can_edit_own_course(): void
    {
        $response = $this->actingAs($this->dosen1)->get("/courses/{$this->course1->id}/edit");
        $response->assertStatus(200);
    }

    public function test_dosen_cannot_edit_other_dosen_course(): void
    {
        $response = $this->actingAs($this->dosen1)->get("/courses/{$this->course2->id}/edit");
        $response->assertStatus(403);
    }

    public function test_mahasiswa_cannot_view_unregistered_course(): void
    {
        $response = $this->actingAs($this->mhs1)->get("/courses/{$this->course2->id}");
        $response->assertStatus(403);
    }

    // ==========================================
    // 5. Pengujian Otorisasi Materi & Tugas (CRUD)
    // ==========================================

    public function test_dosen_can_create_material_in_own_course(): void
    {
        $response = $this->actingAs($this->dosen1)->get("/courses/{$this->course1->id}/materials/create");
        $response->assertStatus(200);
    }

    public function test_dosen_cannot_create_material_in_other_course(): void
    {
        $response = $this->actingAs($this->dosen1)->get("/courses/{$this->course2->id}/materials/create");
        $response->assertStatus(403);
    }

    public function test_mahasiswa_cannot_create_material(): void
    {
        $response = $this->actingAs($this->mhs1)->get("/courses/{$this->course1->id}/materials/create");
        $response->assertStatus(403);
    }

    public function test_dosen_can_create_assignment_in_own_course(): void
    {
        $response = $this->actingAs($this->dosen1)->get("/courses/{$this->course1->id}/assignments/create");
        $response->assertStatus(200);
    }

    public function test_dosen_cannot_create_assignment_in_other_course(): void
    {
        $response = $this->actingAs($this->dosen1)->get("/courses/{$this->course2->id}/assignments/create");
        $response->assertStatus(403);
    }

    public function test_mahasiswa_cannot_create_assignment(): void
    {
        $response = $this->actingAs($this->mhs1)->get("/courses/{$this->course1->id}/assignments/create");
        $response->assertStatus(403);
    }

    // ==========================================
    // 6. Pengujian IDOR Submission & Penilaian (Grade)
    // ==========================================

    public function test_student_can_view_own_submission(): void
    {
        $response = $this->actingAs($this->mhs1)->get("/submissions/{$this->submission1->id}");
        $response->assertStatus(200);
    }

    public function test_student_cannot_view_other_student_submission_idor_protection(): void
    {
        $response = $this->actingAs($this->mhs2)->get("/submissions/{$this->submission1->id}");
        $response->assertStatus(403);
    }

    public function test_course_lecturer_can_view_submission(): void
    {
        $response = $this->actingAs($this->dosen1)->get("/submissions/{$this->submission1->id}");
        $response->assertStatus(200);
    }

    public function test_other_lecturer_cannot_view_submission(): void
    {
        $response = $this->actingAs($this->dosen2)->get("/submissions/{$this->submission1->id}");
        $response->assertStatus(403);
    }

    public function test_course_lecturer_can_grade_submission(): void
    {
        $response = $this->actingAs($this->dosen1)->post("/submissions/{$this->submission1->id}/grades", [
            'score' => 95,
            'feedback' => 'Pengerjaan sangat rapi dan tepat waktu.',
        ]);

        $response->assertRedirect("/submissions/{$this->submission1->id}");
        $this->assertDatabaseHas('grades', [
            'submission_id' => $this->submission1->id,
            'score' => 95,
            'graded_by' => $this->dosen1->id,
        ]);
    }

    public function test_other_lecturer_cannot_grade_submission(): void
    {
        $response = $this->actingAs($this->dosen2)->post("/submissions/{$this->submission1->id}/grades", [
            'score' => 50,
            'feedback' => 'Tidak sah',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_grade_submission_according_to_spec_matrix(): void
    {
        $response = $this->actingAs($this->admin)->post("/submissions/{$this->submission1->id}/grades", [
            'score' => 100,
        ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // 7. Pengujian Manajemen Pengguna & Eskalasi Peran
    // ==========================================

    public function test_non_admin_cannot_access_user_index(): void
    {
        $this->actingAs($this->mhs1)->get('/users')->assertStatus(403);
        $this->actingAs($this->dosen1)->get('/users')->assertStatus(403);
    }

    public function test_admin_can_access_user_index(): void
    {
        $this->actingAs($this->admin)->get('/users')->assertStatus(200);
    }

    public function test_non_admin_cannot_escalate_role_via_update(): void
    {
        $this->actingAs($this->mhs1)->put("/users/{$this->mhs1->id}", [
            'name' => 'Mahasiswa Diedit',
            'email' => $this->mhs1->email,
            'role' => 'admin', // Percobaan eskalasi hak akses
        ]);

        // Peran di database tetap mahasiswa
        $this->assertEquals('mahasiswa', $this->mhs1->fresh()->role);
        $this->assertEquals('Mahasiswa Diedit', $this->mhs1->fresh()->name);
    }

    public function test_guest_can_access_forgot_password_page(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_register_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/register');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_register_page(): void
    {
        $this->actingAs($this->dosen1)->get('/register')->assertStatus(403);
        $this->actingAs($this->mhs1)->get('/register')->assertStatus(403);
    }
}
