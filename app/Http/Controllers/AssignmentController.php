<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    /**
     * Menampilkan daftar tugas untuk mata kuliah tertentu.
     */
    public function index(Course $course)
    {
        Gate::authorize('view', $course);

        $query = $course->assignments()
            ->with(['course', 'submissions'])
            ->withCount('submissions')
            ->latest();

        if (auth()->user()?->role === 'mahasiswa') {
            $query->where('status', 'published');
        }

        $assignments = $query->paginate(10);

        return view('assignments.index', compact('course', 'assignments'));
    }

    /**
     * Menampilkan formulir penambahan tugas baru.
     */
    public function create(Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        return view('assignments.create', compact('course'));
    }

    /**
     * Menyimpan tugas baru ke database.
     */
    public function store(Request $request, Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at'       => ['required', 'date'],
            'max_score'    => ['required', 'integer', 'between:1,100'],
            'allow_late'   => ['boolean'],
            'status'       => ['required', 'in:draft,published,closed'],
        ]);

        $validated['created_by'] = auth()->id() ?? $course->lecturer_id;
        $validated['instructions'] = $validated['instructions'] ?? '';
        $assignment = $course->assignments()->create($validated);

        if ($assignment->status === 'published') {
            \App\Events\AssignmentPublished::dispatch($assignment);
        }

        return redirect()->route('courses.show', $course)->with('success', 'Tugas perkuliahan berhasil dibuat.');
    }

    /**
     * Menampilkan detail satu tugas (shallow route).
     */
    public function show(Assignment $assignment)
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'submissions.student', 'submissions.grade']);

        return view('assignments.show', compact('assignment'));
    }

    /**
     * Menampilkan form edit tugas.
     */
    public function edit(Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        return view('assignments.edit', compact('assignment'));
    }

    /**
     * Memperbarui data tugas.
     */
    public function update(Request $request, Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at'       => ['required', 'date'],
            'max_score'    => ['required', 'integer', 'between:1,100'],
            'allow_late'   => ['boolean'],
            'status'       => ['required', 'in:draft,published,closed'],
        ]);

        $validated['instructions'] = $validated['instructions'] ?? '';
        $wasDraft = $assignment->status === 'draft';
        $assignment->update($validated);

        if ($assignment->status === 'published' && $wasDraft) {
            \App\Events\AssignmentPublished::dispatch($assignment);
        }

        return redirect()->route('assignments.show', $assignment)->with('success', 'Data tugas berhasil diperbarui.');
    }

    /**
     * Menghapus tugas.
     */
    public function destroy(Assignment $assignment)
    {
        Gate::authorize('delete', $assignment);

        $course = $assignment->course;
        $assignment->delete();

        return redirect()->route('courses.show', $course)->with('success', 'Tugas berhasil dihapus.');
    }
}
