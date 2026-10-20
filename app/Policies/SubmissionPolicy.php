<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar pengumpulan tugas.
     * Untuk Dosen/Admin: melihat seluruh pengumpulan di tugas tersebut.
     * Untuk Mahasiswa: disaring di controller agar hanya melihat miliknya sendiri.
     */
    public function viewAny(User $user, ?Assignment $assignment = null): bool
    {
        if (! $assignment) {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $assignment->course->lecturer_id;
        }

        if ($user->role === 'mahasiswa') {
            return $assignment->status !== 'draft'
                && $assignment->course->students()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail satu submission.
     * Aturan IDOR Kritis:
     * - Admin dapat melihat seluruh submission.
     * - Dosen pengampu mata kuliah terkait dapat melihat submission.
     * - Mahasiswa HANYA dapat melihat submission miliknya sendiri.
     */
    public function view(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $submission->assignment->course->lecturer_id;
        }

        if ($user->role === 'mahasiswa') {
            return $user->id === $submission->user_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat mengumpulkan tugas.
     * Aturan:
     * - Hanya mahasiswa yang terdaftar pada mata kuliah terkait yang boleh mengumpulkan.
     * - Admin dan Dosen tidak mengumpulkan tugas.
     */
    public function create(User $user, ?Assignment $assignment = null): bool
    {
        if ($user->role !== 'mahasiswa') {
            return false;
        }

        if (! $assignment) {
            return true;
        }

        if ($assignment->status === 'draft') {
            return false;
        }

        return $assignment->course->students()->where('user_id', $user->id)->exists();
    }

    /**
     * Menentukan apakah pengguna dapat mengedit/mengumpulkan ulang tugas.
     * Mahasiswa pemilik dapat memperbarui jika tugas belum ditutup atau terlambat diizinkan.
     */
    public function update(User $user, Submission $submission): bool
    {
        if ($user->role !== 'mahasiswa' || $user->id !== $submission->user_id) {
            return false;
        }

        $assignment = $submission->assignment;

        if ($assignment->allow_late) {
            return true;
        }

        return now()->lessThanOrEqualTo($assignment->due_at);
    }

    /**
     * Menentukan apakah pengguna dapat menghapus pengumpulan tugas.
     */
    public function delete(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'mahasiswa') {
            // Mahasiswa boleh menghapus jika miliknya dan belum dinilai
            return $user->id === $submission->user_id && ! $submission->grade()->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna berhak mengunduh berkas submission.
     */
    public function download(User $user, Submission $submission): bool
    {
        return $this->view($user, $submission);
    }
}
