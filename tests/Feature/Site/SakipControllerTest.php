<?php

use App\Enums\SakipDocumentType;
use App\Models\ESakip;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('groups published documents by year in the SAKIP cycle order without storage paths', function () {
    ESakip::factory()->published()->create(['year' => 2025, 'document_type' => SakipDocumentType::Lkjip, 'document_title' => 'LKjIP 2025']);
    ESakip::factory()->published()->create(['year' => 2025, 'document_type' => SakipDocumentType::Renstra, 'document_title' => 'Renstra 2025-2029']);
    ESakip::factory()->published()->create([
        'year' => 2026,
        'document_type' => SakipDocumentType::PerjanjianKinerja,
        'document_title' => 'Perjanjian Kinerja 2026',
        'file_path_s3' => 'sakip/2026/10/berkas-privat.pdf',
    ]);
    ESakip::factory()->create(['year' => 2024, 'document_title' => 'Draf 2024']);

    $sakip = ESakip::query()->where('document_title', 'Perjanjian Kinerja 2026')->sole();

    $this->get(route('site.sakip.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Sakip')
            ->where('total', 3)
            ->where('groups.0.year', 2026)
            ->where('groups.0.documents.0.title', 'Perjanjian Kinerja 2026')
            ->where('groups.0.documents.0.download_url', route('documents.sakip.download', $sakip))
            ->missing('groups.0.documents.0.file_path_s3')
            ->where('groups.1.year', 2025)
            ->where('groups.1.documents.0.title', 'Renstra 2025-2029')
            ->where('groups.1.documents.1.title', 'LKjIP 2025')
            ->where('yearOptions', [2026, 2025]))
        ->assertDontSee('berkas-privat')
        ->assertDontSee('Draf 2024');
});

it('filters by year and document type', function () {
    ESakip::factory()->published()->create(['year' => 2025, 'document_type' => SakipDocumentType::Lkjip, 'document_title' => 'LKjIP 2025']);
    ESakip::factory()->published()->create(['year' => 2025, 'document_type' => SakipDocumentType::Iku]);
    ESakip::factory()->published()->create(['year' => 2026, 'document_type' => SakipDocumentType::Lkjip]);

    $this->get(route('site.sakip.index', ['tahun' => 2025, 'jenis' => 'lkjip']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['year' => 2025, 'type' => 'lkjip'])
            ->where('total', 1)
            ->has('groups', 1)
            ->where('groups.0.documents.0.title', 'LKjIP 2025'));
});

it('rejects invalid filters', function () {
    $this->get(route('site.sakip.index', ['tahun' => 'semua', 'jenis' => 'anggaran']))
        ->assertInvalid(['tahun', 'jenis']);
});
