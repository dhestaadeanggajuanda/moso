<?php

beforeEach(function () {
    $this->withoutVite();
});

it('sends baseline security headers', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

    expect($response->headers->get('Permissions-Policy'))->toContain('camera=()');
});

it('sends a nonce based content security policy that matches the inline script', function () {
    $response = $this->get(route('home'));

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'none'")
        ->not->toContain("script-src 'self' 'unsafe-inline'");

    preg_match("/'nonce-([^']+)'/", $csp, $matches);

    expect($matches[1] ?? null)->not->toBeEmpty();
    expect($response->getContent())->toContain('<script nonce="'.$matches[1].'">');
});

it('allows images from the public storage bucket', function () {
    config(['filesystems.disks.s3.url' => 'https://media.example.go.id/moso-public']);

    $csp = $this->get(route('home'))->headers->get('Content-Security-Policy');

    expect($csp)->toMatch("#img-src [^;]*https://media\.example\.go\.id#");
});

it('only sends hsts over https', function () {
    config(['security.hsts.enabled' => true]);

    $this->get(route('home'))->assertHeaderMissing('Strict-Transport-Security');

    $this->get(str_replace('http://', 'https://', route('home')))
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('ignores unknown appearance cookie values', function () {
    $response = $this->withUnencryptedCookie('appearance', "dark';alert(1);//")
        ->get(route('home'));

    expect($response->getContent())
        ->not->toContain('alert(1)')
        ->toContain("const appearance = 'system';");
});
