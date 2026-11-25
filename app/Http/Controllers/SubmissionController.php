<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\RubricAssessment;
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
     * Menampilkan daftar semua pengumpulan tugas pada satu mata kuliah (Dosen pengampu / Admin).
     * Mendukung filter per tugas, filter status penilaian, dan agregasi metrik satu kueri SQL.
     */
    public function courseIndex(Request $request, Course $course)
    {
        Gate::authorize('viewSubmissions', $course);

        $assignments = $course->assignments()->select(['id', 'course_id', 'title'])->orderBy('title')->get();
        $assignmentIds = $assignments->pluck('id');

        $query = Submission::whereIn('assignment_id', $assignmentIds)
            ->with(['student', 'assignment', 'grade.grader']);

        // Filter berdasarkan tugas tertentu (pastikan tetap dalam ruang lingkup kelas)
        if ($request->filled('assignment_id')) {
            $query->where('assignment_id', $request->assignment_id);
        }

        // Filter status penilaian (sudah dinilai / belum dinilai)
        if ($request->filled('status')) {
            if ($request->status === 'graded') {
                $query->has('grade');
            } elseif ($request->status === 'ungraded') {
                $query->doesntHave('grade');
            }
        }

        $submissions = $query->latest('submitted_at')->paginate(15)->withQueryString();

        // Hitung total, sudah dinilai, dan belum dinilai sekaligus dalam 1 kueri SQL agregat
        $metrics = Submission::whereIn('submissions.assignment_id', $assignmentIds)
            ->leftJoin('grades', 'grades.submission_id', '=', 'submissions.id')
            ->selectRaw('COUNT(DISTINCT submissions.id) as total_count, COUNT(DISTINCT grades.submission_id) as graded_count')
            ->first();

        $totalSubmissions = (int) ($metrics->total_count ?? 0);
        $gradedCount = (int) ($metrics->graded_count ?? 0);
        $ungradedCount = $totalSubmissions - $gradedCount;

        return view('courses.submissions', compact(
            'course',
            'assignments',
            'submissions',
            'totalSubmissions',
            'ungradedCount',
            'gradedCount'
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

                // Kalkulasi matematis terenkapsulasi via RubricCriterion model
                $criterionScore = $criterion->calculateScore($level, (float) $maxScore);
                $totalScore += $criterionScore;

                $assessmentsToSave[] = [
                    'criterion_id' => $criterion->id,
                    'level_id'     => $level->id,
                    'score'        => $criterionScore,
                    'feedback'     => $validated['rubric_criterion_feedback'][$criterion->id] ?? null,
                ];
            }

            $finalScore = min((float) $maxScore, round($totalScore, 2));

            \Illuminate\Support\Facades\DB::transaction(function () use ($submission, $assessmentsToSave, $finalScore, $validated) {
                $now = now();
                $upsertData = array_map(function ($item) use ($submission, $now) {
                    return [
                        'submission_id'       => $submission->id,
                        'rubric_criterion_id' => $item['criterion_id'],
                        'rubric_level_id'     => $item['level_id'],
                        'score_awarded'       => $item['score'],
                        'feedback'            => $item['feedback'],
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }, $assessmentsToSave);

                RubricAssessment::upsert(
                    $upsertData,
                    ['submission_id', 'rubric_criterion_id'],
                    ['rubric_level_id', 'score_awarded', 'feedback', 'updated_at']
                );

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
