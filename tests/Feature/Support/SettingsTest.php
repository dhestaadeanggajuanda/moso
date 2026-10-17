<?php

use App\Models\Setting;
use App\Support\Settings;

it('stores encrypted values as ciphertext and returns plaintext', function () {
    $settings = app(Settings::class);

    $settings->put('integration.token', 'rahasia', encrypted: true);

    expect(Setting::query()->where('key', 'integration.token')->value('value'))->not->toContain('rahasia')
        ->and($settings->get('integration.token'))->toBe('rahasia');
});

it('falls back to the default for a missing key', function () {
    expect(app(Settings::class)->get('tidak.ada', 'bawaan'))->toBe('bawaan');
});

it('returns fresh values after an update', function () {
    $settings = app(Settings::class);

    $settings->put('site.name', 'Lama');
    expect($settings->get('site.name'))->toBe('Lama');

    $settings->put('site.name', 'Baru');
    expect($settings->get('site.name'))->toBe('Baru');
});
