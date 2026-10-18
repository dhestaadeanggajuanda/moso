<?php

use App\Models\Category;
use App\Services\PageBuilder\Blocks\NewsGridHybridBlock;

it('links "see all news" to the news index, keeping the block category', function () {
    Category::factory()->create(['name' => 'Sosial']);
    $block = app(NewsGridHybridBlock::class);

    expect($block->hydrate(['limit' => 3])['index_url'])->toBe(route('site.articles.index'))
        ->and($block->hydrate(['limit' => 3, 'local_category' => 'sosial'])['index_url'])
        ->toBe(route('site.articles.index', ['kategori' => 'sosial']));
});
