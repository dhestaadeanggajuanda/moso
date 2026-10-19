<?php

use App\Enums\RoleName;
use App\Models\SsoIdentity;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pasangan kunci RSA untuk IdP palsu, dibuat sekali per kid selama proses tes.
 *
 * @return array{private: string, jwk: array<string, string>}
 */
function ssoTestKey(string $kid): array
{
    static $keys = [];

    return $keys[$kid] ??= (function () use ($kid): array {
        // OpenSSL di Windows butuh berkas konfigurasi; cukup yang minimal.
        $config = (string) tempnam(sys_get_temp_dir(), 'ossl');
        file_put_contents($config, "[ req ]\ndistinguished_name = req_dn\n[ req_dn ]\n");
        $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $config];

        $key = openssl_pkey_new($options);
        openssl_pkey_export($key, $private, null, $options);
        $rsa = openssl_pkey_get_details($key)['rsa'];
        unlink($config);

        $base64Url = fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        return [
            'private' => $private,
            'jwk' => ['kty' => 'RSA', 'kid' => $kid, 'use' => 'sig', 'alg' => 'RS256', 'n' => $base64Url($rsa['n']), 'e' => $base64Url($rsa['e'])],
        ];
    })();
}

/**
 * IdP OIDC palsu: discovery dan JWKS. Token endpoint dipasang oleh ssoCallback().
 *
 * @param  list<list<string>>  $jwksKidsPerFetch  kid yang dipublikasikan pada tiap pengambilan JWKS berturut-turut
 */
function fakeSsoProvider(array $jwksKidsPerFetch = [['kunci-uji']]): void
{
    config([
        'sso.enabled' => true,
        'sso.provider' => 'pemkot',
        'sso.label' => 'SSO Pemkot',
        'sso.issuer' => 'https://sso.test/realms/moso',
        'sso.client_id' => 'moso-portal',
        'sso.client_secret' => 'rahasia-uji',
        'sso.redirect_uri' => null,
        'sso.local_login' => true,
        'sso.mfa.amr' => ['mfa', 'otp'],
        'sso.mfa.acr' => [],
    ]);

    $jwks = Http::sequence();

    foreach ($jwksKidsPerFetch as $kids) {
        $jwks->push(['keys' => array_map(fn (string $kid): array => ssoTestKey($kid)['jwk'], $kids)]);
    }

    Http::preventStrayRequests();
    Http::fake([
        'sso.test/realms/moso/.well-known/openid-configuration' => Http::response([
            'issuer' => 'https://sso.test/realms/moso',
            'authorization_endpoint' => 'https://sso.test/realms/moso/protocol/openid-connect/auth',
            'token_endpoint' => 'https://sso.test/realms/moso/protocol/openid-connect/token',
            'jwks_uri' => 'https://sso.test/realms/moso/protocol/openid-connect/certs',
            'end_session_endpoint' => 'https://sso.test/realms/moso/protocol/openid-connect/logout',
        ]),
        'sso.test/realms/moso/protocol/openid-connect/certs' => $jwks,
        // ID token untuk callback berikutnya diisi ssoCallback() (stub Http::fake menumpuk, jadi dibaca dari container).
        'sso.test/realms/moso/protocol/openid-connect/token' => fn (HttpRequest $request) => Http::response([
            'access_token' => 'akses-uji',
            'token_type' => 'Bearer',
            'id_token' => app('testing.sso.id_token'),
        ]),
    ]);
}

/**
 * Jalankan alur lengkap: redirect ke IdP, lalu callback dengan ID token bertanda tangan.
 *
 * @param  array<string, mixed>  $claims  penimpa klaim bawaan; null menghapus klaim
 * @param  array<string, string>  $query  penimpa query callback
 */
function ssoCallback(TestCase $test, array $claims = [], string $headerKid = 'kunci-uji', ?string $signingKid = null, array $query = []): TestResponse
{
    $test->get(route('sso.redirect'))->assertRedirect();
    $pending = session('sso.pending');
    $now = time();

    $payload = array_filter([
        'iss' => 'https://sso.test/realms/moso',
        'aud' => 'moso-portal',
        'azp' => 'moso-portal',
        'sub' => 'subjek-123',
        'iat' => $now,
        'exp' => $now + 300,
        'nonce' => $pending['nonce'],
        'email' => 'pegawai@dinas.test',
        'email_verified' => true,
        'name' => 'Pegawai Dinas',
        'amr' => ['pwd'],
        ...$claims,
    ], fn (mixed $value): bool => $value !== null);

    app()->instance('testing.sso.id_token', JWT::encode($payload, ssoTestKey($signingKid ?? $headerKid)['private'], 'RS256', $headerKid));

    return $test->get(route('sso.callback', ['code' => 'kode-uji', 'state' => $pending['state'], ...$query]));
}

describe('redirect', function () {
    it('is not available while sso is disabled', function () {
        config(['sso.enabled' => false]);

        $this->get(route('sso.redirect'))->assertNotFound();
        $this->get(route('sso.callback'))->assertNotFound();
    });

    it('sends the user to the provider with pkce, state, and nonce', function () {
        fakeSsoProvider();

        $response = $this->get(route('sso.redirect'));

        $pending = session('sso.pending');
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        expect($response->headers->get('Location'))->toStartWith('https://sso.test/realms/moso/protocol/openid-connect/auth?')
            ->and($query)->toMatchArray([
                'response_type' => 'code',
                'client_id' => 'moso-portal',
                'redirect_uri' => route('sso.callback'),
                'scope' => 'openid email profile',
                'state' => $pending['state'],
                'nonce' => $pending['nonce'],
                'code_challenge_method' => 'S256',
                'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $pending['code_verifier'], true)), '+/', '-_'), '='),
            ]);
    });
});

describe('callback', function () {
    beforeEach(function () {
        fakeSsoProvider();
    });

    it('signs in a linked account and exchanges the code with the pkce verifier', function () {
        $identity = SsoIdentity::factory()->create(['subject' => 'subjek-123']);

        ssoCallback($this)->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($identity->user);
        expect($identity->refresh()->last_login_at)->not->toBeNull();
        Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/token')
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'kode-uji'
            && is_string($request['code_verifier'])
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('moso-portal:rahasia-uji')));
    });

    it('links an existing account on first login when the provider verified the email', function () {
        $user = User::factory()->create(['email' => 'Pegawai@Dinas.test']);

        ssoCallback($this)->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('sso_identities', ['user_id' => $user->id, 'provider' => 'pemkot', 'subject' => 'subjek-123']);
    });

    it('never creates an account or links an unverified email', function (array $claims) {
        User::factory()->create(['email' => 'pegawai@dinas.test']);

        ssoCallback($this, $claims)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['sso' => 'Akun SSO Anda belum terdaftar di portal ini. Hubungi admin dinas.']);

        $this->assertGuest();
        expect(User::count())->toBe(1)
            ->and(SsoIdentity::count())->toBe(0);
    })->with([
        'unknown email' => [['email' => 'orang-lain@dinas.test']],
        'email not verified' => [['email_verified' => false]],
        'no email claim' => [['email' => null]],
    ]);

    it('does not take over an account already linked to another subject', function () {
        $user = User::factory()->create(['email' => 'pegawai@dinas.test']);
        SsoIdentity::factory()->for($user)->create(['subject' => 'subjek-lama']);

        ssoCallback($this)->assertSessionHasErrors(['sso' => 'Akun portal dengan email ini sudah tertaut ke akun SSO lain. Hubungi admin dinas.']);

        $this->assertGuest();
    });

    it('rejects a callback whose state does not match the session', function () {
        SsoIdentity::factory()->create(['subject' => 'subjek-123']);

        ssoCallback($this, query: ['state' => 'state-palsu'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['sso' => 'Login SSO gagal. Coba lagi, atau hubungi admin bila masalah berlanjut.']);

        $this->assertGuest();
        Http::assertNotSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/token'));
    });

    it('cannot replay a callback after its state was used', function () {
        SsoIdentity::factory()->create(['subject' => 'subjek-123']);
        $callbackUrl = ssoCallback($this)->baseRequest->fullUrl();
        $this->assertAuthenticated();
        $this->post(route('logout'));

        // URL callback yang sama diputar ulang: state sekali pakai sudah dihapus dari sesi.
        $this->get($callbackUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');
        $this->assertGuest();
    });

    it('rejects an id token that fails validation', function (array $claims, string $headerKid, ?string $signingKid) {
        SsoIdentity::factory()->create(['subject' => 'subjek-123']);

        ssoCallback($this, $claims, $headerKid, $signingKid)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
    })->with([
        'other issuer' => [['iss' => 'https://evil.test/realms/moso'], 'kunci-uji', null],
        'other audience' => [['aud' => 'aplikasi-lain', 'azp' => 'aplikasi-lain'], 'kunci-uji', null],
        'other authorized party' => [['aud' => ['moso-portal', 'aplikasi-lain'], 'azp' => 'aplikasi-lain'], 'kunci-uji', null],
        'expired' => [['iat' => time() - 7200, 'exp' => time() - 3600], 'kunci-uji', null],
        'no expiry' => [['exp' => null], 'kunci-uji', null],
        'wrong nonce' => [['nonce' => 'nonce-lain'], 'kunci-uji', null],
        'empty subject' => [['sub' => ''], 'kunci-uji', null],
        'forged signature' => [[], 'kunci-uji', 'kunci-penyerang'],
        'unknown key' => [[], 'kunci-penyerang', null],
    ]);

    it('reports a cancelled login at the provider', function () {
        $this->get(route('sso.redirect'));

        $this->get(route('sso.callback', ['error' => 'access_denied', 'state' => session('sso.pending.state')]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['sso' => 'Login SSO dibatalkan.']);
    });

    it('still asks for the app second factor when the provider did not prove mfa', function () {
        $user = User::factory()->withTwoFactor()->create();
        SsoIdentity::factory()->for($user)->create(['subject' => 'subjek-123']);

        ssoCallback($this)->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
        expect(session('login.id'))->toBe($user->id);
    });

    it('signs in directly when the provider proved mfa', function () {
        $user = User::factory()->withTwoFactor()->create();
        SsoIdentity::factory()->for($user)->create(['subject' => 'subjek-123']);

        ssoCallback($this, ['amr' => ['pwd', 'otp']])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('lets privileged users skip app 2fa setup only with provider mfa', function (array $amr, bool $redirectedToSetup) {
        config(['security.two_factor.enforce' => true]);
        $approver = userWithRole(RoleName::Approver);
        SsoIdentity::factory()->for($approver)->create(['subject' => 'subjek-123']);

        ssoCallback($this, ['amr' => $amr]);

        $response = $this->get(route('admin.articles.index'));

        $redirectedToSetup
            ? $response->assertRedirect(route('security.edit'))
            : $response->assertOk();
    })->with([
        'otp at the provider' => [['pwd', 'otp'], false],
        'password only' => [['pwd'], true],
    ]);
});

describe('key rotation', function () {
    it('fetches the key set again when the provider rotates its signing key', function () {
        fakeSsoProvider([['kunci-uji'], ['kunci-uji', 'kunci-baru']]);
        SsoIdentity::factory()->create(['subject' => 'subjek-123']);

        ssoCallback($this)->assertRedirect(route('dashboard'));
        $this->post(route('logout'));

        ssoCallback($this, headerKid: 'kunci-baru')->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    });
});

describe('logout', function () {
    it('ends the session at the provider for sso users', function () {
        fakeSsoProvider();
        SsoIdentity::factory()->create(['subject' => 'subjek-123']);
        ssoCallback($this);

        $response = $this->post(route('logout'));

        $this->assertGuest();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        expect($response->headers->get('Location'))->toStartWith('https://sso.test/realms/moso/protocol/openid-connect/logout?')
            ->and($query['client_id'])->toBe('moso-portal')
            ->and($query['post_logout_redirect_uri'])->toBe(route('home'))
            ->and($query['id_token_hint'])->toBeString()->not->toBeEmpty();
    });

    it('keeps the normal redirect for password users', function () {
        fakeSsoProvider();

        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect('/');
    });
});

describe('local login switch', function () {
    it('turns off password, passkey, and reset routes when only sso is allowed', function () {
        fakeSsoProvider();
        config(['sso.local_login' => false]);
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertNotFound();
        $this->get(route('password.request'))->assertNotFound();
        $this->post(route('password.email'), ['email' => $user->email])->assertNotFound();
        $this->assertGuest();

        $this->get(route('login'))->assertInertia(fn ($page) => $page
            ->where('localLogin', false)
            ->where('canResetPassword', false)
            ->where('sso.label', 'SSO Pemkot'));
    });

    it('keeps local login while sso itself is disabled', function () {
        config(['sso.enabled' => false, 'sso.local_login' => false]);
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    });
});
