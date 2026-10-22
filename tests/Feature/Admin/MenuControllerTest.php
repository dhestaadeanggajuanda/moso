<?php

use App\Enums\MenuLocation;
use App\Enums\RoleName;
use App\Models\Menu;
use App\Models\Page;

it('lets site admins manage menus but not writers', function (RoleName $role, bool $allowed) {
    $response = $this->actingAs(userWithRole($role))->get(route('admin.menus.index'));

    $allowed ? $response->assertOk() : $response->assertForbidden();
})->with([
    'approver' => [RoleName::Approver, true],
    'editor (admin harian)' => [RoleName::Editor, true],
    'penulis' => [RoleName::Penulis, false],
    'kontributor' => [RoleName::Kontributor, false],
]);

it('creates a menu item linked to a page', function () {
    $page = Page::factory()->published()->create(['slug' => 'profil']);

    $this->actingAs(userWithRole(RoleName::Approver))
        ->post(route('admin.menus.store'), [
            'location' => 'header',
            'label' => 'Profil',
            'page_id' => $page->id,
            'url' => '',
            'parent_id' => '',
            'sort_order' => '',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.menus.index', ['location' => 'header']));

    $menu = Menu::query()->sole();

    expect($menu->href)->toBe('/profil')
        ->and($menu->url)->toBeNull()
        ->and($menu->sort_order)->toBe(0);
});

it('rejects unsafe urls', function (string $url) {
    $this->actingAs(userWithRole(RoleName::Approver))
        ->post(route('admin.menus.store'), ['location' => 'header', 'label' => 'Tautan', 'url' => $url])
        ->assertInvalid(['url' => 'URL harus diawali "/" (halaman internal) atau "http(s)://".']);
})->with([
    'javascript scheme' => 'javascript:alert(1)',
    'data scheme' => 'data:text/html,<script>alert(1)</script>',
    'protocol relative' => '//evil.example',
    'backslash protocol relative' => '/\evil.example',
    'bare domain' => 'evil.example',
]);

it('rejects choosing both a page and a url', function () {
    $page = Page::factory()->create();

    $this->actingAs(userWithRole(RoleName::Approver))
        ->post(route('admin.menus.store'), [
            'location' => 'header',
            'label' => 'Ganda',
            'page_id' => $page->id,
            'url' => '/lain',
        ])
        ->assertInvalid(['url' => 'Pilih salah satu: halaman tujuan atau URL.']);
});

it('prevents moving an item under its own descendant', function () {
    $root = Menu::factory()->create();
    $child = Menu::factory()->childOf($root)->create();
    $grandchild = Menu::factory()->childOf($child)->create();

    $this->actingAs(userWithRole(RoleName::Approver))
        ->put(route('admin.menus.update', $root), [
            'location' => 'header',
            'label' => $root->label,
            'url' => $root->url,
            'parent_id' => $grandchild->id,
        ])
        ->assertInvalid(['parent_id' => 'Menu tidak dapat dipindah ke bawah dirinya sendiri.']);

    expect($root->fresh()->parent_id)->toBeNull();
});

it('limits menus to three levels', function () {
    $level1 = Menu::factory()->create();
    $level2 = Menu::factory()->childOf($level1)->create();
    $level3 = Menu::factory()->childOf($level2)->create();

    $this->actingAs(userWithRole(RoleName::Approver))
        ->post(route('admin.menus.store'), [
            'location' => 'header',
            'label' => 'Tingkat empat',
            'url' => '/terlalu-dalam',
            'parent_id' => $level3->id,
        ])
        ->assertInvalid(['parent_id' => 'Menu maksimal 3 tingkat.']);
});

it('rejects a parent from another location', function () {
    $footerItem = Menu::factory()->create(['location' => MenuLocation::Footer]);

    $this->actingAs(userWithRole(RoleName::Approver))
        ->post(route('admin.menus.store'), [
            'location' => 'header',
            'label' => 'Salah lokasi',
            'url' => '/x',
            'parent_id' => $footerItem->id,
        ])
        ->assertInvalid(['parent_id' => 'Induk menu harus berada di lokasi yang sama.']);
});

it('deletes sub menus together with their parent', function () {
    $root = Menu::factory()->create();
    $child = Menu::factory()->childOf($root)->create();

    $this->actingAs(userWithRole(RoleName::Approver))
        ->delete(route('admin.menus.destroy', $root))
        ->assertRedirect();

    $this->assertModelMissing($child);
});
