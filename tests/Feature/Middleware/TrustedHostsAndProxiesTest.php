<?php

use Illuminate\Http\Request;

afterEach(function () {
    // TrustHosts menyimpan pola host secara statis di Symfony Request; jangan bocor ke tes lain.
    Request::setTrustedHosts([]);
});

it('serves only the application host outside local and testing', function () {
    config([
        'app.url' => 'https://portal.example.go.id',
        'security.trusted_hosts' => ['www.portal.example.go.id'],
    ]);
    $this->app['env'] = 'production';

    $this->get('https://portal.example.go.id/login')->assertOk();
    $this->get('https://www.portal.example.go.id/login')->assertOk();

    // Host penyerang dan subdomain yang tidak didaftarkan ditolak sebelum URL apa pun dibangun.
    $this->get('https://evil.example/login')->assertBadRequest();
    $this->get('https://evil.portal.example.go.id/login')->assertBadRequest();
    $this->get('https://portal.example.go.id.evil.example/login')->assertBadRequest();
});

it('ignores forwarded headers unless the proxy is configured as trusted', function () {
    config(['security.hsts.enabled' => true]);

    $this->get('/up', ['X-Forwarded-Proto' => 'https'])
        ->assertHeaderMissing('Strict-Transport-Security');

    // Klien uji datang dari 127.0.0.1, seolah-olah reverse proxy yang mengakhiri TLS.
    config(['trustedproxy.proxies' => '127.0.0.1']);

    $this->get('/up', ['X-Forwarded-Proto' => 'https'])
        ->assertHeader('Strict-Transport-Security');
});
