<?php

namespace Tests\Feature;

use App\Events\SubmissionGraded;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Rubric;
use App\Models\RubricAssessment;
use App\Models\RubricCriterion;
use App\Models\RubricLevel;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RubricTieredGradingTest extends TestCase
{
    use RefreshDatabase;

    private User $dosen;
    private User $otherDosen;
    private User $student;
    private User $otherStudent;
    private Course $course;
    private Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dosen = User::factory()->create(['role' => 'dosen']);
        $this->otherDosen = User::factory()->create(['role' => 'dosen']);
        $this->student = User::factory()->create(['role' => 'mahasiswa']);
        $this->otherStudent = User::factory()->create(['role' => 'mahasiswa']);

        $this->course = Course::factory()->create(['lecturer_id' => $this->dosen->id]);
        $this->course->students()->attach([$this->student->id, $this->otherStudent->id]);

        $this->assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->dosen->id,
            'max_score'  => 100,
            'status'     => 'published',
        ]);
    }

    public function test_dosen_can_open_manage_rubric_page(): void
    {
        $response = $this->actingAs($this->dosen)
            ->get(route('assignments.rubric.manage', $this->assignment));

        $response->assertOk();
        $response->assertSee('Rubrik Penilaian Bertingkat');
        $response->assertSee('Status Total Bobot Kriteria');
    }

    public function test_dosen_can_create_rubric_with_weighted_criteria_and_tiered_levels(): void
    {
        $payload = [
            'title'       => 'Rubrik Penilaian Proyek',
            'description' => 'Panduan evaluasi komprehensif',
            'criteria'    => [
                [
                    'title'       => 'Kualitas Logika & Kode',
                    'description' => 'Arsitektur dan clean code',
                    'weight'      => 60.00,
                    'levels'      => [
                        ['title' => 'Sangat Baik', 'description' => 'Sempurna', 'score_percentage' => 100.00],
                        ['title' => 'Cukup', 'description' => 'Cukup baik', 'score_percentage' => 60.00],
                    ],
                ],
                [
                    'title'       => 'Dokumentasi & Laporan',
                    'description' => 'Kelengkapan berkas',
                    'weight'      => 40.00,
                    'levels'      => [
                        ['title' => 'Lengkap', 'description' => 'Sesuai format', 'score_percentage' => 100.00],
                        ['title' => 'Kurang', 'description' => 'Tidak lengkap', 'score_percentage' => 40.00],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('assignments.rubric.store', $this->assignment), $payload);

        $response->assertRedirect(route('assignments.show', $this->assignment));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rubrics', [
            'assignment_id' => $this->assignment->id,
            'title'         => 'Rubrik Penilaian Proyek',
        ]);

        $this->assertDatabaseHas('rubric_criteria', [
            'title'  => 'Kualitas Logika & Kode',
            'weight' => 60.00,
        ]);

        $this->assertDatabaseHas('rubric_criteria', [
            'title'  => 'Dokumentasi & Laporan',
            'weight' => 40.00,
        ]);

        $this->assertDatabaseHas('rubric_levels', [
            'title'            => 'Sangat Baik',
            'score_percentage' => 100.00,
        ]);
    }

    public function test_rubric_creation_rejects_when_total_weight_is_not_100_percent(): void
    {
        $payload = [
            'title'       => 'Rubrik Cacat',
            'description' => 'Total bobot hanya 70%',
            'criteria'    => [
                [
                    'title'  => 'Kriteria 1',
                    'weight' => 50.00,
                    'levels' => [
                        ['title' => 'Baik', 'score_percentage' => 100.00],
                    ],
                ],
                [
                    'title'  => 'Kriteria 2',
                    'weight' => 20.00,
                    'levels' => [
                        ['title' => 'Baik', 'score_percentage' => 100.00],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('assignments.rubric.store', $this->assignment), $payload);

        $response->assertSessionHasErrors(['criteria']);
        $this->assertDatabaseMissing('rubrics', ['title' => 'Rubrik Cacat']);
    }

    public function test_student_and_other_lecturer_cannot_manage_rubric(): void
    {
        // 1. Mahasiswa mencoba mengelola rubrik -> 403
        $response = $this->actingAs($this->student)
            ->get(route('assignments.rubric.manage', $this->assignment));
        $response->assertForbidden();

        // 2. Dosen dari mata kuliah lain mencoba mengelola rubrik -> 403
        $response = $this->actingAs($this->otherDosen)
            ->get(route('assignments.rubric.manage', $this->assignment));
        $response->assertForbidden();
    }

    public function test_dosen_grading_with_rubric_automatically_calculates_and_stores_grade(): void
    {
        Event::fake([SubmissionGraded::class]);

        // Buat rubrik dengan 2 kriteria: 60% dan 40%
        $rubric = Rubric::create([
            'assignment_id' => $this->assignment->id,
            'title'         => 'Rubrik Skripsi/Proyek',
        ]);

        $c1 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'title'     => 'Fungsionalitas',
            'weight'    => 60.00,
            'order'     => 0,
        ]);
        $l1_100 = RubricLevel::create([
            'rubric_criterion_id' => $c1->id,
            'title'               => 'Sempurna',
            'score_percentage'    => 100.00,
        ]);
        $l1_50 = RubricLevel::create([
            'rubric_criterion_id' => $c1->id,
            'title'               => 'Cukup',
            'score_percentage'    => 50.00,
        ]);

        $c2 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'title'     => 'Kerapian Kode',
            'weight'    => 40.00,
            'order'     => 1,
        ]);
        $l2_100 = RubricLevel::create([
            'rubric_criterion_id' => $c2->id,
            'title'               => 'Sangat Rapi',
            'score_percentage'    => 100.00,
        ]);
        $l2_50 = RubricLevel::create([
            'rubric_criterion_id' => $c2->id,
            'title'               => 'Sedang',
            'score_percentage'    => 50.00,
        ]);

        // Mahasiswa mengumpulkan tugas
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->student->id,
        ]);

        // Dosen menilai menggunakan rubrik:
        // C1 (bobot 60%) memilih level 100% -> 60.00 poin
        // C2 (bobot 40%) memilih level 50%  -> 20.00 poin
        // Total kalkulasi otomatis = 80.00 poin
        $gradingPayload = [
            'rubric_levels' => [
                $c1->id => $l1_100->id,
                $c2->id => $l2_50->id,
            ],
            'rubric_criterion_feedback' => [
                $c1->id => 'Implementasi sangat memuaskan.',
                $c2->id => 'Perlu perapian indentasi kode.',
            ],
            'feedback' => 'Kerja yang bagus secara keseluruhan!',
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('submissions.grades.store', $submission), $gradingPayload);

        $response->assertRedirect(route('submissions.show', $submission));
        $response->assertSessionHas('success');

        // Nilai akhir otomatis tersimpan pada tabel grades (skema Bagian 4 tetap utuh)
        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score'         => 80.00,
            'graded_by'     => $this->dosen->id,
            'feedback'      => 'Kerja yang bagus secara keseluruhan!',
        ]);

        // Rincian tersimpan pada rubric_assessments
        $this->assertDatabaseHas('rubric_assessments', [
            'submission_id'       => $submission->id,
            'rubric_criterion_id' => $c1->id,
            'rubric_level_id'     => $l1_100->id,
            'score_awarded'       => 60.00,
            'feedback'            => 'Implementasi sangat memuaskan.',
        ]);

        $this->assertDatabaseHas('rubric_assessments', [
            'submission_id'       => $submission->id,
            'rubric_criterion_id' => $c2->id,
            'rubric_level_id'     => $l2_50->id,
            'score_awarded'       => 20.00,
            'feedback'            => 'Perlu perapian indentasi kode.',
        ]);

        Event::assertDispatched(SubmissionGraded::class);
    }

    public function test_student_can_view_own_rubric_breakdown_but_cannot_access_other_student_submission(): void
    {
        $rubric = Rubric::create(['assignment_id' => $this->assignment->id, 'title' => 'Rubrik']);
        $criterion = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'title'     => 'Kriteria Utama',
            'weight'    => 100.00,
        ]);
        $level = RubricLevel::create([
            'rubric_criterion_id' => $criterion->id,
            'title'               => 'Hebat',
            'score_percentage'    => 100.00,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->student->id,
        ]);

        Grade::create([
            'submission_id' => $submission->id,
            'graded_by'     => $this->dosen->id,
            'score'         => 100.00,
            'graded_at'     => now(),
        ]);

        RubricAssessment::create([
            'submission_id'       => $submission->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id'     => $level->id,
            'score_awarded'       => 100.00,
            'feedback'            => 'Sangat baik',
        ]);

        // 1. Mahasiswa pemilik melihat submission sendiri -> 200 OK dan melihat rincian rubrik
        $response = $this->actingAs($this->student)
            ->get(route('submissions.show', $submission));

        $response->assertOk();
        $response->assertSee('Rincian Rubrik Penilaian');
        $response->assertSee('Kriteria Utama');
        $response->assertSee('Hebat');

        // 2. Mahasiswa lain mencoba mengakses -> 403 Forbidden (Anti IDOR)
        $responseOther = $this->actingAs($this->otherStudent)
            ->get(route('submissions.show', $submission));

        $responseOther->assertForbidden();
    }

    public function test_deleting_grade_also_cleans_up_rubric_assessments(): void
    {
        $rubric = Rubric::create(['assignment_id' => $this->assignment->id, 'title' => 'Rubrik']);
        $criterion = RubricCriterion::create(['rubric_id' => $rubric->id, 'title' => 'C1', 'weight' => 100.00]);
        $level = RubricLevel::create(['rubric_criterion_id' => $criterion->id, 'title' => 'L1', 'score_percentage' => 100.00]);

        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->student->id,
        ]);

        $grade = Grade::create([
            'submission_id' => $submission->id,
            'graded_by'     => $this->dosen->id,
            'score'         => 100.00,
            'graded_at'     => now(),
        ]);

        RubricAssessment::create([
            'submission_id'       => $submission->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id'     => $level->id,
            'score_awarded'       => 100.00,
        ]);

        $this->assertDatabaseCount('rubric_assessments', 1);

        $response = $this->actingAs($this->dosen)
            ->delete(route('submissions.grades.destroy', $submission));

        $response->assertRedirect(route('submissions.show', $submission));
        $this->assertDatabaseMissing('grades', ['id' => $grade->id]);
        $this->assertDatabaseCount('rubric_assessments', 0);
    }

    public function test_rubric_cannot_be_updated_once_assessed(): void
    {
        $rubric = Rubric::create(['assignment_id' => $this->assignment->id, 'title' => 'Rubrik']);
        $criterion = RubricCriterion::create(['rubric_id' => $rubric->id, 'title' => 'C1', 'weight' => 100.00]);
        $level = RubricLevel::create(['rubric_criterion_id' => $criterion->id, 'title' => 'L1', 'score_percentage' => 100.00]);

        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->student->id,
        ]);

        RubricAssessment::create([
            'submission_id'       => $submission->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id'     => $level->id,
            'score_awarded'       => 100.00,
        ]);

        $payload = [
            'title'       => 'Judul Baru',
            'description' => 'Deskripsi baru',
            'criteria'    => [
                [
                    'title'    => 'Kriteria Baru',
                    'weight'   => 100.00,
                    'levels'   => [
                        ['title' => 'Baru', 'score_percentage' => 100.00],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->dosen)
            ->post(route('assignments.rubric.store', $this->assignment), $payload);

        $response->assertRedirect(route('assignments.rubric.manage', $this->assignment));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('rubrics', ['id' => $rubric->id, 'title' => 'Rubrik']);
    }

    public function test_rubric_cannot_be_deleted_once_assessed(): void
    {
        $rubric = Rubric::create(['assignment_id' => $this->assignment->id, 'title' => 'Rubrik']);
        $criterion = RubricCriterion::create(['rubric_id' => $rubric->id, 'title' => 'C1', 'weight' => 100.00]);
        $level = RubricLevel::create(['rubric_criterion_id' => $criterion->id, 'title' => 'L1', 'score_percentage' => 100.00]);

        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->student->id,
        ]);

        RubricAssessment::create([
            'submission_id'       => $submission->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id'     => $level->id,
            'score_awarded'       => 100.00,
        ]);

        $response = $this->actingAs($this->dosen)
            ->delete(route('assignments.rubric.destroy', $this->assignment));

        $response->assertRedirect(route('assignments.rubric.manage', $this->assignment));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('rubrics', ['id' => $rubric->id]);
        $this->assertDatabaseCount('rubric_assessments', 1);
    }

    public function test_rubric_criterion_calculate_score_method(): void
    {
        $criterion = new RubricCriterion(['weight' => 40.00]);
        $level = new RubricLevel(['score_percentage' => 80.00]);

        // (40 / 100) * (80 / 100) * 100 = 32.00
        $score = $criterion->calculateScore($level, 100);
        $this->assertEquals(32.00, $score);

        // Uji dengan max score 50: (40 / 100) * (80 / 100) * 50 = 16.00
        $score50 = $criterion->calculateScore($level, 50);
        $this->assertEquals(16.00, $score50);
    }
}

