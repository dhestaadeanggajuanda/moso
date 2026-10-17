<?php

use App\Enums\RoleName;

beforeEach(function () {
    config(['security.two_factor.enforce' => true]);
});

it('sends privileged users without 2fa to the security page', function (RoleName $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.articles.index'))
        ->assertRedirect(route('security.edit'));
})->with([RoleName::SuperAdmin, RoleName::Approver]);

it('lets privileged users with confirmed 2fa through', function () {
    $approver = userWithRole(RoleName::Approver);
    $approver->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->actingAs($approver)
        ->get(route('admin.articles.index'))
        ->assertOk();
});

it('does not require 2fa from editors and kontributors', function (RoleName $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.articles.index'))
        ->assertOk();
})->with([RoleName::Editor, RoleName::Kontributor]);
