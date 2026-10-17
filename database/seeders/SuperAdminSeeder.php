<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('seeding.super_admin.email');

        if (! is_string($email) || $email === '') {
            $this->command->warn('SEED_SUPERADMIN_EMAIL kosong: Super Admin tidak dibuat.');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            User::query()->where('email', $email)->firstOrFail()->assignRole(RoleName::SuperAdmin);

            return;
        }

        $password = config('seeding.super_admin.password');
        $generated = ! is_string($password) || $password === '';

        if ($generated) {
            $password = Str::password(20);
        }

        $user = User::query()->forceCreate([
            'name' => config('seeding.super_admin.name'),
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $user->assignRole(RoleName::SuperAdmin);

        if ($generated) {
            $this->command->warn("Super Admin {$email} dibuat dengan password acak (catat sekarang, tidak ditampilkan lagi): {$password}");
        }
    }
}
