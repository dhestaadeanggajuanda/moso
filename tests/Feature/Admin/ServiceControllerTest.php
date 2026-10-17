<?php

use App\Enums\RoleName;
use App\Models\Service;

it('forbids a kontributor from managing services', function () {
    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->get(route('admin.services.index'))
        ->assertForbidden();
});

it('turns textarea lines into requirement and procedure lists', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.services.store'), [
            'name' => 'Surat Keterangan Tidak Mampu',
            'requirements' => "  Fotokopi KTP \n\nFotokopi KK\r\nSurat pengantar RT ",
            'procedure' => "Isi formulir\nVerifikasi berkas",
            'processing_time' => '1 hari kerja',
            'cost' => 'Gratis',
            'sort_order' => '',
            'is_published' => '1',
        ])
        ->assertRedirect(route('admin.services.index'));

    $service = Service::query()->sole();

    expect($service->requirements)->toBe(['Fotokopi KTP', 'Fotokopi KK', 'Surat pengantar RT'])
        ->and($service->procedure)->toBe(['Isi formulir', 'Verifikasi berkas'])
        ->and($service->sort_order)->toBe(0)
        ->and($service->slug)->toBe('surat-keterangan-tidak-mampu');
});

it('requires at least one requirement and one procedure step', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.services.store'), [
            'name' => 'Layanan',
            'requirements' => " \n ",
            'procedure' => '',
            'processing_time' => '1 hari',
            'cost' => 'Gratis',
        ])
        ->assertInvalid(['requirements', 'procedure']);
});
