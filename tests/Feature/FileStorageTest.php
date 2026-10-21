<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileStorageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $dosen;
    private User $otherDosen;
    private User $mhs1;
    private User $mhs2;
    private Course $course;
    private Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->dosen = User::factory()->create(['role' => 'dosen']);
        $this->otherDosen = User::factory()->create(['role' => 'dosen']);
        $this->mhs1 = User::factory()->create(['role' => 'mahasiswa']);
        $this->mhs2 = User::factory()->create(['role' => 'mahasiswa']);

        $this->course = Course::factory()->create([
            'lecturer_id' => $this->dosen->id,
            'status' => 'active',
        ]);
        $this->course->students()->attach([$this->mhs1->id, $this->mhs2->id]);

        $this->assignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'created_by' => $this->dosen->id,
            'title' => 'Tugas 01 Pemrograman Web',
            'due_at' => now()->addDays(5),
            'allow_late' => true,
            'status' => 'published',
        ]);
    }

    public function test_submission_stored_in_private_disk_with_randomized_filename(): void
    {
        $file = UploadedFile::fake()->create('laporan_akhir.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->mhs1)->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => $file,
            'note' => 'Pengumpulan tugas pertama',
        ]);

        $response->assertRedirect("/assignments/{$this->assignment->id}");

        $submission = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->mhs1->id)
            ->first();

        $this->assertNotNull($submission);
        $this->assertEquals('laporan_akhir.pdf', $submission->original_name);
        $this->assertGreaterThan(0, $submission->file_size);

        // Pastikan nama berkas fisik diacak (tidak sama dengan laporan_akhir.pdf)
        $this->assertStringNotContainsString('laporan_akhir.pdf', $submission->file_path);

        // Pastikan tersimpan di disk privat (local), BUKAN di storage/app/public
        Storage::disk('local')->assertExists($submission->file_path);
        Storage::disk('public')->assertMissing($submission->file_path);
    }

    public function test_submission_validation_rejects_disallowed_mimes_and_over_5mb(): void
    {
        // 1. File ekstensi dilarang (.php / .exe / .txt)
        $badFile = UploadedFile::fake()->create('script.php', 100, 'application/x-php');
        $responseBadExt = $this->actingAs($this->mhs1)->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => $badFile,
        ]);
        $responseBadExt->assertSessionHasErrors('file');

        // 2. File melebihi batas 5 MB (5120 KB + 100 KB)
        $largeFile = UploadedFile::fake()->create('tugas_raksasa.pdf', 5300, 'application/pdf');
        $responseLarge = $this->actingAs($this->mhs1)->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => $largeFile,
        ]);
        $responseLarge->assertSessionHasErrors('file');

        // 3. File yang sah (.docx 2MB)
        $validFile = UploadedFile::fake()->create('jawaban_tugas.docx', 2048, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $responseValid = $this->actingAs($this->mhs1)->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => $validFile,
        ]);
        $responseValid->assertSessionHasNoErrors();
    }

    public function test_resubmission_uses_update_or_create_and_deletes_old_physical_file(): void
    {
        // Pengumpulan pertama
        $firstFile = UploadedFile::fake()->create('versi_1.pdf', 300, 'application/pdf');
        $this->actingAs($this->mhs1)->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => $firstFile,
        ]);

        $firstSubmission = Submission::where('user_id', $this->mhs1->id)->first();
        $oldPath = $firstSubmission->file_path;
        Storage::disk('local')->assertExists($oldPath);

        // Pengumpulan kedua (kumpulkan ulang / revisi)
        $secondFile = UploadedFile::fake()->create('versi_revisi_final.docx', 400, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->actingAs($this->mhs1)->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => $secondFile,
            'note' => 'Perbaikan nomor 3',
        ]);

        // Jumlah record di database tetap 1 (updateOrCreate)
        $this->assertEquals(1, Submission::where('assignment_id', $this->assignment->id)->where('user_id', $this->mhs1->id)->count());

        $updatedSubmission = Submission::where('user_id', $this->mhs1->id)->first();
        $this->assertEquals('versi_revisi_final.docx', $updatedSubmission->original_name);
        $this->assertEquals('Perbaikan nomor 3', $updatedSubmission->note);

        // Berkas fisik lama otomatis terhapus untuk mencegah penumpukan sampah
        Storage::disk('local')->assertMissing($oldPath);
        // Berkas fisik baru ada di penyimpanan
        Storage::disk('local')->assertExists($updatedSubmission->file_path);
    }

    public function test_submission_calculates_is_late_server_side(): void
    {
        // Tugas yang sudah lewat deadline
        $pastAssignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'created_by' => $this->dosen->id,
            'due_at' => now()->subDay(),
            'allow_late' => true,
            'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('tugas_terlambat.pdf', 200, 'application/pdf');
        $this->actingAs($this->mhs1)->post("/assignments/{$pastAssignment->id}/submissions", [
            'file' => $file,
        ]);

        $submission = Submission::where('assignment_id', $pastAssignment->id)->where('user_id', $this->mhs1->id)->first();
        $this->assertTrue($submission->is_late);
    }

    public function test_submission_rejected_when_past_due_and_allow_late_is_false(): void
    {
        $strictAssignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'created_by' => $this->dosen->id,
            'due_at' => now()->subHours(2),
            'allow_late' => false,
            'status' => 'published',
        ]);

        $file = UploadedFile::fake()->create('tugas_telat.pdf', 150, 'application/pdf');
        $response = $this->actingAs($this->mhs1)->post("/assignments/{$strictAssignment->id}/submissions", [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertEquals(0, Submission::where('assignment_id', $strictAssignment->id)->count());
    }

    public function test_submission_download_authorizes_policy_and_streams_file(): void
    {
        $file = UploadedFile::fake()->create('rahasia_mhs1.pdf', 120, 'application/pdf');
        $this->actingAs($this->mhs1)->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => $file,
        ]);

        $submission = Submission::where('user_id', $this->mhs1->id)->first();

        // 1. Mahasiswa pemilik boleh mengunduh
        $responseOwner = $this->actingAs($this->mhs1)->get("/submissions/{$submission->id}/download");
        $responseOwner->assertStatus(200);

        // 2. Dosen pengampu boleh mengunduh
        $responseLecturer = $this->actingAs($this->dosen)->get("/submissions/{$submission->id}/download");
        $responseLecturer->assertStatus(200);

        // 3. Admin boleh mengunduh
        $responseAdmin = $this->actingAs($this->admin)->get("/submissions/{$submission->id}/download");
        $responseAdmin->assertStatus(200);

        // 4. Mahasiswa lain DITOLAK (IDOR Protection)
        $responseOtherMhs = $this->actingAs($this->mhs2)->get("/submissions/{$submission->id}/download");
        $responseOtherMhs->assertStatus(403);

        // 5. Dosen lain DITOLAK
        $responseOtherDosen = $this->actingAs($this->otherDosen)->get("/submissions/{$submission->id}/download");
        $responseOtherDosen->assertStatus(403);
    }

    public function test_physical_file_deleted_when_submission_record_is_deleted(): void
    {
        $file = UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf');
        $this->actingAs($this->mhs1)->post("/assignments/{$this->assignment->id}/submissions", [
            'file' => $file,
        ]);

        $submission = Submission::where('user_id', $this->mhs1->id)->first();
        $filePath = $submission->file_path;
        Storage::disk('local')->assertExists($filePath);

        // Mahasiswa menghapus pengumpulannya
        $response = $this->actingAs($this->mhs1)->delete("/submissions/{$submission->id}");
        $response->assertRedirect("/assignments/{$this->assignment->id}");

        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
        // Verifikasi berkas fisik di disk privat ikut terhapus
        Storage::disk('local')->assertMissing($filePath);
    }


    public function test_material_file_upload_download_and_deletion(): void
    {
        $file = UploadedFile::fake()->create('slide_kuliah.pptx', 1024, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $response = $this->actingAs($this->dosen)->post("/courses/{$this->course->id}/materials", [
            'title' => 'Pertemuan 09 Slide',
            'type' => 'file',
            'file' => $file,
        ]);

        $response->assertRedirect("/courses/{$this->course->id}");

        $material = Material::where('course_id', $this->course->id)->first();
        $this->assertNotNull($material);
        $this->assertEquals('slide_kuliah.pptx', $material->original_name);
        Storage::disk('local')->assertExists($material->file_path);

        // Mahasiswa terdaftar dapat mengunduh materi
        $responseDownload = $this->actingAs($this->mhs1)->get("/materials/{$material->id}/download");
        $responseDownload->assertStatus(200);

        // Dosen menghapus materi
        $this->actingAs($this->dosen)->delete("/materials/{$material->id}");
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        Storage::disk('local')->assertMissing($material->file_path);
    }
}
