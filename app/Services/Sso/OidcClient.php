<?php

namespace App\Services\Sso;

use App\Exceptions\SsoAuthenticationFailed;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Klien OpenID Connect (Authorization Code + PKCE S256, state, nonce).
 *
 * Semua endpoint diambil dari discovery issuer yang dikonfigurasi di
 * config/sso.php, tidak pernah dari input pengguna. ID token wajib lolos:
 * tanda tangan JWKS (alg dari kunci IdP, bukan dari token), iss persis,
 * aud memuat client_id (azp bila ada), exp/iat, dan nonce (OWASP A07).
 */
class OidcClient
{
    private const DISCOVERY_TTL_SECONDS = 3600;

    /**
     * URL login IdP beserta nilai rahasia yang harus disimpan di sesi sampai callback.
     *
     * @return array{url: string, state: string, nonce: string, code_verifier: string}
     */
    public function authorizationRequest(): array
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $codeVerifier = Str::random(64);
        $endpoint = $this->discovery()['authorization_endpoint'];

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'scope' => implode(' ', (array) config('sso.scopes')),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => self::base64Url(hash('sha256', $codeVerifier, true)),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        return [
            'url' => $endpoint.(str_contains($endpoint, '?') ? '&' : '?').$query,
            'state' => $state,
            'nonce' => $nonce,
            'code_verifier' => $codeVerifier,
        ];
    }

    /**
     * Tukar authorization code dengan token, lalu validasi ID token.
     *
     * @throws SsoAuthenticationFailed
     */
    public function authenticate(string $code, string $codeVerifier, string $nonce): OidcIdentity
    {
        try {
            $tokens = $this->http()
                ->asForm()
                ->withBasicAuth(rawurlencode($this->clientId()), rawurlencode((string) config('sso.client_secret')))
                ->post($this->discovery()['token_endpoint'], [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri(),
                    'code_verifier' => $codeVerifier,
                ])
                ->throw()
                ->json();
        } catch (SsoAuthenticationFailed $e) {
            throw $e;
        } catch (Throwable $e) {
            throw SsoAuthenticationFailed::because('token_exchange_failed', $e);
        }

        $idToken = is_array($tokens) ? ($tokens['id_token'] ?? null) : null;

        if (! is_string($idToken) || $idToken === '') {
            throw SsoAuthenticationFailed::because('missing_id_token');
        }

        $claims = $this->validatedClaims($idToken, $nonce);

        return new OidcIdentity(
            subject: $claims['sub'],
            email: is_string($claims['email'] ?? null) ? $claims['email'] : null,
            emailVerified: ($claims['email_verified'] ?? false) === true || ($claims['email_verified'] ?? null) === 'true',
            name: is_string($claims['name'] ?? null) ? $claims['name'] : null,
            mfa: $this->provesMfa($claims),
            idToken: $idToken,
        );
    }

    /**
     * URL logout IdP (RP-initiated logout), atau null bila IdP tidak menyediakannya.
     */
    public function endSessionUrl(?string $idTokenHint, string $postLogoutRedirectUri): ?string
    {
        try {
            $endpoint = $this->discovery()['end_session_endpoint'] ?? null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! is_string($endpoint) || $endpoint === '') {
            return null;
        }

        $query = http_build_query(array_filter([
            'client_id' => $this->clientId(),
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
            'id_token_hint' => $idTokenHint,
        ]), '', '&', PHP_QUERY_RFC3986);

        return $endpoint.(str_contains($endpoint, '?') ? '&' : '?').$query;
    }

    public function redirectUri(): string
    {
        $configured = config('sso.redirect_uri');

        return is_string($configured) && $configured !== '' ? $configured : route('sso.callback');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws SsoAuthenticationFailed
     */
    private function validatedClaims(string $idToken, string $nonce): array
    {
        $previousLeeway = JWT::$leeway;
        JWT::$leeway = max(0, (int) config('sso.leeway'));

        try {
            $payload = JWT::decode($idToken, $this->keys($this->keyId($idToken)));
        } catch (Throwable $e) {
            throw SsoAuthenticationFailed::because('invalid_id_token', $e);
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        /** @var array<string, mixed> $claims */
        $claims = json_decode((string) json_encode($payload), true);
        $audiences = (array) ($claims['aud'] ?? []);

        $checks = [
            'issuer_mismatch' => ($claims['iss'] ?? null) === $this->issuer(),
            'audience_mismatch' => in_array($this->clientId(), $audiences, true),
            'authorized_party_mismatch' => isset($claims['azp']) || count($audiences) > 1
                ? ($claims['azp'] ?? null) === $this->clientId()
                : true,
            'missing_expiry' => is_numeric($claims['exp'] ?? null) && is_numeric($claims['iat'] ?? null),
            'nonce_mismatch' => is_string($claims['nonce'] ?? null) && hash_equals($nonce, $claims['nonce']),
            'missing_subject' => is_string($claims['sub'] ?? null) && $claims['sub'] !== '' && strlen($claims['sub']) <= 255,
        ];

        foreach ($checks as $reason => $passed) {
            if (! $passed) {
                throw SsoAuthenticationFailed::because($reason);
            }
        }

        return $claims;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function provesMfa(array $claims): bool
    {
        $amr = array_map(
            fn (mixed $method): string => strtolower((string) $method),
            array_filter((array) ($claims['amr'] ?? []), 'is_scalar'),
        );
        $acceptedAmr = array_map('strtolower', (array) config('sso.mfa.amr'));
        $acceptedAcr = (array) config('sso.mfa.acr');

        return array_intersect($amr, $acceptedAmr) !== []
            || (is_string($claims['acr'] ?? null) && in_array($claims['acr'], $acceptedAcr, true));
    }

    /**
     * Kunci publik IdP. Bila `kid` token belum dikenal (IdP merotasi kunci),
     * JWKS diambil ulang sekali.
     *
     * @return array<string, Key>
     */
    private function keys(?string $keyId): array
    {
        // Yang di-cache JWKS mentah: objek kunci OpenSSL tidak bisa diserialisasi ke cache.
        $cacheKey = 'sso:jwks:'.sha1($this->issuer());
        $jwks = Cache::get($cacheKey);
        $keys = is_array($jwks) ? self::parseKeys($jwks) : null;

        if ($keys === null || ($keyId !== null && ! array_key_exists($keyId, $keys))) {
            $jwks = $this->fetchJwks();
            Cache::put($cacheKey, $jwks, self::DISCOVERY_TTL_SECONDS);
            $keys = self::parseKeys($jwks);
        }

        return $keys;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchJwks(): array
    {
        $jwks = $this->http()->get($this->discovery()['jwks_uri'])->throw()->json();

        if (! is_array($jwks) || ! isset($jwks['keys']) || ! is_array($jwks['keys'])) {
            throw SsoAuthenticationFailed::because('invalid_jwks');
        }

        return $jwks;
    }

    /**
     * Kunci tanpa "alg" dianggap RS256; kunci simetris (HS*) tidak pernah ada di JWKS publik.
     *
     * @param  array<string, mixed>  $jwks
     * @return array<string, Key>
     */
    private static function parseKeys(array $jwks): array
    {
        return JWK::parseKeySet($jwks, 'RS256');
    }

    private function keyId(string $jwt): ?string
    {
        $header = json_decode(JWT::urlsafeB64Decode(Str::before($jwt, '.')), true);

        return is_array($header) && is_string($header['kid'] ?? null) ? $header['kid'] : null;
    }

    /**
     * Dokumen discovery issuer, di-cache bila valid.
     *
     * @return array{issuer: string, authorization_endpoint: string, token_endpoint: string, jwks_uri: string, end_session_endpoint?: string}
     *
     * @throws SsoAuthenticationFailed
     */
    private function discovery(): array
    {
        if ($this->issuer() === '' || $this->clientId() === '' || ! $this->isAllowedEndpoint($this->issuer())) {
            throw SsoAuthenticationFailed::because('not_configured');
        }

        $cacheKey = 'sso:discovery:'.sha1($this->issuer());
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            /** @var array{issuer: string, authorization_endpoint: string, token_endpoint: string, jwks_uri: string, end_session_endpoint?: string} $cached */
            return $cached;
        }

        try {
            $document = $this->http()->get($this->issuer().'/.well-known/openid-configuration')->throw()->json();
        } catch (Throwable $e) {
            throw SsoAuthenticationFailed::because('discovery_failed', $e);
        }

        if (! is_array($document) || ($document['issuer'] ?? null) !== $this->issuer()) {
            throw SsoAuthenticationFailed::because('discovery_issuer_mismatch');
        }

        foreach (['authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $endpoint) {
            if (! $this->isAllowedEndpoint($document[$endpoint] ?? null)) {
                throw SsoAuthenticationFailed::because('discovery_invalid_endpoint');
            }
        }

        if (isset($document['end_session_endpoint']) && ! $this->isAllowedEndpoint($document['end_session_endpoint'])) {
            unset($document['end_session_endpoint']);
        }

        $discovery = array_intersect_key($document, array_flip([
            'issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri', 'end_session_endpoint',
        ]));

        Cache::put($cacheKey, $discovery, self::DISCOVERY_TTL_SECONDS);

        /** @var array{issuer: string, authorization_endpoint: string, token_endpoint: string, jwks_uri: string, end_session_endpoint?: string} $discovery */
        return $discovery;
    }

    /**
     * Endpoint harus URL http(s) absolut; di produksi wajib HTTPS.
     */
    private function isAllowedEndpoint(mixed $url): bool
    {
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme === 'https' || ($scheme === 'http' && ! app()->isProduction());
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            // Jangan ikuti redirect: permintaan server hanya ke endpoint dari discovery (SSRF, A01).
            ->withoutRedirecting()
            ->connectTimeout((int) config('sso.timeout'))
            ->timeout((int) config('sso.timeout'));
    }

    private function issuer(): string
    {
        return (string) config('sso.issuer');
    }

    private function clientId(): string
    {
        return (string) config('sso.client_id');
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
