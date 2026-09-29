<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * Menampilkan daftar tugas untuk mata kuliah tertentu.
     */
    public function index(Course $course)
    {
        $assignments = $course->assignments()->latest()->paginate(10);

        return view('assignments.index', compact('course', 'assignments'));
    }

    /**
     * Menampilkan formulir penambahan tugas baru.
     */
    public function create(Course $course)
    {
        // Proteksi IDOR: Hanya Admin atau Dosen pengampu yang boleh menambah tugas
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda bukan dosen pengampu mata kuliah ini.'
            );
        }

        return view('assignments.create', compact('course'));
    }

    /**
     * Menyimpan tugas baru ke database.
     */
    public function store(Request $request, Course $course)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda bukan dosen pengampu mata kuliah ini.'
            );
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'integer', 'between:1,100'],
            'allow_late' => ['boolean'],
            'status' => ['required', 'in:draft,published,closed'],
        ]);

        $validated['created_by'] = auth()->id() ?? $course->lecturer_id;
        $course->assignments()->create($validated);

        return redirect()->route('courses.show', $course)->with('success', 'Tugas perkuliahan berhasil dibuat.');
    }

    /**
     * Menampilkan detail satu tugas (shallow route).
     */
    public function show(Assignment $assignment)
    {
        $assignment->load(['course', 'submissions.student']);

        return view('assignments.show', compact('assignment'));
    }

    /**
     * Menampilkan form edit tugas.
     */
    public function edit(Assignment $assignment)
    {
        // Proteksi IDOR: Hanya Admin atau Dosen pengampu yang boleh mengedit tugas
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $assignment->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki hak untuk mengedit tugas ini.'
            );
        }

        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Memperbarui data tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $assignment->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki hak untuk memperbarui tugas ini.'
            );
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'integer', 'between:1,100'],
            'allow_late' => ['boolean'],
            'status' => ['required', 'in:draft,published,closed'],
        ]);

        $assignment->update($validated);

        return redirect()->route('assignments.show', $assignment)->with('success', 'Data tugas berhasil diperbarui.');
    }

    /**
     * Menghapus tugas.
     */
    public function destroy(Assignment $assignment)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $assignment->course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki hak untuk menghapus tugas ini.'
            );
        }

        $course = $assignment->course;
        $assignment->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Tugas berhasil dihapus.');
    }
}
