<?php

/*
|--------------------------------------------------------------------------
| Header keamanan HTTP (OWASP A02 Security Misconfiguration, A05 Injection)
|--------------------------------------------------------------------------
|
| Dipakai oleh App\Http\Middleware\SecurityHeaders. Origin bucket S3 publik
| (AWS_URL) otomatis diizinkan untuk gambar dan media. Origin lain, misalnya
| host gambar Media Center, ditambahkan lewat variabel CSP_* (pisahkan
| dengan koma).
|
*/

$origins = fn (string $key): array => array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env($key, '')),
)));

return [

    'csp' => [
        'enabled' => (bool) env('CSP_ENABLED', true),

        // true = hanya melaporkan pelanggaran ke console browser, tidak memblokir.
        'report_only' => (bool) env('CSP_REPORT_ONLY', false),

        'img_src' => $origins('CSP_IMG_SRC'),
        'media_src' => $origins('CSP_MEDIA_SRC'),
        'connect_src' => $origins('CSP_CONNECT_SRC'),
        'frame_src' => $origins('CSP_FRAME_SRC'),
    ],

    'two_factor' => [
        // Role yang wajib 2FA sebelum mengakses panel admin.
        'enforce' => (bool) env('SECURITY_ENFORCE_2FA', true),
        'roles' => ['Super Admin', 'Approver'],
    ],

    'hsts' => [
        // Hanya dikirim pada request HTTPS.
        'enabled' => (bool) env('HSTS_ENABLED', env('APP_ENV') === 'production'),
        'max_age' => (int) env('HSTS_MAX_AGE', 31536000),
    ],

    // Host yang dilayani selain host APP_URL (cocok persis, tanpa subdomain).
    // Request dengan header Host lain ditolak, agar tautan reset kata sandi
    // tidak bisa diarahkan ke domain penyerang (App\Support\TrustedHosts).
    'trusted_hosts' => $origins('TRUSTED_HOSTS'),

];
