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
     * Matriks nilai mahasiswa untuk setiap tugas pada mata kuliah tersebut dengan paginasi adaptif.
     */
    public function courseGrades(Request $request, Course $course): View
    {
        Gate::authorize('update', $course);

        $course->loadMissing('lecturer');

        $allowedPerPage = [15, 25, 50];
        $perPage = $request->integer('per_page', 15);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $assignments = $course->assignments()->orderBy('due_at')->get();
        $students = $course->students()
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        // Ambil data pengumpulan dan nilai HANYA untuk mahasiswa di halaman aktif
        $submissions = Submission::whereIn('assignment_id', $assignments->pluck('id'))
            ->whereIn('user_id', $students->pluck('id'))
            ->with('grade')
            ->get()
            ->groupBy(fn ($submission) => $submission->user_id . '_' . $submission->assignment_id);

        // Siapkan matriks nilai & rata-rata di controller agar View tetap bersih (MVC)
        $matrix = $students->getCollection()->map(function ($student) use ($assignments, $submissions) {
            $cells = [];
            $totalScore = 0.0;
            $gradedCount = 0;

            foreach ($assignments as $assignment) {
                $sub = $submissions->get($student->id . '_' . $assignment->id)?->first();
                $grade = $sub?->grade;
                $hasGrade = $grade !== null;

                $badgeBg = null;
                $badgeColor = null;

                if ($hasGrade) {
                    $score = (float) $grade->score;
                    $totalScore += $score;
                    $gradedCount++;

                    if ($score >= 80) {
                        $badgeBg = '#dcfce7';
                        $badgeColor = '#166534';
                    } elseif ($score >= 60) {
                        $badgeBg = '#fef9c3';
                        $badgeColor = '#854d0e';
                    } else {
                        $badgeBg = '#fee2e2';
                        $badgeColor = '#991b1b';
                    }
                }

                $cells[$assignment->id] = (object) [
                    'submission'      => $sub,
                    'has_grade'       => $hasGrade,
                    'formatted_score' => $hasGrade ? number_format((float) $grade->score, 1) : null,
                    'badge_bg'        => $badgeBg,
                    'badge_color'     => $badgeColor,
                ];
            }

            return (object) [
                'student' => $student,
                'cells'   => $cells,
                'average' => $gradedCount > 0 ? number_format($totalScore / $gradedCount, 1) : null,
            ];
        });

        return view('grades.course', compact('course', 'assignments', 'students', 'matrix', 'perPage', 'allowedPerPage'));
    }

    /**
     * Menampilkan rekap nilai per mahasiswa (khusus peran mahasiswa).
     */
    public function studentGrades(): View
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'mahasiswa', 403, 'Halaman rekap nilai ini khusus untuk peran mahasiswa.');

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
