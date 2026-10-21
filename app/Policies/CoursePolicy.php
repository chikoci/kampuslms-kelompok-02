<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar mata kuliah.
     * Penyaringan aktual (scope) dilakukan di level query controller.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail satu mata kuliah.
     * Aturan:
     * - Admin dapat melihat mata kuliah apapun.
     * - Dosen hanya dapat melihat jika ia adalah dosen pengampu mata kuliah tersebut.
     * - Mahasiswa hanya dapat melihat jika ia terdaftar (enrolled) pada mata kuliah tersebut.
     */
    public function view(User $user, Course $course): bool
    {
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
     * Menentukan apakah pengguna dapat membuat mata kuliah baru.
     * Aturan: Hanya Administrator yang berhak membuat mata kuliah baru.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui data mata kuliah.
     * Aturan:
     * - Admin dapat memperbarui mata kuliah apapun.
     * - Dosen hanya dapat memperbarui mata kuliah yang diampunya sendiri.
     */
    public function update(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $course->lecturer_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus mata kuliah.
     * Aturan: Hanya Administrator yang berhak menghapus mata kuliah.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna dapat mendaftarkan mahasiswa ke mata kuliah.
     * Aturan:
     * - Admin dapat mendaftarkan mahasiswa ke mata kuliah manapun.
     * - Dosen dapat mendaftarkan mahasiswa ke mata kuliah yang diampunya sendiri.
     */
    public function enroll(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $course->lecturer_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat mengeluarkan mahasiswa dari mata kuliah.
     */
    public function unenroll(User $user, Course $course): bool
    {
        return $this->enroll($user, $course);
    }
}
