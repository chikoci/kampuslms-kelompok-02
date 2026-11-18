<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Rubric;
use App\Models\User;

class RubricPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat rubrik suatu tugas.
     * Dosen pengampu, Admin, dan Mahasiswa yang terdaftar pada course berhak melihat rubrik.
     */
    public function view(User $user, Rubric $rubric): bool
    {
        $assignment = $rubric->assignment;
        if (! $assignment) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $course = $assignment->course;
        if (! $course) {
            return false;
        }

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
     * Menentukan apakah pengguna dapat mengelola (membuat/memperbarui) rubrik untuk tugas tertentu.
     * Hanya Dosen pengampu mata kuliah terkait atau Admin yang diizinkan.
     */
    public function manage(User $user, Assignment $assignment): bool
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
     * Menentukan apakah pengguna dapat menghapus rubrik tugas.
     */
    public function delete(User $user, Rubric $rubric): bool
    {
        $assignment = $rubric->assignment;
        if (! $assignment) {
            return false;
        }

        return $this->manage($user, $assignment);
    }
}

