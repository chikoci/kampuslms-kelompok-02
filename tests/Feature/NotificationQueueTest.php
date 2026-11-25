<?php

namespace Tests\Feature;

use App\Events\AssignmentPublished;
use App\Events\SubmissionGraded;
use App\Listeners\SendAssignmentNotification;
use App\Listeners\SendGradeNotification;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\NewAssignmentNotification;
use App\Notifications\SubmissionGradedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $student1;
    private User $student2;
    private User $unregisteredStudent;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $this->student1 = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $this->student2 = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $this->unregisteredStudent = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $this->course = Course::factory()->create([
            'lecturer_id' => $this->dosen->id,
            'status'      => 'active',
        ]);

        // Daftarkan student 1 dan 2 ke kelas
        $this->course->students()->attach([$this->student1->id, $this->student2->id]);
    }

    /**
     * Uji 1: Membuat tugas dengan status published memicu event AssignmentPublished.
     */
    public function test_creating_published_assignment_dispatches_event(): void
    {
        Event::fake([AssignmentPublished::class]);

        $this->actingAs($this->dosen)->post(route('courses.assignments.store', $this->course), [
            'title'        => 'Tugas Queue Asinkron',
            'instructions' => 'Kerjakan modul queue',
            'due_at'       => now()->addDays(7)->toDateTimeString(),
            'max_score'    => 100,
            'allow_late'   => false,
            'status'       => 'published',
        ]);

        Event::assertDispatched(AssignmentPublished::class, function ($event) {
            return $event->assignment->title === 'Tugas Queue Asinkron';
        });
    }

    /**
     * Uji 2: Listener SendAssignmentNotification mengimplementasikan ShouldQueue dan mengirim notifikasi ke mahasiswa terdaftar.
     */
    public function test_listener_implements_should_queue_and_sends_notification_to_enrolled_students(): void
    {
        Notification::fake();

        $assignment = Assignment::factory()->create([
            'course_id'   => $this->course->id,
            'created_by'  => $this->dosen->id,
            'title'       => 'Tugas Queue 1',
            'status'      => 'published',
        ]);

        $listener = new SendAssignmentNotification();
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
        $this->assertEquals(3, $listener->tries);
        $this->assertEquals(10, $listener->backoff);

        $event = new AssignmentPublished($assignment);
        $listener->handle($event);

        // Mahasiswa terdaftar harus menerima notifikasi
        Notification::assertSentTo(
            [$this->student1, $this->student2],
            NewAssignmentNotification::class,
            function ($notification) use ($assignment) {
                return $notification->assignment->id === $assignment->id;
            }
        );

        // Mahasiswa luar tidak boleh menerima notifikasi
        Notification::assertNotSentTo(
            $this->unregisteredStudent,
            NewAssignmentNotification::class
        );
    }

    /**
     * Uji 3: Payload NewAssignmentNotification bebas dari data sensitif.
     */
    public function test_new_assignment_notification_payload_is_safe(): void
    {
        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
            'title'      => 'Tugas Keamanan Payload',
        ]);

        $notification = new NewAssignmentNotification($assignment);
        $payload = $notification->toDatabase($this->student1);

        $this->assertEquals($assignment->id, $payload['assignment_id']);
        $this->assertEquals('Tugas Keamanan Payload', $payload['title']);
        $this->assertEquals($this->course->name, $payload['course_name']);
        $this->assertArrayNotHasKey('score', $payload);
        $this->assertArrayNotHasKey('feedback', $payload);
        $this->assertArrayNotHasKey('password', $payload);
    }

    /**
     * Uji 4: Penilaian tugas memicu event SubmissionGraded dan mengirim notifikasi ke mahasiswa pemilik.
     */
    public function test_grading_submission_dispatches_event_and_notifies_student_safely(): void
    {
        Notification::fake();

        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $this->student1->id,
        ]);

        // Dosen memberi nilai
        $this->actingAs($this->dosen)->post(route('submissions.grades.store', $submission), [
            'score'    => 92.5,
            'feedback' => 'Penjelasan alur queue sangat baik.',
        ]);

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score'         => 92.5,
            'graded_by'     => $this->dosen->id,
        ]);

        // Jalankan listener secara eksplisit
        $listener = new SendGradeNotification();
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
        $this->assertEquals(3, $listener->tries);
        $this->assertEquals(10, $listener->backoff);

        $event = new SubmissionGraded($submission);
        $listener->handle($event);

        Notification::assertSentTo(
            $this->student1,
            SubmissionGradedNotification::class
        );

        // Uji payload: Nilai (score) dan feedback DILARANG masuk payload notifikasi
        $notif = new SubmissionGradedNotification($submission);
        $payload = $notif->toDatabase($this->student1);

        $this->assertArrayNotHasKey('score', $payload, 'Payload notifikasi tidak boleh memuat score!');
        $this->assertArrayNotHasKey('feedback', $payload, 'Payload notifikasi tidak boleh memuat feedback!');
        $this->assertEquals($submission->id, $payload['submission_id']);
    }

    /**
     * Uji 4b: Method failed() pada SendGradeNotification mencatat submission_id dan pesan error ke log.
     */
    public function test_send_grade_notification_failed_handler_logs_submission_id(): void
    {
        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'SendGradeNotification')
                    && isset($context['submission_id'])
                    && $context['submission_id'] !== null
                    && ($context['error'] ?? '') === 'Simulasi kegagalan worker';
            });

        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $this->student1->id,
        ]);

        $listener = new SendGradeNotification();
        $listener->failed(new SubmissionGraded($submission), new \RuntimeException('Simulasi kegagalan worker'));
    }

    /**
     * Uji 5: Endpoint polling unread-count mengembalikan JSON jumlah notifikasi belum dibaca.
     */
    public function test_unread_count_json_endpoint_for_navbar_polling(): void
    {
        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
        ]);

        // Kirim 2 notifikasi nyata ke database
        $this->student1->notify(new NewAssignmentNotification($assignment));
        $this->student1->notify(new NewAssignmentNotification($assignment));

        $response = $this->actingAs($this->student1)->getJson(route('notifications.unreadCount'));

        $response->assertStatus(200)
            ->assertJson(['unread_count' => 2]);
    }

    /**
     * Uji 6: Mahasiswa dapat melihat daftar notifikasi dan menandai notifikasinya sebagai sudah dibaca.
     */
    public function test_student_can_view_and_mark_own_notification_as_read(): void
    {
        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
        ]);

        $this->student1->notify(new NewAssignmentNotification($assignment));
        $notification = $this->student1->notifications()->first();

        $this->assertNull($notification->read_at);

        // Buka halaman notifikasi
        $viewResponse = $this->actingAs($this->student1)->get(route('notifications.index'));
        $viewResponse->assertStatus(200)
            ->assertSee('Pusat Pemberitahuan')
            ->assertSee($assignment->title);

        // Tandai sudah dibaca
        $postResponse = $this->actingAs($this->student1)->post(route('notifications.read', $notification->id));
        $postResponse->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * Uji 7: Proteksi IDOR ketat: Mahasiswa TIDAK BISA menandai notifikasi milik mahasiswa lain.
     */
    public function test_anti_idor_student_cannot_mark_other_student_notification(): void
    {
        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
        ]);

        // Buat notifikasi milik student 1
        $this->student1->notify(new NewAssignmentNotification($assignment));
        $notifStudent1 = $this->student1->notifications()->first();

        // Student 2 mencoba menandai dibaca notifikasi milik Student 1
        $response = $this->actingAs($this->student2)->post(route('notifications.read', $notifStudent1->id));

        // Harus 404 Not Found karena scoped ke user yang sedang login
        $response->assertStatus(404);
        $this->assertNull($notifStudent1->fresh()->read_at);
    }

    /**
     * Uji 8: Dosen dan Admin dapat melihat rekap nilai kelas, sedangkan Mahasiswa ditolak.
     */
    public function test_course_grades_matrix_access_authorization(): void
    {
        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
        ]);

        // Dosen pengampu bisa mengakses
        $responseDosen = $this->actingAs($this->dosen)->get(route('courses.grades', $this->course));
        $responseDosen->assertStatus(200)
            ->assertSee('Rekapitulasi Nilai Akademik Kelas')
            ->assertSee($this->student1->name);

        // Mahasiswa ditolak (403 Forbidden via CoursePolicy)
        $responseMhs = $this->actingAs($this->student1)->get(route('courses.grades', $this->course));
        $responseMhs->assertStatus(403);
    }

    /**
     * Uji 9: Mahasiswa dapat melihat halaman rekap transkrip nilai pribadinya (Nilai Saya).
     */
    public function test_student_can_view_own_grades_transcript(): void
    {
        $assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
            'status'     => 'published',
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id'       => $this->student1->id,
        ]);

        Grade::factory()->create([
            'submission_id' => $submission->id,
            'score'         => 88.0,
            'feedback'      => 'Sangat memuaskan.',
            'graded_by'     => $this->dosen->id,
        ]);

        $response = $this->actingAs($this->student1)->get(route('grades.student'));
        $response->assertStatus(200)
            ->assertSee('Transkrip & Rekap Nilai Tugas', false)
            ->assertSee('88.0')
            ->assertSee('Sangat memuaskan.');
    }
}
