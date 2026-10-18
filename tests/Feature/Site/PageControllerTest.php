<?php

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the configured home page with hydrated blocks in the active theme', function () {
    app(Settings::class)->put('site.home_page', 'beranda-dinas');
    Page::factory()->published()->create([
        'title' => 'Beranda Dinas',
        'slug' => 'beranda-dinas',
        'content_blocks' => [
            ['id' => (string) Str::uuid(), 'type' => 'HeroBanner', 'props' => ['heading' => 'Layanan Dinas Sosial']],
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Show')
            ->where('page.title', 'Beranda Dinas')
            ->where('theme', 'TemaModern')
            ->where('blocks.0.props.heading', 'Layanan Dinas Sosial')
            ->where('blocks.0.data.search_url', route('site.services.index'))
            ->where('site.links.services', route('site.services.index')));
});

it('still renders an empty home page when the home page is not published', function () {
    Page::factory()->create(['slug' => 'beranda']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Show')
            ->where('page', null)
            ->where('blocks', []));
});

it('shows a published page by its slug', function () {
    $page = Page::factory()->published()->create(['title' => 'Profil Dinas']);

    $this->get(route('site.pages.show', $page))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('site/Show')
            ->where('page.title', 'Profil Dinas'));
});

it('hides draft pages from the public', function () {
    $page = Page::factory()->create();

    $this->get(route('site.pages.show', $page))->assertNotFound();
});

it('uses the page theme only when that theme is compiled', function (?string $pageTheme, string $expected) {
    config(['themes.enabled' => ['TemaModern', 'TemaKlasik'], 'themes.default' => 'TemaModern']);
    $page = Page::factory()->published()->create(['theme' => $pageTheme]);

    $this->get(route('site.pages.show', $page))
        ->assertInertia(fn (Assert $inertia) => $inertia->where('theme', $expected));
})->with([
    'compiled theme' => ['TemaKlasik', 'TemaKlasik'],
    'theme not compiled' => ['TemaHilang', 'TemaModern'],
    'no page theme' => [null, 'TemaModern'],
]);
