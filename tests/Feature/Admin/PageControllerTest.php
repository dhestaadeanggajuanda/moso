<?php

use App\Enums\PageStatus;
use App\Enums\RoleName;
use App\Models\Page;

it('forbids a kontributor from managing pages', function () {
    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->get(route('admin.pages.index'))
        ->assertForbidden();
});

it('creates a page with empty blocks and records the author', function () {
    $editor = userWithRole(RoleName::Editor);

    $this->actingAs($editor)
        ->post(route('admin.pages.store'), [
            'title' => 'Profil Dinas',
            'status' => 'draft',
            'content_blocks' => [['type' => 'Injected', 'props' => []]],
        ])
        ->assertRedirect();

    $page = Page::query()->sole();

    expect($page->slug)->toBe('profil-dinas')
        ->and($page->content_blocks)->toBe([])
        ->and($page->created_by)->toBe($editor->id)
        ->and($page->published_at)->toBeNull();
});

it('stamps published_at when a page is published', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'status' => PageStatus::Published->value,
        ])
        ->assertRedirect();

    expect($page->fresh()->published_at)->not->toBeNull();
});

it('rejects slugs reserved by the application', function (string $slug) {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.pages.store'), ['title' => 'Halaman', 'slug' => $slug, 'status' => 'draft'])
        ->assertInvalid(['slug' => 'Slug ini dipakai oleh sistem. Gunakan slug lain.']);
})->with(['admin', 'login', 'api', 'berita']);
