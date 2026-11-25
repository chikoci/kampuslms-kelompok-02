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
    public function index(Request $request, Assignment $assignment)
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
        $allowedPerPage = [15, 25, 50];
        $perPage = $request->integer('per_page', 15);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        // Urutkan di level SQL: mahasiswa yang sudah mengumpulkan tampil paling atas, lalu urut nama
        $enrolledStudents = $assignment->course->students()
            ->orderByDesc(
                Submission::selectRaw('1')
                    ->whereColumn('submissions.user_id', 'users.id')
                    ->where('submissions.assignment_id', $assignment->id)
                    ->limit(1)
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        // Ambil pemetaan submission HANYA untuk mahasiswa di halaman aktif
        $submissions = $assignment->submissions()
            ->whereIn('user_id', $enrolledStudents->pluck('id'))
            ->with(['student', 'grade'])
            ->get()
            ->keyBy('user_id');

        // Agregasi metrik ringkasan kelas dihitung di Controller (MVC)
        $totalEnrolled = $enrolledStudents->total();
        $submittedCount = $assignment->submissions()->count();
        $pendingGrading = $assignment->submissions()->doesntHave('grade')->count();

        return view('submissions.index', compact(
            'assignment',
            'enrolledStudents',
            'submissions',
            'totalEnrolled',
            'submittedCount',
            'pendingGrading',
            'perPage',
            'allowedPerPage'
        ));
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

        $submission->load([
            'assignment.course',
            'assignment.rubric.criteria.levels',
            'student',
            'grade.grader',
            'rubricAssessments.criterion',
            'rubricAssessments.level',
        ]);

        // Navigasi cepat untuk Dosen/Admin saat memeriksa berkas secara berurutan (looping penilaian)
        $totalSubmissions = 0;
        $currentIndex = 0;
        $prevSubmission = null;
        $nextSubmission = null;

        if (auth()->user()?->role === 'dosen' || auth()->user()?->role === 'admin') {
            $allSubmissions = $submission->assignment->submissions()
                ->with('student')
                ->orderBy('id', 'asc')
                ->get();

            $totalSubmissions = $allSubmissions->count();
            $pos = $allSubmissions->search(fn($item) => $item->id === $submission->id);
            if ($pos !== false) {
                $currentIndex = $pos + 1;
                $prevSubmission = $allSubmissions->get($pos - 1);
                $nextSubmission = $allSubmissions->get($pos + 1);
            }
        }

        return view('submissions.show', compact('submission', 'prevSubmission', 'nextSubmission', 'totalSubmissions', 'currentIndex'));
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
     * Mendukung penilaian manual atau penilaian otomatis terhitung via Rubrik Bertingkat.
     */
    public function grade(Request $request, Submission $submission)
    {
        Gate::authorize('create', [Grade::class, $submission]);

        $maxScore = $submission->assignment->max_score ?? 100;
        $rubric = $submission->assignment->rubric()->with(['criteria.levels'])->first();

        // 1. Jalur Penilaian Otomatis via Rubrik Bertingkat (jika ada rubrik dan data level dikirim)
        if ($rubric && $request->has('rubric_levels')) {
            $validated = $request->validate([
                'rubric_levels'               => ['required', 'array'],
                'rubric_levels.*'             => ['required', 'integer'],
                'rubric_criterion_feedback'   => ['nullable', 'array'],
                'rubric_criterion_feedback.*' => ['nullable', 'string', 'max:500'],
                'feedback'                    => ['nullable', 'string', 'max:1000'],
            ], [
                'rubric_levels.required' => 'Seluruh kriteria rubrik wajib dinilai.',
            ]);

            $totalScore = 0.0;
            $assessmentsToSave = [];

            foreach ($rubric->criteria as $criterion) {
                $levelId = $validated['rubric_levels'][$criterion->id] ?? null;
                $level = $criterion->levels->firstWhere('id', $levelId);

                if (! $level) {
                    return back()
                        ->withErrors(["rubric_levels.{$criterion->id}" => "Tingkatan untuk kriteria '{$criterion->title}' wajib dipilih."])
                        ->withInput();
                }

                // Kalkulasi matematis: (bobot / 100) * (level_percentage / 100) * max_score
                $criterionScore = ((float) $criterion->weight / 100.0) * ((float) $level->score_percentage / 100.0) * (float) $maxScore;
                $totalScore += $criterionScore;

                $assessmentsToSave[] = [
                    'criterion_id' => $criterion->id,
                    'level_id'     => $level->id,
                    'score'        => round($criterionScore, 2),
                    'feedback'     => $validated['rubric_criterion_feedback'][$criterion->id] ?? null,
                ];
            }

            $finalScore = min((float) $maxScore, round($totalScore, 2));

            \Illuminate\Support\Facades\DB::transaction(function () use ($submission, $assessmentsToSave, $finalScore, $validated) {
                foreach ($assessmentsToSave as $item) {
                    $submission->rubricAssessments()->updateOrCreate(
                        ['rubric_criterion_id' => $item['criterion_id']],
                        [
                            'rubric_level_id' => $item['level_id'],
                            'score_awarded'   => $item['score'],
                            'feedback'        => $item['feedback'],
                        ]
                    );
                }

                $submission->grade()->updateOrCreate(
                    ['submission_id' => $submission->id],
                    [
                        'score'     => $finalScore,
                        'feedback'  => $validated['feedback'] ?? null,
                        'graded_by' => auth()->id(),
                        'graded_at' => now(),
                    ]
                );
            });

            event(new \App\Events\SubmissionGraded($submission));

            return redirect()->route('submissions.show', $submission)
                ->with('success', "Nilai berhasil dihitung otomatis dari rubrik ({$finalScore}/{$maxScore}) dan disimpan.");
        }

        // 2. Jalur Penilaian Standar (Kompatibilitas Penuh / Tanpa Rubrik)
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
        $submission->loadMissing(['assignment.course', 'grade']);

        // Pengecekan izin wajib dijalankan di baris awal sebelum memeriksa ada/tidaknya nilai
        Gate::authorize('update', $submission->assignment->course);

        if ($submission->grade) {
            Gate::authorize('delete', $submission->grade);

            \Illuminate\Support\Facades\DB::transaction(function () use ($submission) {
                $submission->rubricAssessments()->delete();
                $submission->grade->delete();
            });
        }

        return redirect()->route('submissions.show', $submission)->with('success', 'Nilai pengumpulan tugas berhasil dihapus.');
    }
}
