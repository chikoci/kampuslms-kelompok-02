<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    /**
     * Menampilkan detail satu submission.
     */
    public function show(Request $request, Submission $submission): SubmissionResource
    {
        $user = $request->user();

        $isOwnerStudent = $user->role === 'mahasiswa' && $submission->user_id === $user->id;
        $isCourseLecturer = $user->role === 'dosen' && $submission->assignment->course->lecturer_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! ($isOwnerStudent || $isCourseLecturer || $isAdmin)) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ], 403));
        }

        $submission->load(['student', 'assignment', 'grade.grader']);

        return new SubmissionResource($submission);
    }

    /**
     * Memberikan atau memperbarui nilai (upsert).
     * Mengembalikan status 201 saat baru dibuat dan 200 saat diperbarui.
     */
    public function grade(Request $request, Submission $submission): JsonResponse
    {
        $user = $request->user();

        $isCourseLecturer = $user->role === 'dosen' && $submission->assignment->course->lecturer_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! ($isCourseLecturer || $isAdmin)) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ], 403));
        }

        $request->validate([
            'score' => ['required', 'numeric', 'between:0,100'],
            'feedback' => ['nullable', 'string'],
        ]);

        $isNew = ! $submission->grade()->exists();

        $grade = Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'score' => $request->score,
                'feedback' => $request->feedback,
                'graded_at' => now(),
                'graded_by' => $user->id,
            ]
        );

        $grade->load('grader');
        $statusCode = $isNew ? 201 : 200;

        return (new GradeResource($grade))
            ->response()
            ->setStatusCode($statusCode);
    }
}
