<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama sesuai peran pengguna yang sedang terautentikasi.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role === 'admin') {
            return $this->adminDashboard();
        }

        if ($user->role === 'dosen') {
            return $this->dosenDashboard($user);
        }

        return $this->mahasiswaDashboard($user);
    }

    /**
     * Data dashboard untuk Administrator sistem.
     */
    protected function adminDashboard()
    {
        $metrics = Cache::remember('dashboard.admin.metrics', now()->addMinutes(10), function () {
            return [
                'users_count' => User::count(),
                'students_count' => User::where('role', 'mahasiswa')->count(),
                'lecturers_count' => User::where('role', 'dosen')->count(),
                'courses_count' => Course::count(),
                'materials_count' => Material::count(),
                'assignments_count' => Assignment::count(),
                'submissions_count' => Submission::count(),
            ];
        });

        $recentUsers = User::latest()->take(5)->get();
        $recentCourses = Course::with('lecturer')->latest()->take(5)->get();

        return view('dashboard', [
            'role' => 'admin',
            'metrics' => $metrics,
            'recentUsers' => $recentUsers,
            'recentCourses' => $recentCourses,
        ]);
    }

    /**
     * Data dashboard untuk Dosen Pengampu.
     */
    protected function dosenDashboard(User $user)
    {
        $myCourses = $user->taughtCourses()
            ->withCount(['materials', 'assignments', 'students'])
            ->latest()
            ->get();

        $courseIds = $myCourses->pluck('id');

        $pendingSubmissions = Submission::whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->doesntHave('grade')
            ->with(['assignment.course', 'student'])
            ->latest()
            ->get();

        $metrics = Cache::remember("dashboard.dosen.{$user->id}.metrics", now()->addMinutes(10), function () use ($myCourses, $courseIds, $pendingSubmissions) {
            return [
                'courses_count' => $myCourses->count(),
                'total_students' => $myCourses->sum('students_count'),
                'total_assignments' => Assignment::whereIn('course_id', $courseIds)->count(),
                'pending_grading_count' => $pendingSubmissions->count(),
            ];
        });

        return view('dashboard', [
            'role' => 'dosen',
            'metrics' => $metrics,
            'myCourses' => $myCourses,
            'pendingSubmissions' => $pendingSubmissions,
        ]);
    }

    /**
     * Data dashboard untuk Mahasiswa.
     */
    protected function mahasiswaDashboard(User $user)
    {
        $enrolledCourses = $user->courses()
            ->with('lecturer')
            ->withCount([
                'materials',
                'assignments' => fn ($q) => $q->where('status', 'published'),
            ])
            ->get();

        $courseIds = $enrolledCourses->pluck('id');

        // Tugas mendatang dari mata kuliah yang diikuti
        $upcomingAssignments = Assignment::whereIn('course_id', $courseIds)
            ->where('status', 'published')
            ->where('due_at', '>=', now())
            ->with('course')
            ->orderBy('due_at')
            ->take(6)
            ->get();

        // Pengumpulan tugas terbaru milik mahasiswa sendiri
        $myRecentSubmissions = $user->submissions()
            ->with(['assignment.course', 'grade'])
            ->latest()
            ->take(6)
            ->get();

        $metrics = [
            'courses_count' => $enrolledCourses->count(),
            'upcoming_count' => $upcomingAssignments->count(),
            'submitted_count' => $user->submissions()->count(),
            'graded_count' => $user->submissions()->has('grade')->count(),
        ];

        return view('dashboard', [
            'role' => 'mahasiswa',
            'metrics' => $metrics,
            'enrolledCourses' => $enrolledCourses,
            'upcomingAssignments' => $upcomingAssignments,
            'myRecentSubmissions' => $myRecentSubmissions,
        ]);
    }
}
