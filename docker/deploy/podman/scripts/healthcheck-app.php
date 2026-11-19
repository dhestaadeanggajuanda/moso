<?php

// Health check container app: GET /up dengan header Host = host APP_URL.
// TrustHosts menolak host lain (termasuk 127.0.0.1) dengan status 400.
$host = parse_url((string) getenv('APP_URL'), PHP_URL_HOST);
$context = stream_context_create(['http' => ['header' => "Host: {$host}", 'timeout' => 4, 'ignore_errors' => false]]);

exit(@file_get_contents('http://127.0.0.1:8000/up', false, $context) === false ? 1 : 0);
