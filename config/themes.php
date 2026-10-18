<?php

/*
|--------------------------------------------------------------------------
| Pluggable Themes
|--------------------------------------------------------------------------
|
| Harus sama dengan yang dikompilasi Vite (vite/plugins/themes.ts membaca
| variabel yang sama). Tema pertama menjadi bawaan. Mengganti daftar ini
| membutuhkan `npm run build` ulang.
|
*/

$enabled = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('VITE_THEMES', 'TemaModern')),
), fn (string $name) => preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) === 1));

return [

    'enabled' => $enabled !== [] ? $enabled : ['TemaModern'],

    'default' => $enabled[0] ?? 'TemaModern',

    /*
    | Alias font per tema, sesuai opsi `fonts` di vite.config.ts. Dirender
    | lewat @fonts hanya di halaman situs yang memakai tema tersebut.
    */
    'fonts' => [
        'TemaModern' => ['jakarta'],
    ],

];
