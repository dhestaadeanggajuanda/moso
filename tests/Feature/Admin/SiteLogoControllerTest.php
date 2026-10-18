<?php

use App\Enums\RoleName;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

it('stores an uploaded logo as a re-encoded webp on the public disk', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->post(route('admin.settings.logos.update', 'pemkot'), [
            'logo' => UploadedFile::fake()->image('lambang-singkawang.png', 400, 480),
        ])
        ->assertRedirect(route('admin.settings.index'))
        ->assertSessionHasNoErrors();

    $path = app(Settings::class)->get('site.logo_pemkot');

    expect($path)->toMatch('#^branding/\d{4}/\d{2}/[0-9a-f-]{36}\.webp$#');
    Storage::disk('s3')->assertExists($path);
    expect(getimagesizefromstring(Storage::disk('s3')->get($path))['mime'])->toBe('image/webp');
});

it('accepts small horizontal logos', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->post(route('admin.settings.logos.update', 'dinas'), [
            'logo' => UploadedFile::fake()->image('logo-dinas.png', 300, 80),
        ])
        ->assertSessionHasNoErrors();

    expect(app(Settings::class)->get('site.logo_dinas'))->toStartWith('branding/');
});

it('deletes the previously uploaded logo but never a manually placed file', function () {
    $settings = app(Settings::class);
    $admin = userWithRole(RoleName::SuperAdmin);

    $this->actingAs($admin)->post(route('admin.settings.logos.update', 'dinas'), [
        'logo' => UploadedFile::fake()->image('lama.png', 300, 300),
    ]);
    $previous = $settings->get('site.logo_dinas');

    $this->actingAs($admin)->post(route('admin.settings.logos.update', 'dinas'), [
        'logo' => UploadedFile::fake()->image('baru.png', 300, 300),
    ]);

    Storage::disk('s3')->assertMissing($previous);
    Storage::disk('s3')->assertExists($settings->get('site.logo_dinas'));

    Storage::disk('s3')->put('branding/logo-manual.png', 'png');
    $settings->put('site.logo_pemkot', 'branding/logo-manual.png');

    $this->actingAs($admin)->post(route('admin.settings.logos.update', 'pemkot'), [
        'logo' => UploadedFile::fake()->image('baru.png', 300, 300),
    ]);

    Storage::disk('s3')->assertExists('branding/logo-manual.png');
});

it('rejects files that are not safe raster logos', function (UploadedFile $file) {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->post(route('admin.settings.logos.update', 'pemkot'), ['logo' => $file])
        ->assertInvalid(['logo']);

    expect(Storage::disk('s3')->allFiles())->toBe([])
        ->and(app(Settings::class)->get('site.logo_pemkot'))->toBeNull();
})->with([
    'svg' => fn () => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'),
    'pdf' => fn () => fakePdf('logo.pdf'),
    'terlalu kecil' => fn () => UploadedFile::fake()->image('ikon.png', 32, 32),
    'lebih dari 2 MB' => fn () => UploadedFile::fake()->image('besar.png', 800, 800)->size(3000),
]);

it('requires a file', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->post(route('admin.settings.logos.update', 'pemkot'))
        ->assertInvalid(['logo']);
});

it('only knows the pemkot and dinas logo slots', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->post(route('admin.settings.logos.update', 'favicon'), [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ])
        ->assertNotFound();
});

it('forbids every role except super admin from changing logos', function (RoleName $role) {
    $settings = app(Settings::class);
    $settings->put('site.logo_dinas', 'branding/logo-dinas.webp');
    $user = userWithRole($role);

    $this->actingAs($user)
        ->post(route('admin.settings.logos.update', 'dinas'), [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.settings.logos.destroy', 'dinas'))
        ->assertForbidden();

    expect(Storage::disk('s3')->allFiles())->toBe([])
        ->and($settings->get('site.logo_dinas'))->toBe('branding/logo-dinas.webp');
})->with([RoleName::Approver, RoleName::Editor, RoleName::Kontributor]);

it('removes a logo so the header falls back to the neutral emblem', function () {
    $admin = userWithRole(RoleName::SuperAdmin);

    $this->actingAs($admin)->post(route('admin.settings.logos.update', 'pemkot'), [
        'logo' => UploadedFile::fake()->image('lambang.png', 300, 300),
    ]);
    $path = app(Settings::class)->get('site.logo_pemkot');

    $this->actingAs($admin)
        ->delete(route('admin.settings.logos.destroy', 'pemkot'))
        ->assertRedirect(route('admin.settings.index'));

    expect(app(Settings::class)->get('site.logo_pemkot'))->toBeNull();
    Storage::disk('s3')->assertMissing($path);
});
