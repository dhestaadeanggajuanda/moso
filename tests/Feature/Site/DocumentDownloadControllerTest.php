<?php

use App\Models\ESakip;
use App\Models\PublicDocument;

beforeEach(function () {
    fakePrivateDisk();
});

it('redirects a published ppid document to a signed inline url', function () {
    $document = PublicDocument::factory()->published()->create([
        'title' => 'Daftar Informasi Publik',
        'file_path' => 'ppid/2026/10/abc.pdf',
    ]);

    $this->get(route('documents.ppid.download', $document->slug))
        ->assertRedirect('https://signed.test/ppid/2026/10/abc.pdf?disposition='
            .rawurlencode('inline; filename="daftar-informasi-publik.pdf"'));
});

it('hides unpublished documents behind a 404', function () {
    $document = PublicDocument::factory()->create();
    $sakip = ESakip::factory()->create();

    $this->get(route('documents.ppid.download', $document->slug))->assertNotFound();
    $this->get(route('documents.sakip.download', $sakip))->assertNotFound();
});

it('redirects a published sakip document to a signed url', function () {
    $sakip = ESakip::factory()->published()->create([
        'year' => 2025,
        'document_type' => 'lkjip',
        'file_path_s3' => 'sakip/2026/10/xyz.pdf',
    ]);

    $this->get(route('documents.sakip.download', $sakip))
        ->assertRedirectContains('https://signed.test/sakip/2026/10/xyz.pdf')
        ->assertRedirectContains(rawurlencode('filename="sakip-2025-lkjip.pdf"'));
});
