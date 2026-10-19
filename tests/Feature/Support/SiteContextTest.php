<?php

use App\Support\Settings;
use App\Support\SiteContext;
use Illuminate\Http\Request;

function siteContext(): array
{
    return app(SiteContext::class)->toArray(Request::create('/'));
}

it('only passes links with a safe scheme from the settings to the theme', function (string $value, ?string $expected) {
    $settings = app(Settings::class);
    $settings->put('contact.complaint_url', $value);
    $settings->put('social.facebook', $value);

    $site = siteContext();

    expect($site['contact']['complaint_url'])->toBe($expected)
        ->and($site['social']['facebook'] ?? null)->toBe($expected);
})->with([
    'https' => ['https://www.lapor.go.id', 'https://www.lapor.go.id'],
    'javascript' => ['javascript:alert(1)', null],
    'protocol relative' => ['//evil.test', null],
    'backslash protocol relative' => ['/\evil.test', null],
    'internal path' => ['/pengaduan', '/pengaduan'],
]);

it('resolves logo paths on the public disk and ignores anything else', function () {
    config(['filesystems.disks.s3.url' => 'https://media.example.go.id/moso-public']);
    $settings = app(Settings::class);
    $settings->put('site.logo_pemkot', 'branding/logo-pemkot.webp');
    $settings->put('site.logo_dinas', '../.env');

    $site = siteContext();

    expect($site['logos']['pemkot'])->toBe('https://media.example.go.id/moso-public/branding/logo-pemkot.webp')
        ->and($site['logos']['dinas'])->toBeNull();
});
