<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar tugas.
     * Penyaringan aktual dibatasi di level query.
     */
    public function viewAny(User $user, ?Course $course = null): bool
    {
        if (! $course) {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $course->lecturer_id;
        }

        if ($user->role === 'mahasiswa') {
            return $course->students()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail tugas.
     * Aturan:
     * - Admin dapat melihat semua tugas.
     * - Dosen pengampu mata kuliah terkait dapat melihat tugas.
     * - Mahasiswa yang terdaftar pada mata kuliah terkait dapat melihat tugas.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $assignment->course;

        if ($user->role === 'dosen') {
            return $user->id === $course->lecturer_id;
        }

        if ($user->role === 'mahasiswa') {
            return $assignment->status !== 'draft'
                && $course->students()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat membuat tugas baru.
     * Aturan:
     * - Admin dapat membuat tugas.
     * - Dosen hanya dapat membuat tugas untuk mata kuliah yang diampunya sendiri.
     * - Mahasiswa dilarang membuat tugas.
     */
    public function create(User $user, ?Course $course = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $course ? $user->id === $course->lecturer_id : true;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui tugas.
     * Aturan:
     * - Admin dapat memperbarui tugas apapun.
     * - Dosen pengampu mata kuliah terkait dapat memperbarui tugas.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $assignment->course->lecturer_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus tugas.
     * Aturan:
     * - Admin dapat menghapus tugas apapun.
     * - Dosen pengampu mata kuliah terkait dapat menghapus tugas.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $assignment->course->lecturer_id;
        }

        return false;
    }
}
