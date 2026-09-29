<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Menampilkan daftar seluruh mata kuliah dari database.
     */
    public function index(Request $request)
    {
        $courses = Course::query()
            ->with('lecturer')
            ->when($request->filled('q'), fn ($query) =>
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('code', 'like', '%' . $request->q . '%');
                }))
            ->when($request->filled('status'), fn ($query) =>
                $query->where('status', $request->status))
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
        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.create', compact('lecturers'));
    }

    /**
     * Menyimpan data mata kuliah baru ke database.
     */
    public function store(StoreCourseRequest $request)
    {
        Course::create($request->validated());

        $courses = Course::with('lecturer')
            ->latest()
            ->paginate(15);

        return view('courses.index', compact('courses'));
    }

    /**
     * Menampilkan detail satu mata kuliah beserta dosen, materi, dan tugasnya.
     */
    public function show(Course $course)
    {
        $course->load(['lecturer', 'materials', 'assignments']);

        return view('courses.show', compact('course'));
    }

    /**
     * Menampilkan formulir edit mata kuliah.
     */
    public function edit(Course $course)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki izin untuk mengedit mata kuliah ini.'
            );
        }

        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    /**
     * Memperbarui data mata kuliah di database.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin' || $course->lecturer_id === auth()->id(),
                403,
                'Akses ditolak: Anda tidak memiliki izin untuk memperbarui mata kuliah ini.'
            );
        }

        $course->update($request->validated());

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    /**
     * Menghapus data mata kuliah dari database.
     */
    public function destroy(Course $course)
    {
        if (auth()->check()) {
            abort_unless(
                auth()->user()->role === 'admin',
                403,
                'Akses ditolak: Hanya Admin yang diizinkan menghapus mata kuliah.'
            );
        }

        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
