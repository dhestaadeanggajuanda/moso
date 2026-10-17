<?php

use App\Enums\RoleName;
use App\Models\PublicDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    fakePrivateDisk();
});

it('forbids a kontributor from managing ppid documents', function () {
    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->get(route('admin.ppid.index'))
        ->assertForbidden();
});

it('stores the pdf on the private disk and stamps published_at', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.ppid.store'), [
            'title' => 'Laporan Keuangan Dinas 2025',
            'category' => 'berkala',
            'year' => 2025,
            'file' => fakePdf('laporan asli (final).pdf'),
            'is_published' => '1',
            // Kunci yang tidak boleh dikendalikan pengguna:
            'file_path' => '../../.env',
        ])
        ->assertRedirect(route('admin.ppid.index'));

    $document = PublicDocument::query()->sole();

    expect($document->file_path)->toMatch('#^ppid/\d{4}/\d{2}/[0-9a-f-]{36}\.pdf$#')
        ->and($document->file_size)->toBeGreaterThan(0)
        ->and($document->published_at)->not->toBeNull()
        ->and($document->slug)->toBe('laporan-keuangan-dinas-2025');
    Storage::disk('s3_private')->assertExists($document->file_path);
});

it('rejects a file without a pdf signature even when named .pdf', function () {
    $disguised = UploadedFile::fake()->createWithContent('laporan.pdf', '<html><script>alert(1)</script></html>');

    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.ppid.store'), [
            'title' => 'Dokumen palsu',
            'category' => 'berkala',
            'file' => $disguised,
        ])
        ->assertInvalid(['file' => 'Berkas harus berupa dokumen PDF yang valid.']);

    expect(PublicDocument::query()->count())->toBe(0);
    expect(Storage::disk('s3_private')->allFiles())->toBeEmpty();
});

it('requires a file when creating', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.ppid.store'), ['title' => 'Tanpa berkas', 'category' => 'berkala'])
        ->assertInvalid(['file']);
});

it('replaces the stored file and removes the old one', function () {
    Storage::disk('s3_private')->put('ppid/lama.pdf', 'lama');
    $document = PublicDocument::factory()->create(['file_path' => 'ppid/lama.pdf']);

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.ppid.update', $document), [
            'title' => $document->title,
            'category' => $document->category->value,
            'file' => fakePdf(),
        ])
        ->assertRedirect(route('admin.ppid.index'));

    Storage::disk('s3_private')->assertMissing('ppid/lama.pdf');
    Storage::disk('s3_private')->assertExists($document->fresh()->file_path);
});

it('deletes the stored file together with the document', function () {
    Storage::disk('s3_private')->put('ppid/hapus.pdf', 'isi');
    $document = PublicDocument::factory()->create(['file_path' => 'ppid/hapus.pdf']);

    $this->actingAs(userWithRole(RoleName::Editor))
        ->delete(route('admin.ppid.destroy', $document))
        ->assertRedirect(route('admin.ppid.index'));

    $this->assertModelMissing($document);
    Storage::disk('s3_private')->assertMissing('ppid/hapus.pdf');
});

it('lets managers preview an unpublished file', function () {
    $document = PublicDocument::factory()->create(['file_path' => 'ppid/draf.pdf']);

    $this->actingAs(userWithRole(RoleName::Editor))
        ->get(route('admin.ppid.file', $document))
        ->assertRedirectContains('https://signed.test/ppid/draf.pdf');
});
