<?php

use App\Enums\RoleName;
use App\Models\Setting;
use App\Support\Settings;
use Inertia\Testing\AssertableInertia as Assert;

it('forbids every role except super admin', function (RoleName $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.settings.index'))
        ->assertForbidden();
})->with([RoleName::Approver, RoleName::Editor, RoleName::Kontributor]);

it('never sends encrypted values to the browser', function () {
    app(Settings::class)->put('integration.token', 'rahasia-sekali', encrypted: true);
    app(Settings::class)->put('site.name', 'Portal Dinas');

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->get(route('admin.settings.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/settings/Index')
            ->where('settings.0.key', 'integration.token')
            ->where('settings.0.value', null)
            ->where('settings.0.has_value', true)
            ->where('settings.1.value', 'Portal Dinas'))
        ->assertDontSee('rahasia-sekali');
});

it('updates values and keeps an encrypted value when submitted blank', function () {
    $settings = app(Settings::class);
    $settings->put('integration.token', 'rahasia-lama', encrypted: true);
    $settings->put('site.name', 'Nama lama');

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->put(route('admin.settings.update'), [
            'settings' => [
                ['key' => 'integration.token', 'value' => '', 'is_encrypted' => 1],
                ['key' => 'site.name', 'value' => 'Nama baru', 'is_encrypted' => 0],
            ],
        ])
        ->assertRedirect(route('admin.settings.index'));

    expect($settings->get('site.name'))->toBe('Nama baru')
        ->and($settings->get('integration.token'))->toBe('rahasia-lama');
});

it('rejects keys outside the allowed format', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->put(route('admin.settings.update'), [
            'settings' => [['key' => '../APP_KEY', 'value' => 'x']],
        ])
        ->assertInvalid(['settings.0.key']);

    expect(Setting::query()->count())->toBe(0);
});
