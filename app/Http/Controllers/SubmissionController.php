<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    /**
     * Menampilkan daftar pengumpulan tugas.
     * Dosen/Admin: melihat seluruh mahasiswa terdaftar (siapa sudah/belum mengumpulkan, ditandai terlambat).
     * Mahasiswa: disaring di query level agar hanya melihat miliknya sendiri.
     */
    public function index(Assignment $assignment)
    {
        Gate::authorize('view', $assignment);

        $user = auth()->user();

        if ($user->role === 'mahasiswa') {
            $submissions = $assignment->submissions()
                ->with(['student', 'grade'])
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(15);

            return view('submissions.index', compact('assignment', 'submissions'));
        }

        // Khusus Dosen dan Admin:
        // Ambil pemetaan submission yang sudah masuk
        $submissions = $assignment->submissions()->with(['student', 'grade'])->get()->keyBy('user_id');
        // Urutkan mahasiswa: yang sudah mengumpulkan tampil paling atas
        $enrolledStudents = $assignment->course->students()
            ->orderBy('name')
            ->get()
            ->sortByDesc(fn($student) => $submissions->has($student->id))
            ->values();

        return view('submissions.index', compact('assignment', 'enrolledStudents', 'submissions'));
    }

    /**
     * Menyimpan atau memperbarui pengumpulan tugas oleh mahasiswa.
     * Berkas disimpan di disk privat, nama fisik acak, nama asli dicatat,
     * validasi mimes pdf/doc/docx maks 5MB, is_late dihitung server,
     * serta menerapkan updateOrCreate dan pembersihan berkas lama.
     */
    public function store(Request $request, Assignment $assignment)
    {
        Gate::authorize('create', [Submission::class, $assignment]);

        // Cek tenggat waktu jika pengumpulan terlambat tidak diizinkan
        $isLate = now()->greaterThan($assignment->due_at);
        if ($isLate && ! $assignment->allow_late) {
            return back()->withErrors([
                'file' => 'Batas waktu pengumpulan tugas telah berakhir dan pengumpulan terlambat tidak diizinkan.',
            ])->withInput();
        }

        $file = $request->file('file');

        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return back()->withErrors(['file' => 'Silakan pilih berkas tugas yang ingin dikumpulkan terlebih dahulu.'])->withInput();
        }

        if ($file->getError() === UPLOAD_ERR_INI_SIZE || $file->getError() === UPLOAD_ERR_FORM_SIZE) {
            return back()->withErrors(['file' => 'Ukuran berkas melebihi batas maksimal server.'])->withInput();
        }

        if (! $file->isValid()) {
            \Illuminate\Support\Facades\Log::warning('Submission file upload invalid', [
                'error_code'    => $file->getError(),
                'error_msg'     => $file->getErrorMessage(),
                'client_name'   => $file->getClientOriginalName(),
            ]);
            $errorMsg = match ($file->getError()) {
                UPLOAD_ERR_PARTIAL    => 'Pengunggahan berkas terputus atau berkas sedang dibuka/terkunci oleh aplikasi lain. Silakan tutup aplikasi pembaca berkas dan coba lagi.',
                UPLOAD_ERR_CANT_WRITE  => 'Server gagal menulis berkas ke direktori sementara.',
                UPLOAD_ERR_NO_TMP_DIR  => 'Direktori sementara penyimpanan berkas tidak ditemukan di server.',
                default                => 'Terjadi kesalahan saat mengunggah berkas: ' . $file->getErrorMessage(),
            };
            return back()->withErrors(['file' => $errorMsg])->withInput();
        }

        // Validasi: mimes pdf/doc/docx, maksimal 5 MB (5120 KB), wajib ada
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ], [
            'file.required' => 'Silakan pilih berkas tugas yang ingin dikumpulkan terlebih dahulu.',
            'file.mimes'    => 'Format berkas harus berupa PDF, DOC, atau DOCX.',
            'file.max'      => 'Ukuran berkas tugas tidak boleh melebihi 5 MB.',
            'file.file'     => 'Berkas yang diunggah harus berupa file yang valid.',
        ]);

        $userId = auth()->id();

        // Jika mahasiswa mengumpulkan ulang, bersihkan berkas fisik lama agar tidak menjadi berkas sampah/yatim
        $existing = $assignment->submissions()->where('user_id', $userId)->first();
        if ($existing && $existing->file_path && Storage::disk('local')->exists($existing->file_path)) {
            Storage::disk('local')->delete($existing->file_path);
        }

        // Berkas disimpan di disk privat 'local' (storage/app/private/submissions/{assignment_id})
        // Nama berkas fisik acak dihasilkan otomatis oleh store() untuk mencegah eksploitasi Path Traversal
        $filePath = $file->store('submissions/' . $assignment->id, 'local');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // Terapkan updateOrCreate sesuai unique composite (assignment_id, user_id)
        $submission = $assignment->submissions()->updateOrCreate(
            ['user_id' => $userId],
            [
                'file_path'     => $filePath,
                'original_name' => $originalName,
                'file_size'     => $fileSize,
                'note'          => $validated['note'] ?? null,
                'submitted_at'  => now(),
                'is_late'       => $isLate,
            ]
        );

        return redirect()->route('assignments.show', $assignment)->with('success', 'Tugas berhasil dikumpulkan.');
    }

    /**
     * Menampilkan detail satu pengumpulan tugas.
     * Dilindungi secara ketat oleh SubmissionPolicy::view().
     */
    public function show(Submission $submission)
    {
        Gate::authorize('view', $submission);

        $submission->load(['assignment.course', 'student', 'grade.grader']);

        // Navigasi cepat untuk Dosen/Admin saat memeriksa berkas secara berurutan (looping penilaian)
        $prevSubmission = null;
        $nextSubmission = null;

        if (auth()->user()?->role === 'dosen' || auth()->user()?->role === 'admin') {
            $prevSubmission = $submission->assignment->submissions()
                ->where('id', '<', $submission->id)
                ->orderBy('id', 'desc')
                ->first();
            $nextSubmission = $submission->assignment->submissions()
                ->where('id', '>', $submission->id)
                ->orderBy('id', 'asc')
                ->first();
        }

        return view('submissions.show', compact('submission', 'prevSubmission', 'nextSubmission'));
    }

    /**
     * Endpoint unduhan berkas submission dari disk privat.
     * Memanggil Policy via Gate::authorize() sebelum mengirim berkas (di Laravel 12 $this->authorize() tidak ada).
     */
    public function download(Submission $submission)
    {
        Gate::authorize('download', $submission);

        if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
            return back()->with('error', 'Berkas submission tidak ditemukan pada penyimpanan server.');
        }

        return Storage::disk('local')->download($submission->file_path, $submission->original_name);
    }

    /**
     * Menghapus pengumpulan tugas (jika belum dinilai).
     * Memicu static::deleting pada model Submission yang otomatis menghapus berkas fisik di disk privat.
     */
    public function destroy(Submission $submission)
    {
        Gate::authorize('delete', $submission);

        $assignment = $submission->assignment;
        $submission->delete();

        return redirect()->route('assignments.show', $assignment)->with('success', 'Pengumpulan tugas berhasil dihapus.');
    }

    /**
     * Memberi nilai pada pengumpulan tugas (Dosen pengampu).
     */
    public function grade(Request $request, Submission $submission)
    {
        Gate::authorize('create', [Grade::class, $submission]);

        $maxScore = $submission->assignment->max_score ?? 100;

        $validated = $request->validate([
            'score'    => ['required', 'numeric', 'min:0', 'max:' . $maxScore],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        $submission->grade()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'score'     => $validated['score'],
                'feedback'  => $validated['feedback'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]
        );

        event(new \App\Events\SubmissionGraded($submission));

        return redirect()->route('submissions.show', $submission)->with('success', 'Nilai berhasil disimpan.');
    }

    /**
     * Menghapus nilai pengumpulan tugas (Dosen pengampu / Admin).
     */
    public function destroyGrade(Submission $submission)
    {
        if ($submission->grade) {
            Gate::authorize('delete', $submission->grade);
            $submission->grade->delete();
        }

        return redirect()->route('submissions.show', $submission)->with('success', 'Nilai pengumpulan tugas berhasil dihapus.');
    }
}
