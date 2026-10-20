<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
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

/**
 * Aktifkan asisten AI dengan host uji dan tolak permintaan yang tidak dipalsukan.
 */
function fakeAgenticHost(): void
{
    config([
        'agentic.enabled' => true,
        'agentic.base_url' => 'https://agentic.test',
        'agentic.token' => 'token-dinas-uji',
    ]);
    Http::preventStrayRequests();
    Sleep::fake();
}

/**
 * Nilai satu field multipart dari permintaan keluar yang direkam Http::fake().
 */
function multipartValue(Request $request, string $name): mixed
{
    $part = collect($request->data())->firstWhere('name', $name);

    return is_array($part) ? (string) $part['contents'] : null;
}

/**
 * Isi `data` dari GET /api/v1/drafts/{id} agentic untuk draf yang sudah selesai.
 *
 * @param  array<string, mixed>  $result
 * @return array<string, mixed>
 */
function agenticDoneDraft(string $id, array $result = []): array
{
    return [
        'id' => $id,
        'status' => 'done',
        'stage' => null,
        'error' => null,
        'result' => [
            'title_suggestion' => 'Posyandu Balita di Kelurahan Pasiran',
            'excerpt' => 'Puskesmas menggelar posyandu balita.',
            'body_html' => '<h2>Kegiatan</h2><p>Posyandu digelar pada <mark>[Perlu dikonfirmasi: tanggal]</mark>.</p>',
            'image_alt' => 'Petugas menimbang balita',
            'needs_confirmation' => [['item' => 'tanggal', 'question' => 'Kapan kegiatan berlangsung?']],
            'category_slugs' => [],
            'word_count' => 280,
            ...$result,
        ],
    ];
}

/**
 * Arahkan integrasi Media Center ke host uji dan tolak permintaan yang tidak dipalsukan.
 */
function fakeMediaCenterHost(): void
{
    config(['mediacenter.base_url' => 'https://mc.test']);
    Http::preventStrayRequests();
    Sleep::fake();
}

/**
 * Satu post WordPress seperti yang dikirim Media Center.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function wpPost(int $id, string $title, string $dateGmt, array $overrides = []): array
{
    return array_replace_recursive([
        'id' => $id,
        'date_gmt' => $dateGmt,
        'link' => "https://mc.test/berita/post-{$id}/",
        'title' => ['rendered' => $title],
        'excerpt' => ['rendered' => '<p>Singkawang, MC &#8211; Ringkasan berita.</p>'],
        '_embedded' => ['wp:featuredmedia' => [[
            'source_url' => "https://mc.test/wp-content/uploads/{$id}.jpg",
            'alt_text' => '',
            'media_details' => ['sizes' => ['medium' => ['source_url' => "https://mc.test/wp-content/uploads/{$id}-300.jpg"]]],
        ]]],
    ], $overrides);
}
