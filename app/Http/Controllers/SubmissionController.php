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
     * Dosen/Admin: melihat seluruh mahasiswa yang mengumpulkan.
     * Mahasiswa: disaring di query level agar hanya melihat miliknya sendiri.
     */
    public function index(Assignment $assignment)
    {
        Gate::authorize('view', $assignment);

        $user = auth()->user();
        $query = $assignment->submissions()->with(['student', 'grade']);

        if ($user->role === 'mahasiswa') {
            $query->where('user_id', $user->id);
        }

        $submissions = $query->latest()->paginate(15);

        return view('submissions.index', compact('assignment', 'submissions'));
    }

    /**
     * Menyimpan pengumpulan tugas baru oleh mahasiswa.
     */
    public function store(Request $request, Assignment $assignment)
    {
        Gate::authorize('create', [Submission::class, $assignment]);

        $validated = $request->validate([
            'note' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240'], // max 10MB
        ]);

        $filePath = null;
        $originalName = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('submissions');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        }

        $isLate = now()->greaterThan($assignment->due_at);

        $submission = $assignment->submissions()->updateOrCreate(
            ['user_id' => auth()->id()],
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

        return view('submissions.show', compact('submission'));
    }

    /**
     * Mengunduh berkas submission.
     */
    public function download(Submission $submission)
    {
        Gate::authorize('download', $submission);

        if (! $submission->file_path || ! Storage::exists($submission->file_path)) {
            return back()->with('error', 'Berkas submission tidak ditemukan pada penyimpanan server.');
        }

        return Storage::download($submission->file_path, $submission->original_name);
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

        return redirect()->route('submissions.show', $submission)->with('success', 'Nilai berhasil disimpan.');
    }
}
