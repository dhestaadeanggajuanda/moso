<?php

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Page;
use App\Services\PageBuilder\Blocks\HeroBannerBlock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $props
 * @return array{id: string, type: string, props: array<string, mixed>}
 */
function block(string $type, array $props = []): array
{
    return ['id' => (string) Str::uuid(), 'type' => $type, 'props' => $props];
}

/**
 * @return array<string, mixed>
 */
function newsGridProps(array $overrides = []): array
{
    return [
        'title' => 'Berita Sosial',
        'limit' => 6,
        'columns' => 3,
        'local_category' => null,
        'fetch_media_center' => true,
        'media_center_keyword' => 'sosial',
        'media_center_mode' => 'tag',
        ...$overrides,
    ];
}

it('forbids a kontributor from using the page builder', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->get(route('admin.pages.builder', $page))
        ->assertForbidden();
});

it('shows the builder with the registered block catalog', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->get(route('admin.pages.builder', $page))
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('admin/pages/Builder')
            ->has('catalog', 6)
            ->where('catalog.1.type', 'NewsGridHybrid')
            ->where('catalog.1.defaults.fetch_media_center', false));
});

it('stores a NewsGridHybrid block with the media center flags and strips unknown props', function () {
    $page = Page::factory()->create();
    Category::factory()->create(['name' => 'Sosial']);
    $grid = block('NewsGridHybrid', newsGridProps(['local_category' => 'sosial', 'api_url' => 'https://evil.test']));

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), ['blocks' => [$grid]])
        ->assertRedirect(route('admin.pages.builder', $page));

    expect($page->fresh()->content_blocks)->toBe([[
        'id' => $grid['id'],
        'type' => 'NewsGridHybrid',
        'props' => newsGridProps(['local_category' => 'sosial']),
    ]]);
});

it('rejects unregistered block types', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), ['blocks' => [block('IframeEmbed', ['src' => 'https://evil.test'])]])
        ->assertInvalid(['blocks.0.type' => 'Tipe blok tidak dikenal.']);

    expect($page->fresh()->content_blocks)->toBe([]);
});

it('rejects media center keywords that are not plain slugs', function (string $keyword) {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), [
            'blocks' => [block('NewsGridHybrid', newsGridProps(['media_center_keyword' => $keyword]))],
        ])
        ->assertInvalid(['blocks.0.props.media_center_keyword' => 'Kata kunci hanya boleh huruf kecil']);
})->with(['../wp-admin', 'sosial?per_page=100', 'https://evil.test', 'SOSIAL']);

it('requires a keyword when the media center is enabled', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), [
            'blocks' => [block('NewsGridHybrid', newsGridProps(['media_center_keyword' => null]))],
        ])
        ->assertInvalid(['blocks.0.props.media_center_keyword' => 'Isi kata kunci Media Center']);
});

it('rejects unsafe call to action links', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), [
            'blocks' => [block('HeroBanner', [
                'heading' => 'Halo', 'subheading' => null, 'variant' => 'primary',
                'cta_label' => 'Klik', 'cta_url' => 'javascript:alert(1)',
            ])],
        ])
        ->assertInvalid(['blocks.0.props.cta_url' => 'URL harus diawali "/"']);
});

it('sanitizes rich text before saving', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), [
            'blocks' => [block('RichText', ['html' => '<p onclick="x()">Halo</p><script>alert(1)</script>'])],
        ])
        ->assertRedirect();

    expect($page->fresh()->content_blocks[0]['props']['html'])->toBe('<p>Halo</p>');
});

it('rejects duplicate block ids and too many blocks', function () {
    $page = Page::factory()->create();
    $text = block('RichText', ['html' => 'x']);

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), ['blocks' => [$text, $text]])
        ->assertInvalid(['blocks.0.id']);

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), [
            'blocks' => array_map(fn () => block('RichText', ['html' => 'x']), range(1, 51)),
        ])
        ->assertInvalid(['blocks' => 'maksimal 50 blok']);
});

it('uploads a banner photo as a re-encoded webp on the public disk', function () {
    Storage::fake('s3');
    $page = Page::factory()->create();

    $response = $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.pages.builder.images', $page), [
            'image' => UploadedFile::fake()->image('kegiatan.jpg', 1200, 800),
        ])
        ->assertCreated();

    $path = $response->json('path');
    expect($path)->toMatch(HeroBannerBlock::IMAGE_PATH_PATTERN);
    Storage::disk('s3')->assertExists($path);
});

it('forbids a kontributor from uploading builder photos', function () {
    Storage::fake('s3');
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->post(route('admin.pages.builder.images', $page), [
            'image' => UploadedFile::fake()->image('kegiatan.jpg', 1200, 800),
        ])
        ->assertForbidden();

    expect(Storage::disk('s3')->allFiles())->toBe([]);
});

it('rejects builder uploads that are not photos', function () {
    Storage::fake('s3');
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->postJson(route('admin.pages.builder.images', $page), [
            'image' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);

    expect(Storage::disk('s3')->allFiles())->toBe([]);
});

it('rejects a banner photo path that the uploader did not produce', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), ['blocks' => [
            block('HeroBanner', ['heading' => 'Halo', 'variant' => 'primary', 'show_search' => true, 'image_path' => 'private/sakip/laporan.pdf']),
        ]])
        ->assertInvalid(['blocks.0.props.image_path' => 'Foto banner tidak valid. Unggah ulang fotonya.']);
});

it('stores quick access links with only their label, url, and icon', function () {
    $page = Page::factory()->create();
    $links = block('AksesCepat', ['title' => null, 'items' => [
        ['label' => 'PPID', 'url' => '/ppid', 'icon' => 'dokumen', 'onclick' => 'alert(1)'],
        ['label' => 'Pengaduan', 'url' => 'https://www.lapor.go.id', 'icon' => 'pengaduan'],
    ]]);

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), ['blocks' => [$links]])
        ->assertRedirect(route('admin.pages.builder', $page));

    expect($page->fresh()->content_blocks[0]['props']['items'])->toBe([
        ['label' => 'PPID', 'url' => '/ppid', 'icon' => 'dokumen'],
        ['label' => 'Pengaduan', 'url' => 'https://www.lapor.go.id', 'icon' => 'pengaduan'],
    ]);
});

it('rejects quick access links with unsafe urls or unknown icons', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.blocks.update', $page), ['blocks' => [
            block('AksesCepat', ['title' => null, 'items' => [
                ['label' => 'Klik', 'url' => 'javascript:alert(1)', 'icon' => 'roket'],
            ]]),
        ]])
        ->assertInvalid([
            'blocks.0.props.items.0.url' => 'URL harus diawali',
            'blocks.0.props.items.0.icon',
        ]);

    expect($page->fresh()->content_blocks)->toBe([]);
});
