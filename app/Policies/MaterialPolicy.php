<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar materi pada suatu mata kuliah.
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
     * Menentukan apakah pengguna dapat melihat detail materi.
     * Aturan:
     * - Admin dapat melihat seluruh materi.
     * - Dosen pengampu mata kuliah terkait dapat melihat materi.
     * - Mahasiswa yang terdaftar pada mata kuliah terkait dapat melihat materi.
     */
    public function view(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $material->course;

        if ($user->role === 'dosen') {
            return $user->id === $course->lecturer_id;
        }

        if ($user->role === 'mahasiswa') {
            return $course->students()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menambahkan materi baru.
     * Aturan:
     * - Admin dapat menambahkan materi pada mata kuliah apapun.
     * - Dosen hanya dapat menambahkan materi pada mata kuliah yang diampunya sendiri.
     * - Mahasiswa dilarang keras menambahkan materi.
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
     * Menentukan apakah pengguna dapat memperbarui materi.
     * Aturan:
     * - Admin dapat memperbarui materi apapun.
     * - Dosen pengampu mata kuliah terkait dapat memperbarui materi tersebut.
     */
    public function update(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $material->course->lecturer_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus materi.
     * Aturan:
     * - Admin dapat menghapus materi apapun.
     * - Dosen pengampu mata kuliah terkait dapat menghapus materi tersebut.
     */
    public function delete(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return $user->id === $material->course->lecturer_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna berhak mengunduh berkas materi.
     */
    public function download(User $user, Material $material): bool
    {
        return $this->view($user, $material);
    }
}
