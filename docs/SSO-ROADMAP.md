# Roadmap Migrasi Login ke SSO

Saat ini login memakai Laravel Fortify: email + password, 2FA TOTP, passkey, dan rate limit. Dokumen ini menjelaskan cara beralih ke Single Sign-On (SSO) Pemerintah Kota tanpa merombak otorisasi yang sudah ada.

## Prinsip

- **SSO hanya mengganti autentikasi** (siapa pengguna). **Otorisasi tetap di aplikasi:** role spatie/laravel-permission dan Policy tidak berubah.
- Protokol yang dituju: **OpenID Connect (OIDC)**, Authorization Code flow + **PKCE** + validasi `state` dan `nonce`. Contoh penyedia: Keycloak atau SSO Pemkot berbasis OIDC.
- Masa transisi: Fortify dan SSO berjalan berdampingan. Setelah semua pegawai tertaut ke SSO, login password dimatikan.

## Rencana teknis

1. **Paket:** `laravel/socialite` + provider OIDC dari SocialiteProviders (mis. `socialiteproviders/keycloak`). Kompatibilitasnya dengan Laravel 13 diverifikasi saat instalasi, dan API Socialite dicek dengan `search-docs`.
2. **Konfigurasi** di `config/services.php`, diisi dari `.env`: `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_BASE_URL`, `SSO_REALM`, `SSO_REDIRECT_URI`.
3. **Tabel `sso_identities`:**

    | Kolom           | Keterangan                               |
    | --------------- | ---------------------------------------- |
    | `user_id`       | FK `users`, cascade                      |
    | `provider`      | mis. `keycloak`                          |
    | `subject`       | klaim `sub` dari IdP (unik per provider) |
    | `last_login_at` | audit                                    |

    Index unik: (`provider`, `subject`). Penautan akun memakai `subject`, **bukan email**, karena email bisa berubah atau dipakai ulang.

4. **Migrasi `users.password` menjadi nullable**, untuk akun yang hanya login lewat SSO.
5. **Route:** `GET /auth/sso/redirect` dan `GET /auth/sso/callback`, dengan `throttle`. Callback:
    - Memvalidasi `state` (Socialite) dan `nonce`/`iss`/`aud`/`exp` pada ID token.
    - Mencari `sso_identities` berdasarkan (`provider`, `subject`).
    - Bila belum tertaut, akun **tidak dibuat otomatis**: tampilkan "akun belum terdaftar, hubungi admin", atau tautkan hanya bila email terverifikasi di IdP **dan** sudah ada di `users`, sesuai keputusan dinas.
    - Menjalankan `Auth::login()` lalu `session()->regenerate()`.
6. **Mapping role (opsional):** klaim grup IdP (mis. `groups: ["dinsos-editor"]`) dipetakan ke role spatie lewat tabel konfigurasi. Role yang diberikan manual oleh Super Admin tidak ditimpa.
7. **2FA:** bila IdP sudah menerapkan MFA, middleware `EnsureTwoFactorForPrivilegedRoles` bisa melewati pengguna SSO. Gunakan klaim `amr`/`acr` sebagai bukti MFA.
8. **Logout:** RP-initiated logout ke endpoint `end_session` IdP, supaya sesi SSO ikut berakhir.

## Checklist keamanan (OWASP A07)

- [ ] PKCE + `state` + `nonce` aktif
- [ ] ID token divalidasi (`iss`, `aud`, `exp`, tanda tangan via JWKS)
- [ ] Redirect URI dibatasi tepat (tanpa wildcard) di IdP
- [ ] Tidak ada pembuatan akun otomatis tanpa persetujuan
- [ ] Login SSO dan kegagalannya tercatat di log audit
- [ ] Sesi aplikasi berakhir saat sesi SSO berakhir (back-channel logout bila didukung)
