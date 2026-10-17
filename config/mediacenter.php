<?php

/*
|--------------------------------------------------------------------------
| Integrasi Media Center Kota Singkawang (WordPress REST API)
|--------------------------------------------------------------------------
|
| Permintaan keluar hanya pernah dikirim ke base_url ini (anti-SSRF, OWASP
| A01). Kata kunci dari blok Page Builder divalidasi dengan pola
| MediaCenterApiService::KEYWORD_PATTERN dan tidak pernah berupa URL.
|
*/

return [

    'base_url' => rtrim((string) env('MEDIA_CENTER_BASE_URL', 'https://mediacenter.singkawangkota.go.id'), '/'),

    'timeout' => (int) env('MEDIA_CENTER_TIMEOUT', 5),

    'connect_timeout' => (int) env('MEDIA_CENTER_CONNECT_TIMEOUT', 3),

    // Stale-while-revalidate: segar 10 menit, boleh disajikan basi sampai 1 jam
    // sambil diperbarui di latar belakang.
    'cache' => [
        'fresh' => (int) env('MEDIA_CENTER_CACHE_FRESH', 600),
        'stale' => (int) env('MEDIA_CENTER_CACHE_STALE', 3600),
    ],

    // Batas jumlah berita per permintaan.
    'max_items' => 12,

];
