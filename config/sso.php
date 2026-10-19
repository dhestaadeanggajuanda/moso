<?php

/*
|--------------------------------------------------------------------------
| Single Sign-On OpenID Connect (OWASP A07)
|--------------------------------------------------------------------------
|
| Login lewat IdP OIDC (Keycloak, SSO Pemkot, atau penyedia OIDC lain) dengan
| Authorization Code + PKCE, state, dan nonce. ID token selalu divalidasi
| (tanda tangan JWKS, iss, aud, exp, nonce). Lihat docs/SSO-ROADMAP.md.
|
| SSO hanya mengganti autentikasi. Otorisasi tetap memakai role dan Policy
| aplikasi; akun tidak pernah dibuat otomatis dari IdP.
|
*/

$list = fn (string $key, string $default = ''): array => array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env($key, $default)),
)));

return [

    'enabled' => (bool) env('SSO_ENABLED', false),

    // Kunci penyedia yang disimpan di sso_identities.provider (jangan diganti setelah ada akun tertaut).
    'provider' => env('SSO_PROVIDER', 'pemkot'),

    // Teks tombol di halaman login.
    'label' => env('SSO_LABEL', 'SSO Pemerintah Kota'),

    // Issuer persis seperti di discovery IdP, mis. https://sso.example.go.id/realms/pemkot
    'issuer' => rtrim((string) env('SSO_ISSUER', ''), '/'),

    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),

    // Kosong = route sso.callback dari APP_URL. Daftarkan URL ini persis (tanpa wildcard) di IdP.
    'redirect_uri' => env('SSO_REDIRECT_URI'),

    'scopes' => ['openid', 'email', 'profile'],

    // Toleransi selisih jam antara server dan IdP saat memeriksa exp/iat (detik).
    'leeway' => (int) env('SSO_LEEWAY', 60),

    'timeout' => (int) env('SSO_TIMEOUT', 5),

    // Bukti MFA di ID token. Bila cocok, 2FA aplikasi tidak diminta lagi untuk sesi itu.
    'mfa' => [
        'amr' => $list('SSO_MFA_AMR', 'mfa,otp,hwk,swk,sms,face,fpt'),
        'acr' => $list('SSO_MFA_ACR'),
    ],

    // false = login lokal (password, passkey, reset password) dimatikan, hanya SSO.
    // Hanya berlaku bila SSO aktif, agar tidak ada yang terkunci saat SSO belum siap.
    'local_login' => (bool) env('SSO_LOCAL_LOGIN', true),

];
