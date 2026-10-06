<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    /**
     * Membuat tugas baru (Hanya untuk Dosen pengampu atau Admin).
     * Mengembalikan status 201 Created.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ], 403));
        }

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'integer', 'between:1,100'],
            'allow_late' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published,closed'],
        ]);

        $course = Course::findOrFail($validated['course_id']);

        if ($user->role !== 'admin' && $course->lecturer_id !== $user->id) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ], 403));
        }

        $validated['created_by'] = $user->id;
        $validated['allow_late'] = $validated['allow_late'] ?? false;
        $validated['instructions'] = $validated['instructions'] ?? '';

        $assignment = Assignment::create($validated);
        $assignment->load(['course', 'creator']);

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Memperbarui data tugas (Hanya Dosen pemilik atau Admin).
     */
    public function update(Request $request, Assignment $assignment): AssignmentResource
    {
        $this->authorizeDosenAssignmentOwner($assignment, $request->user());

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'due_at' => ['sometimes', 'required', 'date'],
            'max_score' => ['sometimes', 'required', 'integer', 'between:1,100'],
            'allow_late' => ['nullable', 'boolean'],
            'status' => ['sometimes', 'required', 'in:draft,published,closed'],
        ]);

        $assignment->update($validated);
        $assignment->load(['course', 'creator']);

        return new AssignmentResource($assignment);
    }

    /**
     * Menghapus tugas (Hanya Dosen pemilik atau Admin).
     * Mengembalikan status 204 No Content.
     */
    public function destroy(Request $request, Assignment $assignment): Response
    {
        $this->authorizeDosenAssignmentOwner($assignment, $request->user());

        $assignment->delete();

        return response()->noContent();
    }

    /**
     * Menampilkan seluruh pengumpulan tugas (Hanya Dosen pemilik atau Admin).
     */
    public function submissions(Request $request, Assignment $assignment): AnonymousResourceCollection
    {
        $this->authorizeDosenAssignmentOwner($assignment, $request->user());

        $submissions = $assignment->submissions()
            ->with(['student', 'grade.grader'])
            ->latest('submitted_at')
            ->paginate(15);

        return SubmissionResource::collection($submissions);
    }

    /**
     * Mahasiswa mengumpulkan tugas (multipart file upload).
     * Mengembalikan status 201 Created.
     */
    public function storeSubmission(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'mahasiswa') {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ], 403));
        }

        $isEnrolled = $assignment->course->students()->where('users.id', $user->id)->exists();
        if (! $isEnrolled) {
            abort(response()->json([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ], 403));
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'note' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $filePath = $file->store('submissions/' . $assignment->id, 'local');
        $isLate = now()->greaterThan($assignment->due_at);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'file_path' => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'note' => $request->note,
            'is_late' => $isLate,
            'submitted_at' => now(),
        ]);

        $submission->load(['student', 'assignment']);

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Verifikasi bahwa pengguna adalah Dosen pengampu tugas atau Admin.
     */
    private function authorizeDosenAssignmentOwner(Assignment $assignment, $user): void
    {
        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'dosen' && ($assignment->course->lecturer_id === $user->id || $assignment->created_by === $user->id)) {
            return;
        }

        abort(response()->json([
            'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
        ], 403));
    }
}
