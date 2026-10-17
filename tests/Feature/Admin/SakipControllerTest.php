<?php

use App\Enums\RoleName;
use App\Enums\SakipDocumentType;
use App\Models\ESakip;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    fakePrivateDisk();
});

it('forbids a kontributor from managing sakip documents', function () {
    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->post(route('admin.sakip.store'), [
            'year' => 2025,
            'document_type' => 'lkjip',
            'document_title' => 'LKjIP 2025',
            'file' => fakePdf(),
        ])
        ->assertForbidden();

    expect(ESakip::query()->count())->toBe(0);
});

it('stores a sakip document in the e_sakip table with its file path', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.sakip.store'), [
            'year' => 2025,
            'document_type' => 'lkjip',
            'document_title' => 'Laporan Kinerja Tahun 2025',
            'file' => fakePdf(),
            'is_published' => '1',
        ])
        ->assertRedirect(route('admin.sakip.index'));

    $sakip = ESakip::query()->sole();

    expect($sakip->getTable())->toBe('e_sakip')
        ->and($sakip->document_type)->toBe(SakipDocumentType::Lkjip)
        ->and($sakip->file_path_s3)->toStartWith('sakip/');
    Storage::disk('s3_private')->assertExists($sakip->file_path_s3);
});

it('rejects an unknown document type', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.sakip.store'), [
            'year' => 2025,
            'document_type' => 'laporan_lain',
            'document_title' => 'Dokumen',
            'file' => fakePdf(),
        ])
        ->assertInvalid(['document_type']);
});
