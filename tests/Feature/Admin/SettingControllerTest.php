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

it('shows logos as upload slots instead of free text settings', function () {
    config(['filesystems.disks.s3.url' => 'https://media.example.go.id/moso-public']);
    $settings = app(Settings::class);
    $settings->put('site.logo_pemkot', 'branding/2026/10/0b6f1f0e-8d1c-4a59-9a39-3f1e6f0b2c11.webp');
    $settings->put('site.logo_dinas', '');
    $settings->put('site.name', 'Portal Dinas');

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->get(route('admin.settings.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('settings', 1)
            ->where('settings.0.key', 'site.name')
            ->where('logos.0.slot', 'pemkot')
            ->where('logos.0.url', 'https://media.example.go.id/moso-public/branding/2026/10/0b6f1f0e-8d1c-4a59-9a39-3f1e6f0b2c11.webp')
            ->where('logos.1.slot', 'dinas')
            ->where('logos.1.url', null));
});

it('refuses to set a logo path through the text settings form', function () {
    $settings = app(Settings::class);
    $settings->put('site.logo_dinas', 'branding/logo-dinas.webp');

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->put(route('admin.settings.update'), [
            'settings' => [['key' => 'site.logo_dinas', 'value' => 'articles/foto-orang.webp']],
        ])
        ->assertInvalid(['settings.0.key' => 'Logo diatur lewat unggahan']);

    expect($settings->get('site.logo_dinas'))->toBe('branding/logo-dinas.webp');
});
