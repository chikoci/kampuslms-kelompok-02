<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    /**
     * Menampilkan daftar seluruh pengumpulan tugas (untuk Dosen/Admin).
     */
    public function index(Assignment $assignment)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $assignment->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda bukan dosen pengampu mata kuliah ini.'
            );
        }

        $submissions = $assignment->submissions()->with(['student', 'grade'])->latest()->paginate(15);

        return view('submissions.index', compact('assignment', 'submissions'));
    }

    /**
     * Menyimpan pengumpulan tugas baru oleh mahasiswa.
     */
    public function store(Request $request, Assignment $assignment)
    {
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
                'file_path' => $filePath,
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'note' => $validated['note'] ?? null,
                'submitted_at' => now(),
                'is_late' => $isLate,
            ]
        );

        return redirect()->route('assignments.show', $assignment)->with('success', 'Tugas berhasil dikumpulkan.');
    }

    /**
     * Menampilkan detail pengumpulan tugas (DENGAN PROTEKSI IDOR KETAT).
     */
    public function show(Submission $submission)
    {
        // Proteksi IDOR Sementara (Sesuai Prompt Pack C):
        // Boleh dilihat jika:
        // 1. Pemilik tugas itu sendiri (mahasiswa yang login)
        // 2. Dosen pengampu mata kuliah bersangkutan
        // 3. Admin sistem
        if (auth()->check()) {
            abort_unless(
                $submission->user_id === auth()->id()
                    || auth()->user()->role === 'admin'
                    || $submission->assignment->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki izin untuk melihat pengumpulan tugas ini.'
            );
        }

        $submission->load(['assignment.course', 'student', 'grade']);

        return view('submissions.show', compact('submission'));
    }

    /**
     * Mengunduh berkas submission (DENGAN PROTEKSI IDOR).
     */
    public function download(Submission $submission)
    {
        if (auth()->check()) {
            abort_unless(
                $submission->user_id === auth()->id()
                    || auth()->user()->role === 'admin'
                    || $submission->assignment->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki izin untuk mengunduh berkas ini.'
            );
        }

        if (! Storage::exists($submission->file_path)) {
            return back()->with('error', 'Berkas submission tidak ditemukan pada penyimpanan server.');
        }

        return Storage::download($submission->file_path, $submission->original_name);
    }
}
