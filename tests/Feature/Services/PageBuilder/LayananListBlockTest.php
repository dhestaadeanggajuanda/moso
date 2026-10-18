<?php

use App\Models\Service;
use App\Services\PageBuilder\Blocks\LayananListBlock;

it('lists published services up to the limit with the total for the "see all" link', function () {
    Service::factory()->published()->create(['name' => 'Pendataan DTKS', 'sort_order' => 1]);
    Service::factory()->published()->create(['name' => 'Surat Keterangan Tidak Mampu', 'sort_order' => 2]);
    Service::factory()->published()->create(['name' => 'Rehabilitasi Sosial', 'sort_order' => 3]);
    Service::factory()->create(['name' => 'Layanan Draf']);

    $data = app(LayananListBlock::class)->hydrate(['limit' => 2]);

    expect(array_column($data['items'], 'name'))->toBe(['Pendataan DTKS', 'Surat Keterangan Tidak Mampu'])
        ->and($data['total'])->toBe(3)
        ->and($data['index_url'])->toBe(route('site.services.index'));
});
