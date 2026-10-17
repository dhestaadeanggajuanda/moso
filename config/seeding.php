<?php

/*
|--------------------------------------------------------------------------
| Akun awal untuk seeder
|--------------------------------------------------------------------------
|
| Hanya diisi di .env lokal / saat instalasi pertama. Kosongkan lagi setelah
| akun dibuat, terutama sebelum menjalankan `php artisan config:cache` di
| produksi. Bila password kosong, SuperAdminSeeder membuat password acak
| dan menampilkannya sekali di terminal.
|
*/

return [

    'super_admin' => [
        'name' => env('SEED_SUPERADMIN_NAME', 'Super Admin'),
        'email' => env('SEED_SUPERADMIN_EMAIL'),
        'password' => env('SEED_SUPERADMIN_PASSWORD'),
    ],

    // Password akun demo (Approver/Editor/Kontributor) untuk DemoContentSeeder lokal.
    'demo_password' => env('SEED_DEMO_PASSWORD'),

];
