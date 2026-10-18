<?php

use App\Enums\PublicDocumentCategory;
use App\Models\PublicDocument;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('lists only published documents with a download route instead of the storage path', function () {
    PublicDocument::factory()->published()->create([
        'title' => 'Laporan Keuangan 2025',
        'category' => PublicDocumentCategory::Berkala,
        'year' => 2025,
        'file_path' => 'ppid/2026/10/rahasia-berkas.pdf',
    ]);
    $draft = PublicDocument::factory()->create(['title' => 'Draf Belum Terbit']);

    $this->get(route('site.ppid.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Ppid')
            ->where('total', 1)
            ->where('documents.total', 1)
            ->where('documents.data.0.title', 'Laporan Keuangan 2025')
            ->where('documents.data.0.category.label', 'Informasi Berkala')
            ->where('documents.data.0.download_url', route('documents.ppid.download', 'laporan-keuangan-2025'))
            ->missing('documents.data.0.file_path')
            ->where('site.links.ppid', route('site.ppid.index')))
        // Data halaman di-encode JSON dengan garis miring di-escape, jadi yang dicari potongan tanpa "/".
        ->assertDontSee('rahasia-berkas')
        ->assertDontSee($draft->title);
});

it('filters by category and counts published documents per category', function () {
    PublicDocument::factory()->published()->count(2)->create(['category' => PublicDocumentCategory::Berkala]);
    PublicDocument::factory()->published()->create(['category' => PublicDocumentCategory::SetiapSaat, 'title' => 'Daftar Informasi Publik']);
    PublicDocument::factory()->create(['category' => PublicDocumentCategory::SetiapSaat]);

    $this->get(route('site.ppid.index', ['kategori' => 'setiap_saat']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('category', 'setiap_saat')
            ->where('documents.total', 1)
            ->where('documents.data.0.title', 'Daftar Informasi Publik')
            ->where('total', 3)
            ->where('categories.0.value', 'berkala')
            ->where('categories.0.count', 2)
            ->where('categories.1.count', 0)
            ->where('categories.2.count', 1)
            ->where('categories.2.url', route('site.ppid.index', ['kategori' => 'setiap_saat'])));
});

it('rejects categories outside the UU 14/2008 classification', function () {
    $this->get(route('site.ppid.index', ['kategori' => 'rahasia']))
        ->assertInvalid(['kategori']);
});
