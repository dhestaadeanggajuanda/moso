<?php

namespace App\Policies\Concerns;

use App\Enums\PermissionName;
use App\Models\User;

/**
 * Untuk modul yang seluruh aksinya dikendalikan oleh satu permission "manage".
 * Kelas pemakai cukup mendefinisikan permission().
 */
trait GrantsByPermission
{
    abstract protected function permission(): PermissionName;

    public function viewAny(User $user): bool
    {
        return $user->can($this->permission());
    }

    public function view(User $user): bool
    {
        return $user->can($this->permission());
    }

    public function create(User $user): bool
    {
        return $user->can($this->permission());
    }

    public function update(User $user): bool
    {
        return $user->can($this->permission());
    }

    public function delete(User $user): bool
    {
        return $user->can($this->permission());
    }
}
