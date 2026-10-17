<?php

use App\Enums\MenuLocation;
use App\Models\ESakip;
use App\Models\Menu;
use App\Models\Page;
use App\Models\PublicDocument;
use App\Models\Service;

describe('services', function () {
    it('lists only published services in display order without internal fields', function () {
        Service::factory()->published()->create(['name' => 'Bantuan Sosial', 'sort_order' => 2]);
        Service::factory()->published()->create(['name' => 'Akta Kelahiran', 'sort_order' => 1]);
        Service::factory()->create(['name' => 'Draf Layanan']);

        $this->getJson(route('api.v1.services.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Akta Kelahiran')
            ->assertJsonPath('data.1.name', 'Bantuan Sosial')
            ->assertJsonMissingPath('data.0.is_published')
            ->assertJsonMissingPath('data.0.id');
    });

    it('returns 404 for an unpublished service', function () {
        $service = Service::factory()->create();

        $this->getJson(route('api.v1.services.show', $service->slug))->assertNotFound();
    });
});

describe('ppid', function () {
    it('filters published documents and never exposes the storage path', function () {
        PublicDocument::factory()->published()->create(['category' => 'berkala', 'year' => 2025, 'file_path' => 'ppid/rahasia.pdf']);
        PublicDocument::factory()->published()->create(['category' => 'serta_merta', 'year' => 2025]);
        PublicDocument::factory()->create(['category' => 'berkala', 'year' => 2025]);

        $response = $this->getJson(route('api.v1.ppid.index', ['category' => 'berkala']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category.label', 'Informasi Berkala')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.file_path');

        expect($response->json('data.0.download_url'))->toContain('/unduh/ppid/')
            ->and($response->getContent())->not->toContain('rahasia.pdf');
    });

    it('rejects an unknown category', function () {
        $this->getJson(route('api.v1.ppid.index', ['category' => 'dikecualikan']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    });
});

describe('sakip', function () {
    it('filters published documents by year and type', function () {
        ESakip::factory()->published()->create(['year' => 2025, 'document_type' => 'lkjip']);
        ESakip::factory()->published()->create(['year' => 2024, 'document_type' => 'lkjip']);
        ESakip::factory()->published()->create(['year' => 2025, 'document_type' => 'renstra']);
        ESakip::factory()->create(['year' => 2025, 'document_type' => 'lkjip']);

        $this->getJson(route('api.v1.sakip.index', ['year' => 2025, 'type' => 'lkjip']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.document_type.value', 'lkjip')
            ->assertJsonMissingPath('data.0.file_path_s3');
    });
});

describe('menus', function () {
    it('returns a nested public tree without inactive branches or draft pages', function () {
        $published = Page::factory()->published()->create(['slug' => 'profil']);
        $draft = Page::factory()->create();

        $profil = Menu::factory()->create(['label' => 'Profil', 'page_id' => $published->id, 'url' => null, 'sort_order' => 1]);
        Menu::factory()->childOf($profil)->create(['label' => 'Visi Misi', 'url' => '/visi-misi']);
        Menu::factory()->create(['label' => 'Draf', 'page_id' => $draft->id, 'url' => null, 'sort_order' => 2]);
        $hidden = Menu::factory()->inactive()->create(['label' => 'Tersembunyi', 'sort_order' => 3]);
        Menu::factory()->childOf($hidden)->create(['label' => 'Anak tersembunyi']);
        Menu::factory()->create(['label' => 'Footer saja', 'location' => MenuLocation::Footer]);

        $this->getJson(route('api.v1.menus.show', 'header'))
            ->assertOk()
            ->assertExactJson(['data' => [[
                'label' => 'Profil',
                'href' => '/profil',
                'open_in_new_tab' => false,
                'children' => [[
                    'label' => 'Visi Misi',
                    'href' => '/visi-misi',
                    'open_in_new_tab' => false,
                    'children' => [],
                ]],
            ]]]);
    });

    it('returns 404 for an unknown location', function () {
        $this->getJson('/api/v1/menus/sidebar')->assertNotFound();
    });
});

it('sends cache, etag, and rate limit headers and honours if-none-match', function () {
    Service::factory()->published()->create();

    $response = $this->getJson(route('api.v1.services.index'))
        ->assertOk()
        ->assertHeader('X-RateLimit-Limit', '60');

    expect($response->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=300');

    $etag = $response->headers->get('ETag');
    expect($etag)->not->toBeEmpty();

    $this->getJson(route('api.v1.services.index'), ['If-None-Match' => $etag])->assertStatus(304);
});
