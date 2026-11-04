<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GradeController extends Controller
{
    /**
     * Menampilkan rekap nilai per mata kuliah (untuk Dosen pengampu dan Admin).
     * Matriks nilai seluruh mahasiswa untuk setiap tugas pada mata kuliah tersebut.
     */
    public function courseGrades(Course $course): View
    {
        Gate::authorize('update', $course);

        $assignments = $course->assignments()->orderBy('due_at')->get();
        $students = $course->students()->orderBy('name')->get();

        // Ambil data pengumpulan dan nilai
        $submissions = Submission::whereIn('assignment_id', $assignments->pluck('id'))
            ->with('grade')
            ->get()
            ->groupBy(function ($submission) {
                return $submission->user_id . '_' . $submission->assignment_id;
            });

        return view('grades.course', compact('course', 'assignments', 'students', 'submissions'));
    }

    /**
     * Menampilkan rekap nilai per mahasiswa (untuk mahasiswa melihat seluruh nilainya).
     */
    public function studentGrades(): View
    {
        $user = auth()->user();

        // Mahasiswa melihat nilai di setiap mata kuliah yang diikutinya
        $courses = $user->courses()->with([
            'lecturer',
            'assignments' => function ($q) {
                $q->where('status', '!=', 'draft')->orderBy('due_at');
            },
            'assignments.submissions' => function ($q) use ($user) {
                $q->where('user_id', $user->id)->with('grade');
            },
        ])->get();

        return view('grades.student', compact('courses', 'user'));
    }
}
