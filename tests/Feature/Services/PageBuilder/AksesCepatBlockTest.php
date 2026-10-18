<?php

use App\Services\PageBuilder\Blocks\AksesCepatBlock;

it('links its default tiles to public pages that exist, without repeating the banner search', function () {
    $this->withoutVite();

    $items = collect(app(AksesCepatBlock::class)->defaults()['items']);
    $internal = $items->pluck('url')->filter(fn (string $url) => str_starts_with($url, '/'));

    expect($internal->all())->toContain('/ppid', '/sakip')
        ->and($items->pluck('url')->all())->not->toContain('/layanan');

    $internal->each(fn (string $url) => $this->get($url)->assertOk());
});
