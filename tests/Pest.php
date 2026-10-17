<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Membuat pengguna dengan satu role. Role & permission di-seed bila belum ada.
 *
 * @param  array<string, mixed>  $attributes
 */
function userWithRole(RoleName $role, array $attributes = []): User
{
    if (Role::query()->doesntExist()) {
        (new RolesAndPermissionsSeeder)->run();
    }

    return User::factory()->create($attributes)->assignRole($role);
}

/**
 * Berkas PDF minimal yang lolos validasi MIME dan tanda tangan "%PDF-".
 */
function fakePdf(string $name = 'dokumen.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
}

/**
 * Palsukan disk privat dan URL bertanda tangannya agar tujuan redirect bisa diperiksa.
 */
function fakePrivateDisk(): void
{
    Storage::fake('s3_private');

    Storage::disk('s3_private')->buildTemporaryUrlsUsing(
        fn (string $path, DateTimeInterface $expiration, array $options) => 'https://signed.test/'.$path
            .'?disposition='.rawurlencode((string) ($options['ResponseContentDisposition'] ?? '')),
    );
}
