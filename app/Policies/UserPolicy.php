<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

/**
 * Manajemen akun pegawai. Permission users.manage tidak diberikan ke role
 * mana pun, jadi praktisnya hanya Super Admin (lewat Gate::before).
 *
 * Bila kelak permission itu diberikan ke role lain, pemegangnya tetap tidak
 * bisa menyentuh akun Super Admin atau memberi peran Super Admin, agar tidak
 * ada jalan naik hak akses (mis. mengganti email Super Admin lalu reset kata sandi).
 *
 * Aturan "tidak boleh mengubah diri sendiri" ada di UserController dan
 * UpdateUserRequest, karena Super Admin melewati Policy.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::UsersManage);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::UsersManage);
    }

    /**
     * Mengubah data, peran, status aktif, dan tautan SSO akun.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersManage) && ! $model->isSuperAdmin();
    }

    /**
     * Memberi peran Super Admin: hanya Super Admin (Gate::before).
     */
    public function assignSuperAdmin(User $user): bool
    {
        return false;
    }
}
