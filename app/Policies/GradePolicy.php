<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;

class GradePolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar nilai.
     * Dibatasi lebih lanjut di query controller.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail nilai tertentu.
     * Aturan:
     * - Admin dapat melihat seluruh nilai.
     * - Dosen pengampu mata kuliah terkait dapat melihat nilai.
     * - Mahasiswa HANYA dapat melihat nilai untuk pengumpulan tugas miliknya sendiri.
     */
    public function view(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $lecturerId = $grade->submission->assignment->course->lecturer_id;
        if ($user->role === 'dosen') {
            return $user->id === $lecturerId;
        }

        if ($user->role === 'mahasiswa') {
            return $user->id === $grade->submission->user_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat memberikan nilai baru.
     * Sesuai Bagian 3 Spesifikasi Proyek:
     * - Admin: Tidak memberi nilai (-).
     * - Dosen: Berhak memberi nilai hanya pada mata kuliah yang diampunya sendiri.
     * - Mahasiswa: Dilarang keras (-).
     */
    public function create(User $user, ?Submission $submission = null): bool
    {
        if ($user->role !== 'dosen') {
            return false;
        }

        if (! $submission) {
            return true;
        }

        return $user->id === $submission->assignment->course->lecturer_id;
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui nilai.
     * Aturan: Dosen pengampu mata kuliah terkait yang berhak memperbarui nilai.
     */
    public function update(User $user, Grade $grade): bool
    {
        if ($user->role !== 'dosen') {
            return false;
        }

        return $user->id === $grade->submission->assignment->course->lecturer_id;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus nilai.
     */
    public function delete(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $grade->submission->assignment->course->lecturer_id;
        }

        return false;
    }
}
