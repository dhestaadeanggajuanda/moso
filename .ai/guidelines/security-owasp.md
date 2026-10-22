# Moso: Aturan Keamanan Wajib (OWASP Top 10:2025)

Aturan ini wajib dipatuhi di setiap perubahan kode. Matriks lengkapnya ada di `docs/SECURITY-OWASP.md`.

## A01 Broken Access Control (termasuk SSRF)

- Setiap aksi controller yang membaca atau mengubah data wajib diotorisasi lewat Policy (`$this->authorize()` / `Gate::authorize()` / `can` middleware). Pengecekan role di frontend hanya untuk tampilan, tidak pernah menjadi sumber otorisasi.
- Kepemilikan dicek di Policy, misalnya Kontributor hanya boleh mengubah artikel miliknya (anti-IDOR).
- Request HTTP keluar (Media Center) hanya ke base URL dari config, tidak pernah ke URL dari input pengguna atau JSON blok, dan tidak mengikuti redirect (`withoutRedirecting()`). Kata kunci divalidasi dengan regex `^[a-z0-9-]{1,50}$`.
- Tautan buatan pengelola (menu, tombol blok, pengaturan) wajib lolos `App\Support\SafeUrl`; jangan membuat pola URL sendiri.
- API publik `/api/v1` hanya mengembalikan data berstatus published dan memakai `throttle`.

## A02 Security Misconfiguration

- Jangan melonggarkan CSP di `App\Http\Middleware\SecurityHeaders` (misalnya `'unsafe-inline'` atau `'unsafe-eval'` di script-src). Origin baru ditambahkan lewat `config/security.php` (`CSP_*`).
- Inline `<script>` di Blade wajib memakai `nonce="{{ Vite::cspNonce() }}"`.
- Registrasi publik Fortify tetap mati. Akun dibuat oleh Super Admin.
- TrustHosts di `bootstrap/app.php` jangan dilepas: hanya host `APP_URL` dan `TRUSTED_HOSTS` yang dilayani. Proxy diatur lewat `TRUSTED_PROXIES` (`config/trustedproxy.php`), jangan `'*'` bila aplikasi bisa diakses langsung.
- Jalankan `php artisan security:check` sesudah deploy produksi; tambahkan pemeriksaan baru di `App\Console\Commands\SecurityCheck` bila ada pengaturan keamanan baru di `.env`.

## A03 Software Supply Chain

- Utamakan fitur bawaan Laravel/Vue sebelum menambah paket. Paket baru harus aktif dipelihara dan kompatibel dengan Laravel 13.
- Jalankan `composer audit:all` setelah menambah dependensi.

## A04 Cryptographic Failures

- Nilai rahasia di tabel `settings` disimpan terenkripsi (`is_encrypted` + `Crypt`). Jangan menyimpan atau me-log token dan password dalam bentuk plaintext.

## A05 Injection

- Query hanya lewat Eloquent atau Query Builder dengan binding. Untuk `DB::raw`/`whereRaw`, nilai dari pengguna wajib di-bind.
- HTML dari editor (body artikel) wajib disanitasi server-side lewat `App\Services\Content\HtmlSanitizer` sebelum disimpan.
- `v-html` hanya untuk HTML yang sudah disanitasi server. Konten dari Media Center atau API eksternal selalu dirender sebagai teks, tidak pernah lewat `v-html`.
- Upload: validasi `mimes` dan ukuran, re-encode gambar, nama file UUID. SVG, HTML, dan file eksekusi ditolak.

## A06 / A08 Insecure Design dan Integrity

- `content_blocks` divalidasi terhadap `BlockRegistry` sebelum disimpan. Jangan memakai `unserialize()` untuk input apa pun.
- Alur penerbitan per website (`ArticleWorkflowMode`, setting `articles.workflow`, hanya Super Admin): Sederhana (bawaan, seperti WordPress) atau Bertahap (empat mata). Semua aturan alur ada di `ArticlePolicy` dan `ArticleAction::fromStatuses()`, tidak pernah di frontend. Kontributor tidak pernah menerbitkan sendiri di alur mana pun, dan penerbitan dari form tetap lewat `ArticleWorkflow`.

## A07 Authentication Failures

- Pertahankan rate limit login dan 2FA Fortify. Role Super Admin dan Approver wajib 2FA.
- Route reset kata sandi dibatasi `ThrottlePasswordResets` (grup middleware Fortify) dan selalu dijawab `PasswordResetLinkResponse`; jangan menampilkan pesan yang membedakan email terdaftar.
- `AuthenticateSession` tetap di grup `web` agar sesi lain berakhir saat kata sandi berubah.
- Login SSO hanya lewat `App\Services\Sso\OidcClient`: jangan melewati validasi ID token (tanda tangan JWKS, `iss`, `aud`, `exp`, `nonce`) dan jangan mempercayai klaim dari sumber lain. Akun tidak pernah dibuat otomatis dari IdP; penautan hanya lewat `SsoAccountLinker` (subject, atau email terverifikasi ke akun yang ada). Nilai `sso.mfa` di sesi hanya boleh diisi `SsoController`.

## A09 Logging dan Alerting

- Login, perubahan role/permission, dan transisi status artikel wajib tercatat. Jangan me-log data sensitif.

## A10 Exceptional Conditions

- Kegagalan layanan eksternal (Media Center, S3) harus terdegradasi dengan aman: tampilkan data lokal atau cache, catat ke log, dan jangan pernah menampilkan stack trace ke pengguna.
- Gagal berarti tertutup (fail closed): bila otorisasi atau validasi error, tolak permintaan.
