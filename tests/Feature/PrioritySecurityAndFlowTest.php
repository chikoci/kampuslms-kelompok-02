<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\NewAssignmentNotification;
use App\Notifications\SubmissionGradedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Implementasi 10 Feature Test Prioritas Utama (Prompt Pack B - Materi 14)
 * beserta pelengkap cakupan matriks keamanan docs/keamanan.md.
 */
class PrioritySecurityAndFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $dosen1;
    private User $dosen2;
    private User $mhs1;
    private User $mhs2;
    private User $unregisteredMhs;
    private Course $course1;
    private Material $material1;
    private Assignment $assignment1;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->dosen1 = User::factory()->create(['role' => 'dosen']);
        $this->dosen2 = User::factory()->create(['role' => 'dosen']);
        $this->mhs1 = User::factory()->create(['role' => 'mahasiswa']);
        $this->mhs2 = User::factory()->create(['role' => 'mahasiswa']);
        $this->unregisteredMhs = User::factory()->create(['role' => 'mahasiswa']);

        $this->course1 = Course::factory()->create([
            'code' => 'IF1401',
            'name' => 'Pengujian Perangkat Lunak',
            'lecturer_id' => $this->dosen1->id,
            'status' => 'active',
        ]);

        // Hanya mhs1 dan mhs2 yang terdaftar di course1; unregisteredMhs tidak didaftarkan.
        $this->course1->students()->attach([
            $this->mhs1->id => ['enrolled_at' => now()],
            $this->mhs2->id => ['enrolled_at' => now()],
        ]);

        $materialPath = UploadedFile::fake()
            ->create('modul-pertemuan-14.pdf', 256, 'application/pdf')
            ->store('materials/' . $this->course1->id, 'local');

        $this->material1 = Material::factory()->create([
            'course_id' => $this->course1->id,
            'uploaded_by' => $this->dosen1->id,
            'title' => 'Modul Pengujian Otomatis',
            'type' => 'file',
            'file_path' => $materialPath,
            'original_name' => 'modul-pertemuan-14.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->assignment1 = Assignment::factory()->create([
            'course_id' => $this->course1->id,
            'created_by' => $this->dosen1->id,
            'title' => 'Tugas Feature Test Laravel',
            'due_at' => now()->addDays(3),
            'max_score' => 100,
            'allow_late' => false,
            'status' => 'published',
        ]);
    }

    /**
     * [PRIORITAS #1] Mahasiswa terdaftar berhasil mengumpulkan tugas berkas ke disk privat.
     */
    public function test_enrolled_student_can_submit_file_assignment(): void
    {
        $file = UploadedFile::fake()->create('jawaban-mhs1.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->mhs1)->post(
            route('assignments.submissions.store', $this->assignment1),
            [
                'file' => $file,
                'note' => 'Sudah lengkap dengan feature test.',
            ]
        );

        $response->assertRedirect(route('assignments.show', $this->assignment1));

        $submission = Submission::where('assignment_id', $this->assignment1->id)
            ->where('user_id', $this->mhs1->id)
            ->first();

        $this->assertNotNull($submission);
        $this->assertFalse($submission->is_late);
        $this->assertEquals('jawaban-mhs1.pdf', $submission->original_name);
        $this->assertStringNotContainsString('jawaban-mhs1.pdf', $submission->file_path);
        Storage::disk('local')->assertExists($submission->file_path);
        Storage::disk('public')->assertMissing($submission->file_path);
    }

    /**
     * [PRIORITAS #2] Dosen pengampu menilai submission dan notifikasi aman terkirim ke pemilik.
     */
    public function test_course_lecturer_can_grade_submission_and_trigger_notification(): void
    {
        Notification::fake();

        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs1->id,
        ]);

        $response = $this->actingAs($this->dosen1)->post(
            route('submissions.grades.store', $submission),
            [
                'score' => 90,
                'feedback' => 'Struktur pengujian sudah rapi.',
            ]
        );

        $response->assertRedirect(route('submissions.show', $submission));

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'graded_by' => $this->dosen1->id,
            'score' => 90,
        ]);

        Notification::assertSentTo(
            $this->mhs1,
            SubmissionGradedNotification::class,
            function (SubmissionGradedNotification $notification) use ($submission) {
                $payload = $notification->toDatabase($this->mhs1);

                return $payload['submission_id'] === $submission->id
                    && ! array_key_exists('score', $payload)
                    && ! array_key_exists('feedback', $payload);
            }
        );

        Notification::assertNotSentTo($this->mhs2, SubmissionGradedNotification::class);
    }

    /**
     * [PRIORITAS #3] Pengguna tanpa login (guest) ditolak dari seluruh rute terproteksi.
     */
    public function test_unauthenticated_guest_cannot_access_protected_routes(): void
    {
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs1->id,
        ]);

        $protectedWebRoutes = [
            route('dashboard'),
            route('courses.index'),
            route('users.index'),
            route('materials.download', $this->material1),
            route('submissions.download', $submission),
            route('courses.grades', $this->course1),
            route('notifications.index'),
        ];

        foreach ($protectedWebRoutes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('login'));
        }

        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->assertGuest();
    }

    /**
     * [PRIORITAS #4] Mahasiswa lain ditolak (403) melihat maupun mengunduh submission orang lain.
     */
    public function test_student_cannot_download_or_view_other_student_submission(): void
    {
        $subPath = UploadedFile::fake()
            ->create('rahasia-mhs1.pdf', 150, 'application/pdf')
            ->store('submissions/' . $this->assignment1->id, 'local');

        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs1->id,
            'file_path' => $subPath,
            'original_name' => 'rahasia-mhs1.pdf',
        ]);

        $this->actingAs($this->mhs2)
            ->get(route('submissions.show', $submission))
            ->assertForbidden();

        $this->actingAs($this->mhs2)
            ->get(route('submissions.download', $submission))
            ->assertForbidden();
    }

    /**
     * [PRIORITAS #5] Mahasiswa tidak terdaftar ditolak (403) membuka kelas, mengunduh materi, dan submit tugas.
     */
    public function test_unregistered_student_cannot_view_course_download_material_or_submit_assignment(): void
    {
        $this->actingAs($this->unregisteredMhs)
            ->get(route('courses.show', $this->course1))
            ->assertForbidden();

        $this->actingAs($this->unregisteredMhs)
            ->get(route('materials.download', $this->material1))
            ->assertForbidden();

        $file = UploadedFile::fake()->create('susupan.pdf', 120, 'application/pdf');
        $this->actingAs($this->unregisteredMhs)
            ->post(route('assignments.submissions.store', $this->assignment1), [
                'file' => $file,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->unregisteredMhs->id,
        ]);
    }

    /**
     * [PRIORITAS #6] Dosen lain ditolak (403) mengakses atau memodifikasi seluruh resource milik Dosen 1.
     */
    public function test_other_lecturer_cannot_access_or_modify_other_lecturer_course_resources(): void
    {
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs1->id,
        ]);

        $this->actingAs($this->dosen2)->get(route('courses.show', $this->course1))->assertForbidden();
        $this->actingAs($this->dosen2)->get(route('courses.edit', $this->course1))->assertForbidden();
        $this->actingAs($this->dosen2)->get(route('courses.submissions', $this->course1))->assertForbidden();
        $this->actingAs($this->dosen2)->get(route('courses.grades', $this->course1))->assertForbidden();
        $this->actingAs($this->dosen2)->get(route('materials.download', $this->material1))->assertForbidden();

        $this->actingAs($this->dosen2)->put(route('courses.update', $this->course1), [
            'code' => $this->course1->code,
            'name' => 'Diubah Dosen Lain',
            'sks' => 3,
            'lecturer_id' => $this->dosen2->id,
            'status' => 'active',
        ])->assertForbidden();

        $this->actingAs($this->dosen2)->post(route('courses.materials.store', $this->course1), [
            'title' => 'Materi Liar',
            'type' => 'text',
            'description' => 'Tidak sah',
        ])->assertForbidden();

        $this->actingAs($this->dosen2)->post(route('courses.assignments.store', $this->course1), [
            'title' => 'Tugas Liar',
            'due_at' => now()->addDays(2)->toDateTimeString(),
            'max_score' => 100,
            'status' => 'published',
        ])->assertForbidden();

        $this->actingAs($this->dosen2)->post(route('courses.enroll', $this->course1), [
            'student_id' => $this->unregisteredMhs->id,
        ])->assertForbidden();

        $this->actingAs($this->dosen2)->post(route('submissions.grades.store', $submission), [
            'score' => 75,
        ])->assertForbidden();

        $this->assertEquals('Pengujian Perangkat Lunak', $this->course1->fresh()->name);
        $this->assertDatabaseMissing('materials', ['title' => 'Materi Liar']);
        $this->assertDatabaseMissing('assignments', ['title' => 'Tugas Liar']);
        $this->assertDatabaseMissing('grades', ['submission_id' => $submission->id]);
    }

    /**
     * [PRIORITAS #7] Non-admin ditolak (403) membuat/menghapus mata kuliah dan mengakses daftar user/eskalasi role.
     */
    public function test_non_admin_cannot_create_delete_course_or_escalate_role(): void
    {
        $coursePayload = [
            'code' => 'ILEGAL99',
            'name' => 'Kelas Ilegal',
            'sks' => 3,
            'lecturer_id' => $this->dosen1->id,
            'status' => 'active',
        ];

        $this->actingAs($this->dosen1)->post(route('courses.store'), $coursePayload)->assertForbidden();
        $this->actingAs($this->mhs1)->post(route('courses.store'), $coursePayload)->assertForbidden();
        $this->assertDatabaseMissing('courses', ['code' => 'ILEGAL99']);

        $this->actingAs($this->dosen1)->delete(route('courses.destroy', $this->course1))->assertForbidden();
        $this->actingAs($this->mhs1)->delete(route('courses.destroy', $this->course1))->assertForbidden();
        $this->assertDatabaseHas('courses', ['id' => $this->course1->id]);

        $this->actingAs($this->dosen1)->get(route('users.index'))->assertForbidden();
        $this->actingAs($this->mhs1)->get(route('users.index'))->assertForbidden();

        $this->actingAs($this->mhs1)->put(route('users.update', $this->mhs1), [
            'name' => 'Mahasiswa Ubah Nama',
            'email' => $this->mhs1->email,
            'role' => 'admin',
        ]);

        $this->assertEquals('mahasiswa', $this->mhs1->fresh()->role);
    }

    /**
     * [PRIORITAS #8] Admin & mahasiswa ditolak (403) memberi nilai, dan non-mahasiswa ditolak submit tugas.
     */
    public function test_admin_and_student_cannot_grade_submission_nor_non_student_submit(): void
    {
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs1->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('submissions.grades.store', $submission), ['score' => 95])
            ->assertForbidden();

        $this->actingAs($this->mhs1)
            ->post(route('submissions.grades.store', $submission), ['score' => 100])
            ->assertForbidden();

        $this->actingAs($this->mhs1)
            ->get(route('courses.grades', $this->course1))
            ->assertForbidden();

        $file = UploadedFile::fake()->create('bukan-mahasiswa.pdf', 100, 'application/pdf');
        $this->actingAs($this->admin)
            ->post(route('assignments.submissions.store', $this->assignment1), ['file' => $file])
            ->assertForbidden();
        $this->actingAs($this->dosen1)
            ->post(route('assignments.submissions.store', $this->assignment1), ['file' => $file])
            ->assertForbidden();

        $this->assertDatabaseMissing('grades', ['submission_id' => $submission->id]);
        $this->assertEquals(1, Submission::where('assignment_id', $this->assignment1->id)->count());
    }

    /**
     * [PRIORITAS #9] Pengumpulan ditolak setelah tenggat ketika allow_late bernilai false.
     */
    public function test_submission_rejected_past_deadline_when_allow_late_is_false(): void
    {
        $pastAssignment = Assignment::factory()->create([
            'course_id' => $this->course1->id,
            'created_by' => $this->dosen1->id,
            'due_at' => now()->subMinute(),
            'allow_late' => false,
            'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('terlambat.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->mhs1)->post(
            route('assignments.submissions.store', $pastAssignment),
            ['file' => $file]
        );

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $pastAssignment->id,
            'user_id' => $this->mhs1->id,
        ]);
    }

    /**
     * [PRIORITAS #10] Validasi gagal menolak file/URL invalid pada materi & submission serta skor di luar rentang.
     */
    public function test_submission_material_and_grading_reject_invalid_inputs(): void
    {
        // 1. Submission: ekstensi .php dan ukuran > 5 MB
        $badSub = UploadedFile::fake()->create('shell.php', 50, 'application/x-php');
        $this->actingAs($this->mhs1)
            ->post(route('assignments.submissions.store', $this->assignment1), ['file' => $badSub])
            ->assertSessionHasErrors('file');

        $oversizedSub = UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf');
        $this->actingAs($this->mhs1)
            ->post(route('assignments.submissions.store', $this->assignment1), ['file' => $oversizedSub])
            ->assertSessionHasErrors('file');

        // 2. Materi: ekstensi .php dan type=link tanpa URL valid
        $badMat = UploadedFile::fake()->create('materi.php', 50, 'application/x-php');
        $this->actingAs($this->dosen1)
            ->post(route('courses.materials.store', $this->course1), [
                'title' => 'Materi Berbahaya',
                'type' => 'file',
                'file' => $badMat,
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($this->dosen1)
            ->post(route('courses.materials.store', $this->course1), [
                'title' => 'Materi Tautan Kosong',
                'type' => 'link',
                'external_url' => 'bukan-url',
            ])
            ->assertSessionHasErrors('external_url');

        // 3. Penilaian: skor < 0 atau > max_score (100)
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs1->id,
        ]);

        $this->actingAs($this->dosen1)
            ->post(route('submissions.grades.store', $submission), ['score' => -5])
            ->assertSessionHasErrors('score');

        $this->actingAs($this->dosen1)
            ->post(route('submissions.grades.store', $submission), ['score' => 150])
            ->assertSessionHasErrors('score');

        $this->assertDatabaseMissing('materials', ['title' => 'Materi Berbahaya']);
        $this->assertDatabaseMissing('materials', ['title' => 'Materi Tautan Kosong']);
        $this->assertDatabaseMissing('grades', ['submission_id' => $submission->id]);
        $this->assertCount(1, Storage::disk('local')->allFiles('materials/' . $this->course1->id));
    }

    /**
     * [Pelengkap #11] Notifikasi end-to-end terkirim ke mahasiswa terdaftar saat tugas published dibuat.
     */
    public function test_creating_published_assignment_sends_notification_end_to_end_to_enrolled_students(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->dosen1)->post(
            route('courses.assignments.store', $this->course1),
            [
                'title' => 'Tugas Notifikasi E2E',
                'instructions' => 'Uji notifikasi otomatis via controller.',
                'due_at' => now()->addDays(5)->toDateTimeString(),
                'max_score' => 100,
                'allow_late' => true,
                'status' => 'published',
            ]
        );

        $response->assertRedirect(route('courses.show', $this->course1));
        $assignment = Assignment::where('title', 'Tugas Notifikasi E2E')->firstOrFail();

        Notification::assertSentTo(
            [$this->mhs1, $this->mhs2],
            NewAssignmentNotification::class,
            fn (NewAssignmentNotification $n) => $n->toDatabase($this->mhs1)['assignment_id'] === $assignment->id
        );

        Notification::assertNotSentTo($this->unregisteredMhs, NewAssignmentNotification::class);
    }

    /**
     * [Pelengkap #12] Daftar pengumpulan tugas disaring ketat agar mahasiswa hanya melihat miliknya sendiri.
     */
    public function test_assignment_submissions_index_is_scoped_for_student(): void
    {
        Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs1->id,
            'original_name' => 'berkas-unik-mhs1.pdf',
        ]);

        Submission::factory()->create([
            'assignment_id' => $this->assignment1->id,
            'user_id' => $this->mhs2->id,
            'original_name' => 'berkas-rahasia-mhs2.pdf',
        ]);

        $responseMhs1 = $this->actingAs($this->mhs1)
            ->get(route('assignments.submissions.index', $this->assignment1));

        $responseMhs1->assertOk();
        $responseMhs1->assertSee('berkas-unik-mhs1.pdf');
        $responseMhs1->assertDontSee('berkas-rahasia-mhs2.pdf');
    }

    /**
     * [Pelengkap #13] Pendaftaran mahasiswa ganda bersifat idempoten dan ID non-mahasiswa ditolak.
     */
    public function test_duplicate_course_enrollment_is_idempotent_and_non_student_rejected(): void
    {
        $initialCount = DB::table('course_user')
            ->where('course_id', $this->course1->id)
            ->count();

        $dupResponse = $this->actingAs($this->dosen1)->post(
            route('courses.enroll', $this->course1),
            ['student_id' => $this->mhs1->id]
        );
        $dupResponse->assertSessionHas('info');

        $this->assertEquals(
            $initialCount,
            DB::table('course_user')->where('course_id', $this->course1->id)->count()
        );

        $nonStudentResponse = $this->actingAs($this->admin)->post(
            route('courses.enroll', $this->course1),
            ['student_id' => $this->dosen2->id]
        );
        $nonStudentResponse->assertNotFound();
    }
}
