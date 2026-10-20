<?php

/**
 * Konfigurasi produksi yang lolos semua pemeriksaan wajib.
 */
function productionReadyConfig(): void
{
    app()['env'] = 'production';

    config([
        'app.debug' => false,
        'app.url' => 'https://portal.example.go.id',
        'session.secure' => true,
        'session.encrypt' => true,
        'security.csp.enabled' => true,
        'security.csp.report_only' => false,
        'security.hsts.enabled' => true,
        'security.two_factor.enforce' => true,
        'logging.default' => 'stack',
        'logging.channels.stack.level' => 'warning',
        'trustedproxy.proxies' => '10.0.0.5',
    ]);
}

it('passes when production settings are hardened', function () {
    productionReadyConfig();

    $this->artisan('security:check')
        ->expectsOutputToContain('Semua pemeriksaan wajib lolos.')
        ->assertSuccessful();
});

it('fails when a required production setting is unsafe', function (string $key, mixed $value, string $label) {
    productionReadyConfig();
    config([$key => $value]);

    $this->artisan('security:check')
        ->expectsOutputToContain($label)
        ->expectsOutputToContain('1 pemeriksaan wajib gagal')
        ->assertFailed();
})->with([
    'debug on' => ['app.debug', true, 'APP_DEBUG=false'],
    'plain http url' => ['app.url', 'http://portal.example.go.id', 'APP_URL=https://'],
    'insecure session cookie' => ['session.secure', false, 'SESSION_SECURE_COOKIE=true'],
    'csp report only' => ['security.csp.report_only', true, 'CSP_REPORT_ONLY=false'],
    'hsts off' => ['security.hsts.enabled', false, 'HSTS_ENABLED=true'],
    '2fa not enforced' => ['security.two_factor.enforce', false, 'SECURITY_ENFORCE_2FA=true'],
]);

it('checks the sso connection only when sso is enabled', function () {
    productionReadyConfig();
    config(['sso.enabled' => true, 'sso.issuer' => 'http://sso.example.go.id/realms/pemkot', 'sso.client_id' => 'moso', 'sso.client_secret' => 'rahasia']);

    $this->artisan('security:check')
        ->expectsOutputToContain('SSO_ISSUER=https://')
        ->assertFailed();

    config(['sso.enabled' => false]);

    $this->artisan('security:check')->assertSuccessful();
});

it('fails outside the production environment', function () {
    productionReadyConfig();
    app()['env'] = 'local';

    $this->artisan('security:check')->assertFailed();
});

it('only warns about debug logging and missing trusted proxies', function () {
    productionReadyConfig();
    config(['logging.channels.stack.level' => 'debug', 'trustedproxy.proxies' => null]);

    $this->artisan('security:check')
        ->expectsOutputToContain('LOG_LEVEL=warning')
        ->expectsOutputToContain('TRUSTED_PROXIES')
        ->assertSuccessful();
});

it('checks the assistant token and transport only when the assistant is enabled', function (string $url, ?string $token, ?string $hint) {
    productionReadyConfig();
    config(['agentic.enabled' => true, 'agentic.base_url' => $url, 'agentic.token' => $token]);

    $command = $this->artisan('security:check');

    if ($hint === null) {
        $command->assertSuccessful();
    } else {
        $command->expectsOutputToContain($hint)->assertFailed();
    }
})->with([
    'https' => ['https://agentic.example.go.id', 'token', null],
    'http to a private ip' => ['http://172.16.20.30:8080', 'token', null],
    'http to a docker service name' => ['http://agentic:8080', 'token', null],
    'http to a public host' => ['http://agentic.example.go.id', 'token', 'AGENTIC_URL=https://'],
    'http to a public ip' => ['http://203.0.113.10:8080', 'token', 'AGENTIC_URL=https://'],
    'missing token' => ['https://agentic.example.go.id', null, 'AGENTIC_TOKEN'],
]);
