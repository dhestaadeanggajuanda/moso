<?php

use App\Services\PageBuilder\Blocks\HeroSliderBlock;

it('links each slide photo from the public disk in slide order and skips foreign paths', function () {
    config(['filesystems.disks.s3.url' => 'https://media.example.go.id/moso-public']);
    $path = 'page-builder/2026/10/0b9f2c4e-3c1d-4f7a-9a51-2f6c3b8d7e10.webp';

    $data = app(HeroSliderBlock::class)->hydrate(['slides' => [
        ['heading' => 'Satu', 'image_path' => $path],
        ['heading' => 'Dua'],
        ['heading' => 'Tiga', 'image_path' => 'private/sakip/laporan.pdf'],
    ]]);

    expect($data)->toBe([
        'images' => ["https://media.example.go.id/moso-public/{$path}", null, null],
        'search_url' => route('site.services.index'),
    ]);
});

it('keeps only known slide fields and at most five slides', function () {
    $slides = array_map(fn (int $number) => ['heading' => " Slide {$number} ", 'onclick' => 'alert(1)'], range(1, 7));

    $props = app(HeroSliderBlock::class)->normalize(['slides' => $slides, 'autoplay' => '1', 'interval' => '9']);

    expect($props['slides'])->toHaveCount(5)
        ->and($props['slides'][0])->toBe([
            'heading' => 'Slide 1',
            'subheading' => null,
            'cta_label' => null,
            'cta_url' => null,
            'image_path' => null,
            'image_alt' => null,
        ])
        ->and($props['autoplay'])->toBeTrue()
        ->and($props['interval'])->toBe(9);
});
