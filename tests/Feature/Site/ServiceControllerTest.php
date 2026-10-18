<?php

use App\Models\Service;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('lists only published services in display order', function () {
    Service::factory()->published()->create(['name' => 'Rekomendasi Bantuan Sosial', 'sort_order' => 2]);
    Service::factory()->published()->create(['name' => 'Surat Keterangan Tidak Mampu', 'sort_order' => 1]);
    Service::factory()->create(['name' => 'Layanan Draf']);

    $this->get(route('site.services.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Services')
            ->where('services.total', 2)
            ->where('services.data.0.name', 'Surat Keterangan Tidak Mampu')
            ->where('services.data.0.url', route('site.services.show', 'surat-keterangan-tidak-mampu'))
            ->where('services.data.1.name', 'Rekomendasi Bantuan Sosial'));
});

it('searches service names literally', function () {
    Service::factory()->published()->create(['name' => 'Surat Keterangan Tidak Mampu']);
    Service::factory()->published()->create(['name' => 'Diskon 100% Retribusi']);
    Service::factory()->published()->create(['name' => 'Pendataan DTKS']);

    $this->get(route('site.services.index', ['q' => 'keterangan']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('search', 'keterangan')
            ->where('services.total', 1)
            ->where('services.data.0.name', 'Surat Keterangan Tidak Mampu'));

    // "%" dari pengguna tidak boleh menjadi wildcard yang mencocokkan semua layanan.
    $this->get(route('site.services.index', ['q' => '%']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('services.total', 1)
            ->where('services.data.0.name', 'Diskon 100% Retribusi'));
});

it('rejects overly long search terms', function () {
    $this->get(route('site.services.index', ['q' => str_repeat('a', 101)]))
        ->assertInvalid(['q']);
});

it('marks services without a fee as free', function (string $cost, bool $isFree) {
    Service::factory()->published()->create(['cost' => $cost]);

    $this->get(route('site.services.index'))
        ->assertInertia(fn (Assert $page) => $page->where('services.data.0.is_free', $isFree));
})->with([
    ['Gratis', true],
    ['Rp 0', true],
    ['Tidak dipungut biaya', true],
    ['Rp 50.000', false],
]);

it('shows the standard of service for a published service', function () {
    $service = Service::factory()->published()->create([
        'name' => 'Surat Keterangan Tidak Mampu',
        'requirements' => ['Fotokopi KTP'],
        'procedure' => ['Isi formulir', 'Verifikasi berkas'],
    ]);

    $this->get(route('site.services.show', $service))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Service')
            ->where('service.name', 'Surat Keterangan Tidak Mampu')
            ->where('service.requirements', ['Fotokopi KTP'])
            ->where('service.procedure', ['Isi formulir', 'Verifikasi berkas'])
            ->where('index_url', route('site.services.index')));
});

it('hides unpublished services from the public', function () {
    $service = Service::factory()->create();

    $this->get(route('site.services.show', $service))->assertNotFound();
});
