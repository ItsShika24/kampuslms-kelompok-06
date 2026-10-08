<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Menentukan apakah pengguna boleh melihat daftar seluruh user.
     * Hanya Administrator.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna boleh melihat profil pengguna tertentu.
     * Boleh jika Admin atau melihat profil miliknya sendiri.
     */
    public function view(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->id === $model->id;
    }

    /**
     * Menentukan apakah pengguna boleh membuat/mendaftarkan akun pengguna baru.
     * Hanya Administrator (sesuai target Minggu 7 BUILD: register khusus admin).
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna boleh mengubah data profil pengguna.
     * Admin boleh mengubah siapa saja; Pengguna biasa hanya profil dirinya sendiri.
     */
    public function update(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->id === $model->id;
    }

    /**
     * Menentukan apakah pengguna boleh menghapus akun pengguna.
     * Hanya Admin dan tidak boleh menghapus dirinya sendiri.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role === 'admin' && $user->id !== $model->id;
    }
}
