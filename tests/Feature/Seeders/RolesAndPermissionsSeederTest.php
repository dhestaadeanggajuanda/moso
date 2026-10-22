<?php

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

it('creates the five roles idempotently', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(['Approver', 'Editor', 'Kontributor', 'Penulis', 'Super Admin']);
});

it('keeps settings and user management away from every non super admin role', function (RoleName $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::findByName($role->value)->hasPermissionTo(PermissionName::SettingsManage->value))->toBeFalse()
        ->and(Role::findByName($role->value)->hasPermissionTo(PermissionName::UsersManage->value))->toBeFalse();
})->with([RoleName::Approver, RoleName::Editor, RoleName::Kontributor]);

it('reserves publishing for approvers and reviewing for editors', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::findByName(RoleName::Approver->value)->hasPermissionTo(PermissionName::ArticlesPublish->value))->toBeTrue()
        ->and(Role::findByName(RoleName::Editor->value)->hasPermissionTo(PermissionName::ArticlesPublish->value))->toBeFalse()
        ->and(Role::findByName(RoleName::Editor->value)->hasPermissionTo(PermissionName::ArticlesReview->value))->toBeTrue()
        ->and(Role::findByName(RoleName::Kontributor->value)->hasPermissionTo(PermissionName::ArticlesUpdateAny->value))->toBeFalse();
});
