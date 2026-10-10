<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar seluruh pengguna.
     * Aturan: Hanya Admin yang dapat melihat direktori seluruh pengguna.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna dapat melihat profil pengguna tertentu.
     * Aturan: Admin dapat melihat siapapun, pengguna non-admin hanya dapat melihat profilnya sendiri.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $model->id;
    }

    /**
     * Menentukan apakah pengguna dapat membuat/mendaftarkan akun baru.
     * Aturan: Hanya Admin yang berhak mendaftarkan pengguna ke sistem.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui data akun.
     * Aturan: Admin dapat memperbarui siapa saja, pengguna biasa hanya profilnya sendiri.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $model->id;
    }

    /**
     * Menentukan apakah pengguna dapat mengubah peran (role) akun.
     * Aturan Krusial: Hanya Admin yang dapat menetapkan atau mengubah role akun.
     */
    public function updateRole(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna dapat menghapus akun.
     * Aturan: Hanya Admin yang boleh menghapus, dan dilarang menghapus akunnya sendiri.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role === 'admin' && $user->id !== $model->id;
    }
}
