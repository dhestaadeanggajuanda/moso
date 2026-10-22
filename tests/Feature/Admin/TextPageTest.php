<?php

use App\Enums\PageStatus;
use App\Enums\RoleName;
use App\Models\Page;
use Inertia\Testing\AssertableInertia as Assert;

it('creates a text page whose content is typed in the form and sanitized', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.pages.store'), [
            'title' => 'Visi dan Misi',
            'status' => PageStatus::Published->value,
            'layout' => 'text',
            'body' => '<h2>Visi</h2><p>Pelayanan prima.</p><script>alert(1)</script>',
        ])
        ->assertSessionHasNoErrors();

    $page = Page::query()->sole();

    expect($page->content_blocks)->toHaveCount(1)
        ->and($page->content_blocks[0]['type'])->toBe('RichText')
        ->and($page->textBody())->toContain('<h2>Visi</h2>')
        ->not->toContain('<script');

    $this->get(route('site.pages.show', $page->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia->has('blocks', 1));
});

it('updates the text of a text page in place', function () {
    $editor = userWithRole(RoleName::Editor);
    $this->actingAs($editor)->post(route('admin.pages.store'), [
        'title' => 'Profil',
        'status' => PageStatus::Draft->value,
        'layout' => 'text',
        'body' => '<p>Lama</p>',
    ]);
    $page = Page::query()->sole();
    $blockId = $page->content_blocks[0]['id'];

    $this->actingAs($editor)->put(route('admin.pages.update', $page), [
        'title' => 'Profil',
        'status' => PageStatus::Draft->value,
        'body' => '<p>Baru</p>',
    ])->assertSessionHasNoErrors();

    expect($page->refresh()->textBody())->toBe('<p>Baru</p>')
        ->and($page->content_blocks[0]['id'])->toBe($blockId);
});

it('never lets the form overwrite a page built with blocks', function () {
    $page = Page::factory()->create([
        'content_blocks' => [
            ['id' => (string) Str::uuid(), 'type' => 'RichText', 'props' => ['html' => '<p>A</p>']],
            ['id' => (string) Str::uuid(), 'type' => 'AgendaKegiatan', 'props' => ['title' => 'Agenda', 'limit' => 4]],
        ],
    ]);

    $this->actingAs(userWithRole(RoleName::Editor))->put(route('admin.pages.update', $page), [
        'title' => $page->title,
        'status' => $page->status->value,
        'body' => '<p>Ditimpa</p>',
    ])->assertSessionHasNoErrors();

    expect($page->refresh()->content_blocks)->toHaveCount(2);
});

it('starts a block page empty for the page builder', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.pages.store'), [
            'title' => 'Beranda Dinas',
            'status' => PageStatus::Draft->value,
            'layout' => 'blocks',
            'body' => '<p>diabaikan</p>',
        ])
        ->assertSessionHasNoErrors();

    expect(Page::query()->sole()->content_blocks)->toBe([]);
});
