<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    /**
     * Menampilkan daftar mata kuliah dengan penyaringan di level query.
     * Mahasiswa hanya melihat mata kuliah yang diikutinya.
     * Dosen hanya melihat mata kuliah yang diampunya.
     * Admin melihat seluruh mata kuliah.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = match ($user?->role) {
            'admin'     => Course::query(),
            'dosen'     => $user->taughtCourses(),
            'mahasiswa' => $user->courses(),
            default     => Course::where('status', 'active'),
        };

        $courses = $query
            ->with('lecturer')
            ->when($request->filled('q'), fn ($q) =>
                $q->where(fn ($sub) =>
                    $sub->where('name', 'like', '%' . $request->q . '%')
                        ->orWhere('code', 'like', '%' . $request->q . '%')
                ))
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    /**
     * Menampilkan formulir penambahan mata kuliah baru.
     */
    public function create()
    {
        Gate::authorize('create', Course::class);

        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.create', compact('lecturers'));
    }

    /**
     * Menyimpan data mata kuliah baru ke database.
     */
    public function store(StoreCourseRequest $request)
    {
        Gate::authorize('create', Course::class);

        Course::create($request->validated());

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail satu mata kuliah beserta relasinya.
     */
    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        $user = auth()->user();

        $course->load([
            'lecturer',
            'students' => fn ($q) => $q->orderBy('name'),
            'materials',
            'assignments' => function ($query) use ($user) {
                if ($user?->role === 'mahasiswa') {
                    $query->where('status', 'published');
                }
                $query->latest();
            },
            'assignments.submissions',
        ]);

        $availableStudents = collect();
        if ($user && $user->can('enroll', $course)) {
            $enrolledIds = $course->students->pluck('id');
            $availableStudents = User::where('role', 'mahasiswa')
                ->whereNotIn('id', $enrolledIds)
                ->orderBy('name')
                ->get();
        }

        return view('courses.show', compact('course', 'availableStudents'));
    }

    /**
     * Menampilkan formulir edit mata kuliah.
     */
    public function edit(Course $course)
    {
        Gate::authorize('update', $course);

        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    /**
     * Memperbarui data mata kuliah di database.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        Gate::authorize('update', $course);

        $data = $request->validated();
        if (auth()->user()?->role !== 'admin') {
            $data['lecturer_id'] = $course->lecturer_id;
        }

        $course->update($data);

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    /**
     * Menghapus data mata kuliah dari database.
     */
    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);

        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil dihapus.');
    }

    /**
     * Mendaftarkan mahasiswa ke mata kuliah (Admin atau Dosen Pengampu).
     */
    public function enroll(Request $request, Course $course)
    {
        Gate::authorize('enroll', $course);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
        ], [
            'student_id.required' => 'Silakan pilih mahasiswa yang ingin didaftarkan.',
            'student_id.exists'   => 'Mahasiswa yang dipilih tidak valid.',
        ]);

        $student = User::where('id', $validated['student_id'])->where('role', 'mahasiswa')->firstOrFail();

        // Cegah pendaftaran ganda jika mahasiswa sudah terdaftar
        if ($course->students()->where('user_id', $student->id)->exists()) {
            return back()->with('info', "Mahasiswa {$student->name} sudah terdaftar pada kelas ini.");
        }

        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        return back()->with('success', "Mahasiswa {$student->name} ({$student->nim_nip}) berhasil didaftarkan ke kelas ini.");
    }

    /**
     * Mengeluarkan mahasiswa dari mata kuliah (Admin atau Dosen Pengampu).
     */
    public function unenroll(Course $course, User $student)
    {
        Gate::authorize('unenroll', $course);

        $course->students()->detach($student->id);

        return back()->with('success', "Mahasiswa {$student->name} ({$student->nim_nip}) berhasil dikeluarkan dari kelas ini.");
    }
}
