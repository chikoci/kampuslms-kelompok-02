<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    /**
     * Menampilkan daftar mata kuliah sesuai peran:
     * Dosen: mata kuliah yang diajar.
     * Mahasiswa: mata kuliah yang diikuti.
     * Admin: seluruh mata kuliah.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Course::with('lecturer')->withCount(['materials', 'assignments', 'students']);

        if ($user->role === 'dosen') {
            $query->where('lecturer_id', $user->id);
        } elseif ($user->role === 'mahasiswa') {
            $query->whereHas('students', fn ($q) => $q->where('user_id', $user->id));
        }

        $courses = $query->latest()->paginate(15);

        return CourseResource::collection($courses);
    }

    /**
     * Menampilkan detail satu mata kuliah beserta jumlah materi dan tugas.
     */
    public function show(Request $request, Course $course): CourseResource
    {
        $this->authorizeCourseAccess($course, $request->user());

        $course->load('lecturer')->loadCount(['materials', 'assignments', 'students']);

        return new CourseResource($course);
    }

    /**
     * Menampilkan daftar materi pada mata kuliah tertentu.
     */
    public function materials(Request $request, Course $course): AnonymousResourceCollection
    {
        $this->authorizeCourseAccess($course, $request->user());

        $materials = $course->materials()
            ->with('uploader')
            ->latest()
            ->paginate(15);

        return MaterialResource::collection($materials);
    }

    /**
     * Menampilkan daftar tugas pada mata kuliah tertentu dengan dukungan filter ?status= dan ?page=.
     */
    public function assignments(Request $request, Course $course): AnonymousResourceCollection
    {
        $this->authorizeCourseAccess($course, $request->user());

        $query = $course->assignments()
            ->with('creator')
            ->withCount('submissions');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->latest()->paginate(15);

        return AssignmentResource::collection($assignments);
    }

    /**
     * Verifikasi otorisasi akses mata kuliah berdasarkan keterdaftaran / pengampu.
     */
    private function authorizeCourseAccess(Course $course, $user): void
    {
        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'dosen' && $course->lecturer_id === $user->id) {
            return;
        }

        if ($user->role === 'mahasiswa' && $course->students()->where('users.id', $user->id)->exists()) {
            return;
        }

        abort(response()->json([
            'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
        ], 403));
    }
}
