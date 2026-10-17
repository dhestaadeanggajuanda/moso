<?php

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Page;
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
            ->has('catalog', 4)
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
