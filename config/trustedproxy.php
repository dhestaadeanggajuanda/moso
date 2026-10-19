<?php

/*
|--------------------------------------------------------------------------
| Proxy tepercaya (OWASP A02 Security Misconfiguration)
|--------------------------------------------------------------------------
|
| Dibaca oleh middleware TrustProxies bawaan Laravel pada setiap request.
| Isi dengan IP atau CIDR reverse proxy / load balancer yang mengakhiri TLS,
| dipisah koma (mis. "10.0.0.5,172.16.0.0/12"). Tanpa ini, aplikasi di
| belakang proxy mengira semua request adalah HTTP (HSTS tidak terkirim) dan
| semua pengunjung berbagi satu IP di rate limiter.
|
| Kosong (bawaan) berarti header X-Forwarded-* tidak dipercaya sama sekali.
| Pakai "*" hanya bila aplikasi tidak bisa diakses langsung selain lewat proxy.
|
*/

return [

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
