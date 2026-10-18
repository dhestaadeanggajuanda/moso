<?php

use App\Services\PageBuilder\Blocks\HeroBannerBlock;

it('links the banner photo from the public disk and the search to the services page', function () {
    config(['filesystems.disks.s3.url' => 'https://media.example.go.id/moso-public']);
    $path = 'page-builder/2026/10/0b9f2c4e-3c1d-4f7a-9a51-2f6c3b8d7e10.webp';

    $data = app(HeroBannerBlock::class)->hydrate(['heading' => 'Halo', 'image_path' => $path]);

    expect($data)->toBe([
        'image_url' => "https://media.example.go.id/moso-public/{$path}",
        'search_url' => route('site.services.index'),
    ]);
});

it('never links a stored path that the uploader did not produce', function () {
    $data = app(HeroBannerBlock::class)->hydrate(['heading' => 'Halo', 'image_path' => 'private/sakip/laporan.pdf']);

    expect($data['image_url'])->toBeNull();
});
