<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentAndGradingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $dosen1;
    protected User $dosen2;
    protected User $mhs1;
    protected User $mhs2;
    protected Course $course1;
    protected Course $course2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = User::factory()->create(['role' => 'admin']);
        $this->dosen1 = User::factory()->create(['role' => 'dosen']);
        $this->dosen2 = User::factory()->create(['role' => 'dosen']);
        $this->mhs1   = User::factory()->create(['role' => 'mahasiswa']);
        $this->mhs2   = User::factory()->create(['role' => 'mahasiswa']);

        $this->course1 = Course::factory()->create(['lecturer_id' => $this->dosen1->id]);
        $this->course2 = Course::factory()->create(['lecturer_id' => $this->dosen2->id]);
    }

    public function test_dosen_can_create_text_only_material(): void
    {
        $response = $this->actingAs($this->dosen1)->post(route('courses.materials.store', $this->course1), [
            'title'       => 'Materi Pengumuman Teks',
            'type'        => 'text',
            'description' => 'Ini adalah rangkuman materi perkuliahan teks.',
        ]);

        $response->assertRedirect(route('courses.show', $this->course1));
        $this->assertDatabaseHas('materials', [
            'course_id'   => $this->course1->id,
            'title'       => 'Materi Pengumuman Teks',
            'type'        => 'text',
            'file_path'   => null,
        ]);
    }

    public function test_admin_and_course_lecturer_can_enroll_student_to_course(): void
    {
        // 1. Dosen pengampu mendaftarkan mhs1 ke course1 miliknya (berhasil)
        $response = $this->actingAs($this->dosen1)->post(route('courses.enroll', $this->course1), [
            'student_id' => $this->mhs1->id,
        ]);
        $response->assertSessionHas('success');
        $this->assertTrue($this->course1->students()->where('user_id', $this->mhs1->id)->exists());

        // 2. Dosen 2 TIDAK BISA mendaftarkan mahasiswa ke course1 milik Dosen 1 (403 Forbidden)
        $response2 = $this->actingAs($this->dosen2)->post(route('courses.enroll', $this->course1), [
            'student_id' => $this->mhs2->id,
        ]);
        $response2->assertStatus(403);

        // 3. Admin bisa mendaftarkan mhs2 ke course1 (berhasil)
        $response3 = $this->actingAs($this->admin)->post(route('courses.enroll', $this->course1), [
            'student_id' => $this->mhs2->id,
        ]);
        $response3->assertSessionHas('success');
        $this->assertTrue($this->course1->students()->where('user_id', $this->mhs2->id)->exists());
    }

    public function test_admin_and_course_lecturer_can_unenroll_student_from_course(): void
    {
        $this->course1->students()->attach($this->mhs1->id, ['enrolled_at' => now()]);
        $this->course1->students()->attach($this->mhs2->id, ['enrolled_at' => now()]);

        // Dosen pengampu mengeluarkan mhs1
        $response = $this->actingAs($this->dosen1)->delete(route('courses.unenroll', [$this->course1, $this->mhs1]));
        $response->assertSessionHas('success');
        $this->assertFalse($this->course1->students()->where('user_id', $this->mhs1->id)->exists());

        // Admin mengeluarkan mhs2
        $responseAdmin = $this->actingAs($this->admin)->delete(route('courses.unenroll', [$this->course1, $this->mhs2]));
        $responseAdmin->assertSessionHas('success');
        $this->assertFalse($this->course1->students()->where('user_id', $this->mhs2->id)->exists());
    }

    public function test_dosen_can_delete_grade_and_navigate_looping_submissions(): void
    {
        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course1->id,
            'created_by' => $this->dosen1->id,
            'max_score'  => 100,
        ]);

        $sub1 = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $this->mhs1->id,
        ]);

        $sub2 = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $this->mhs2->id,
        ]);

        // Dosen memberi nilai sub1
        $this->actingAs($this->dosen1)->post(route('submissions.grades.store', $sub1), [
            'score'    => 88,
            'feedback' => 'Bagus sekali',
        ]);
        $this->assertDatabaseHas('grades', [
            'submission_id' => $sub1->id,
            'score'         => 88,
        ]);

        // Dosen memeriksa sub1 dan memastikan navigasi ke sub2 tersedia
        $resShow = $this->actingAs($this->dosen1)->get(route('submissions.show', $sub1));
        $resShow->assertStatus(200);
        $resShow->assertSee(route('submissions.show', $sub2));

        // Dosen menghapus nilai sub1 (reset nilai)
        $resDelete = $this->actingAs($this->dosen1)->delete(route('submissions.grades.destroy', $sub1));
        $resDelete->assertRedirect(route('submissions.show', $sub1));
        $this->assertDatabaseMissing('grades', [
            'submission_id' => $sub1->id,
        ]);
    }

    /**
     * Uji 4: Otorisasi destroyGrade tetap menolak (403) pengguna tidak berhak meskipun nilai masih null.
     */
    public function test_unauthorized_user_cannot_destroy_grade_even_when_grade_is_null(): void
    {
        $this->course1->students()->attach($this->mhs1->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course1->id,
            'created_by' => $this->dosen1->id,
        ]);

        // Submission belum dinilai (grade === null)
        $sub = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $this->mhs1->id,
        ]);

        // Mahasiswa harus ditolak 403
        $this->actingAs($this->mhs1)
            ->delete(route('submissions.grades.destroy', $sub))
            ->assertStatus(403);

        // Dosen pengampu mata kuliah lain juga harus ditolak 403
        $this->actingAs($this->dosen2)
            ->delete(route('submissions.grades.destroy', $sub))
            ->assertStatus(403);
    }

    /**
     * Uji 5: Halaman daftar pengumpulan dosen memakai paginasi dan agregasi dari Controller.
     */
    public function test_lecturer_submission_index_uses_pagination_and_controller_aggregates(): void
    {
        $this->course1->students()->attach([
            $this->mhs1->id => ['enrolled_at' => now()],
            $this->mhs2->id => ['enrolled_at' => now()],
        ]);

        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course1->id,
            'created_by' => $this->dosen1->id,
        ]);

        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $this->mhs1->id,
        ]);

        $response = $this->actingAs($this->dosen1)
            ->get(route('assignments.submissions.index', ['assignment' => $assignment, 'per_page' => 25]));

        $response->assertStatus(200)
            ->assertSee($this->mhs1->name)
            ->assertSee($this->mhs2->name)
            ->assertViewHas('totalEnrolled', 2)
            ->assertViewHas('submittedCount', 1)
            ->assertViewHas('pendingGrading', 1)
            ->assertViewHas('perPage', 25);
    }
}
