<?php

use App\Models\Article;

it('suffixes duplicate slugs, counting soft deleted rows', function () {
    $first = Article::factory()->create(['title' => 'Rapat Koordinasi']);
    $first->delete();

    $second = Article::factory()->create(['title' => 'Rapat Koordinasi']);

    expect($second->slug)->toBe('rapat-koordinasi-2');
});

it('keeps its own slug when saved again', function () {
    $article = Article::factory()->create(['title' => 'Rapat Koordinasi']);

    $article->update(['excerpt' => 'Ringkasan baru']);

    expect($article->fresh()->slug)->toBe('rapat-koordinasi');
});

it('normalises a manually entered slug', function () {
    $article = Article::factory()->create(['slug' => 'Berita Utama Hari Ini']);

    expect($article->slug)->toBe('berita-utama-hari-ini');
});
