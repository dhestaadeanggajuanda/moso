# Login SSO (OpenID Connect)

Login lewat Single Sign-On (SSO) Pemerintah Kota sudah tersedia dan berjalan berdampingan dengan login Fortify (email + password, 2FA, passkey). Dokumen ini menjelaskan rancangan, konfigurasi, dan langkah migrasi sampai login password dimatikan.

## Prinsip

- **SSO hanya mengganti autentikasi** (siapa pengguna). **Otorisasi tetap di aplikasi:** role spatie/laravel-permission dan Policy tidak berubah.
- Protokol: **OpenID Connect (OIDC)**, Authorization Code flow + **PKCE (S256)** + `state` + `nonce`. Berlaku untuk IdP OIDC apa pun (Keycloak, SSO Pemkot, Azure AD, dan lain-lain) lewat dokumen discovery.
- **Akun tidak pernah dibuat otomatis dari IdP.** Akun tetap dibuat oleh Super Admin.
- Masa transisi: Fortify dan SSO berjalan berdampingan. Setelah semua pegawai tertaut, login lokal dimatikan dengan `SSO_LOCAL_LOGIN=false`.

## Komponen

| Bagian                          | Lokasi                                                                                            |
| ------------------------------- | ------------------------------------------------------------------------------------------------- |
| Konfigurasi                     | `config/sso.php` (dari `.env`, lihat bagian Konfigurasi)                                          |
| Klien OIDC                      | `App\Services\Sso\OidcClient`: discovery, PKCE, penukaran code, validasi ID token, URL logout IdP |
| Penautan akun                   | `App\Services\Sso\SsoAccountLinker`, tabel `sso_identities` (model `SsoIdentity`)                 |
| Route                           | `GET /auth/sso/redirect`, `GET /auth/sso/callback` (`App\Http\Controllers\Auth\SsoController`)    |
| Tombol di halaman login         | `resources/js/pages/auth/Login.vue` (prop `sso`, `localLogin` dari `FortifyServiceProvider`)      |
| Logout ke IdP                   | `App\Http\Responses\LogoutResponse` + listener `RememberSsoLogoutHint`                            |
| Mematikan login lokal           | `App\Http\Middleware\EnsureLocalLoginEnabled` (grup middleware Fortify)                           |
| Pengecualian 2FA dengan MFA IdP | `App\Http\Middleware\EnsureTwoFactorForPrivilegedRoles` (sesi `sso.mfa`)                          |
| IdP lokal untuk pengembangan    | Service `keycloak` (profil Docker `sso`) + realm `docker/keycloak/moso-realm.json`                |
| Dependensi                      | `firebase/php-jwt` (verifikasi tanda tangan JWT dengan JWKS). Tanpa Socialite.                    |

## Alur login

1. Tombol "Masuk dengan SSO" membuka `/auth/sso/redirect`. Aplikasi membuat `state`, `nonce`, dan `code_verifier` acak, menyimpannya di sesi, lalu mengarahkan ke `authorization_endpoint` IdP dengan `code_challenge` (S256).
2. IdP mengembalikan pengguna ke `/auth/sso/callback?code=...&state=...`. Nilai sesi diambil sekali pakai; `state` dibandingkan dengan `hash_equals`.
3. Code ditukar di `token_endpoint` bersama `code_verifier` dan kredensial klien (HTTP Basic).
4. ID token divalidasi:
    - tanda tangan dengan kunci dari `jwks_uri` (algoritma ditentukan kunci IdP, bukan header token; kunci diambil ulang sekali bila IdP merotasi kunci);
    - `iss` sama persis dengan `SSO_ISSUER`, `aud` memuat client id, `azp` sama dengan client id bila ada;
    - `exp` dan `iat` wajib ada (toleransi jam `SSO_LEEWAY`), `nonce` sama dengan nilai sesi, `sub` tidak kosong.
5. Akun dicari lewat (`provider`, `sub`) di `sso_identities`. Bila belum tertaut: hanya ditautkan bila IdP menyatakan `email_verified=true` **dan** email itu sudah ada di `users` dan belum tertaut ke subject lain. Selain itu login ditolak dengan pesan "akun belum terdaftar, hubungi admin".
6. Bila pengguna sudah mengaktifkan 2FA aplikasi dan ID token tidak membuktikan MFA, pengguna diarahkan ke tantangan 2FA Fortify. Selain itu `Auth::login()` lalu `session()->regenerate()`.
7. Setiap langkah tercatat di log audit: `sso.linked`, `sso.login`, `sso.authenticated` (menunggu 2FA), dan `sso.failed` dengan kode alasan. Token tidak pernah dicatat.

Discovery dan JWKS di-cache satu jam. Semua permintaan keluar memakai timeout dan tidak mengikuti redirect. Di produksi, issuer dan semua endpoint wajib HTTPS.

## MFA

Bila ID token membuktikan MFA di IdP, 2FA aplikasi tidak diminta lagi untuk sesi itu, termasuk kewajiban 2FA untuk Super Admin dan Approver. Bukti MFA dibaca dari klaim:

- `amr` yang memuat salah satu nilai `SSO_MFA_AMR` (bawaan: `mfa,otp,hwk,swk,sms,face,fpt`), atau
- `acr` yang sama dengan salah satu nilai `SSO_MFA_ACR` (kosong secara bawaan).

Pastikan IdP benar-benar mengirim klaim ini hanya bila MFA terjadi. Di Keycloak, tambahkan mapper "Authentication Method Reference (AMR)" ke client scope klien portal, atau pakai alur step-up dengan nilai `acr` tertentu.

## Logout

Logout aplikasi mengarahkan pengguna SSO ke `end_session_endpoint` IdP dengan `id_token_hint`, `client_id`, dan `post_logout_redirect_uri` = `APP_URL` (tanpa garis miring di akhir). Daftarkan nilai itu persis di IdP. Back-channel logout dari IdP belum didukung: sesi aplikasi tetap berakhir sesuai `SESSION_LIFETIME`.

## Konfigurasi

| Variabel                             | Keterangan                                                                                            |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------- |
| `SSO_ENABLED`                        | `true` untuk menampilkan tombol SSO dan mengaktifkan route                                            |
| `SSO_PROVIDER`                       | Kunci penyedia di `sso_identities.provider` (mis. `pemkot`). Jangan diganti setelah ada akun tertaut. |
| `SSO_LABEL`                          | Teks tombol login                                                                                     |
| `SSO_ISSUER`                         | Issuer persis seperti di discovery, mis. `https://sso.singkawangkota.go.id/realms/pemkot`             |
| `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET` | Kredensial klien confidential dari IdP                                                                |
| `SSO_REDIRECT_URI`                   | Kosong = `APP_URL/auth/sso/callback`                                                                  |
| `SSO_MFA_AMR`, `SSO_MFA_ACR`         | Klaim yang dianggap bukti MFA                                                                         |
| `SSO_LOCAL_LOGIN`                    | `false` = password, passkey, dan reset kata sandi dimatikan (hanya berlaku bila SSO aktif)            |

Di IdP, daftarkan klien **confidential** dengan Standard Flow saja (tanpa implicit dan direct access grants), PKCE S256 wajib, redirect URI persis `APP_URL/auth/sso/callback`, dan post-logout redirect URI persis `APP_URL`. Scope yang diminta: `openid email profile`.

Sesudah deploy, `php artisan security:check` ikut memeriksa issuer HTTPS dan kredensial klien bila SSO aktif.

## Mencoba dengan Keycloak lokal

1. Isi di `.env`: `KEYCLOAK_ADMIN_PASSWORD`, `SSO_CLIENT_SECRET` (acak, mis. `openssl rand -hex 24`), `SSO_ISSUER=http://localhost:8180/realms/moso`, `SSO_ENABLED=true`.
2. Jalankan:

    ```bash
    docker compose -f docker/compose.yaml --env-file .env --profile sso up -d keycloak
    ```

    Realm `moso` dan klien `moso-portal` diimpor otomatis dari `docker/keycloak/moso-realm.json` (redirect URI = `APP_URL/auth/sso/callback`, jadi buka portal lewat host yang sama dengan `APP_URL`). Console admin: `http://localhost:8180`.

3. Di console admin, realm `moso` → Users → buat pengguna dengan email yang sama dengan akun portal yang sudah ada, centang "Email verified", lalu atur kata sandinya.
4. Buka `/login`, klik "Masuk dengan SSO". Login pertama menautkan akun; login berikutnya memakai `sub`.

Untuk server development dan produksi, pakai `docker/deploy/keycloak` (mode produksi dengan MySQL, lihat `docs/DEPLOY.md`); realm yang sama diimpor dengan `MOSO_APP_URL` sebagai URL portal.

Data Keycloak lokal tidak disimpan di volume: membuat ulang container menghapus pengguna uji, dan subject baru tidak bisa mengambil alih akun yang sudah tertaut (hapus baris `sso_identities` lama bila perlu).

## Langkah migrasi di produksi

1. Minta klien OIDC ke pengelola SSO Pemkot dengan pengaturan di atas.
2. Isi variabel `SSO_*`, set `SSO_ENABLED=true`, lalu jalankan `php artisan security:check`.
3. Umumkan ke pegawai: login pertama lewat SSO menautkan akun secara otomatis bila email di IdP sama dan terverifikasi.
4. Pantau log audit `sso.failed` dengan alasan `not_registered` dan `email_unverified` untuk pegawai yang belum bisa masuk.
5. Setelah semua akun tertaut, set `SSO_LOCAL_LOGIN=false`. Bila IdP bermasalah, login lokal bisa dinyalakan lagi sementara dengan `SSO_LOCAL_LOGIN=true`.

## Belum termasuk

- Pemetaan grup IdP ke role (mis. klaim `groups`). Role tetap diberikan manual oleh Super Admin.
- Back-channel logout.
- Akun khusus SSO tanpa password (`users.password` nullable). Baru dibutuhkan bila ada pembuatan akun yang tidak lewat password.

## Checklist keamanan (OWASP A07)

- [x] PKCE (S256) + `state` + `nonce` aktif; nilai sesi sekali pakai
- [x] ID token divalidasi (`iss`, `aud`/`azp`, `exp`/`iat`, `nonce`, tanda tangan via JWKS)
- [x] Redirect URI dibatasi tepat (tanpa wildcard) di IdP; realm lokal mencontohkannya
- [x] Tidak ada pembuatan akun otomatis; penautan hanya lewat email terverifikasi ke akun yang ada
- [x] Login SSO dan kegagalannya tercatat di log audit tanpa token
- [x] Logout aplikasi mengakhiri sesi di IdP (RP-initiated logout)
- [ ] Back-channel logout dari IdP
