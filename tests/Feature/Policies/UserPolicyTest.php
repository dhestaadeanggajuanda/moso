<?php

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;

test('only super admins may list, create, and edit accounts by default', function (RoleName $role, bool $allowed) {
    $user = userWithRole($role);
    $other = userWithRole(RoleName::Kontributor);

    expect($user->can('viewAny', User::class))->toBe($allowed)
        ->and($user->can('create', User::class))->toBe($allowed)
        ->and($user->can('update', $other))->toBe($allowed)
        ->and($user->can('assignSuperAdmin', User::class))->toBe($allowed);
})->with([
    [RoleName::SuperAdmin, true],
    [RoleName::Approver, false],
    [RoleName::Editor, false],
    [RoleName::Kontributor, false],
]);

test('a super admin may edit another super admin account', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);

    expect($superAdmin->can('update', userWithRole(RoleName::SuperAdmin)))->toBeTrue();
});

test('a non super admin holding users.manage may not touch super admin accounts or grant the role', function () {
    $manager = userWithRole(RoleName::Editor);
    $manager->givePermissionTo(PermissionName::UsersManage->value);

    expect($manager->can('viewAny', User::class))->toBeTrue()
        ->and($manager->can('update', userWithRole(RoleName::Kontributor)))->toBeTrue()
        ->and($manager->can('update', userWithRole(RoleName::SuperAdmin)))->toBeFalse()
        ->and($manager->can('assignSuperAdmin', User::class))->toBeFalse();
});
