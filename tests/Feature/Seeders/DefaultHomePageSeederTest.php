<?php

use App\Enums\MenuLocation;
use App\Enums\PageStatus;
use App\Models\Menu;
use App\Models\Page;
use Database\Seeders\DefaultHomePageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('gives a new website a published home page and a basic header menu', function () {
    $this->seed(DefaultHomePageSeeder::class);

    $beranda = Page::query()->where('slug', 'beranda')->sole();

    expect($beranda->status)->toBe(PageStatus::Published)
        ->and(array_column($beranda->content_blocks, 'type'))
        ->toBe(['HeroBanner', 'AksesCepat', 'LayananList', 'NewsGridHybrid', 'AgendaKegiatan'])
        ->and(Menu::query()->where('location', MenuLocation::Header)->orderBy('sort_order')->pluck('label')->all())
        ->toBe(['Beranda', 'Berita', 'Layanan', 'Informasi Publik', 'E-SAKIP']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('blocks', 5));
});

it('never replaces a home page or menu the admin already arranged', function () {
    $beranda = Page::factory()->published()->create([
        'slug' => 'beranda',
        'content_blocks' => [],
    ]);
    Menu::factory()->create(['label' => 'Profil']);

    $this->seed(DefaultHomePageSeeder::class);
    $this->seed(DefaultHomePageSeeder::class);

    expect($beranda->refresh()->content_blocks)->toBe([])
        ->and(Page::query()->where('slug', 'beranda')->count())->toBe(1)
        ->and(Menu::query()->pluck('label')->all())->toBe(['Profil']);
});
