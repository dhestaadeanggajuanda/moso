<?php

/*
|--------------------------------------------------------------------------
| Asisten penulis berita (moso-agentic)
|--------------------------------------------------------------------------
|
| Service pusat yang menyusun draf berita atau liputan kegiatan dari judul,
| foto, dan deskripsi pendek. Moso memanggilnya lewat App\Services\Agentic\
| AgenticClient dengan token per dinas. Permintaan keluar hanya pernah
| dikirim ke base_url ini (anti-SSRF, OWASP A01): moso mengirim byte foto,
| bukan URL, sehingga agentic tidak pernah mengambil alamat dari luar.
|
*/

return [

    // false = tombol "Tulis dengan AI", halaman Asisten AI, dan sinkron memori dimatikan.
    'enabled' => (bool) env('AGENTIC_ENABLED', false),

    // Alamat service tanpa /api/v1, mis. http://172.16.20.30:8080 (jaringan privat) atau https://agentic.example.go.id
    'base_url' => rtrim((string) env('AGENTIC_URL', ''), '/'),

    // Token dinas dari `php artisan tenant:create` di moso-agentic. Jangan pernah di-log.
    'token' => env('AGENTIC_TOKEN'),

    'connect_timeout' => (int) env('AGENTIC_CONNECT_TIMEOUT', 3),

    // Batas tunggu per permintaan (detik). Pembuatan draf berjalan asinkron di
    // agentic, jadi permintaan dari moso selalu singkat (Octane membatasi 30 detik).
    'timeout' => (int) env('AGENTIC_TIMEOUT', 15),

];
