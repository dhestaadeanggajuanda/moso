<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempoten: aman dijalankan ulang setelah daftar permission berubah.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (RoleName::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        foreach (PermissionName::defaultsByRole() as $role => $permissions) {
            Role::findByName($role, 'web')->syncPermissions(
                array_map(fn (PermissionName $permission) => $permission->value, $permissions),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
