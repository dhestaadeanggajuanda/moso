<?php

use App\Enums\RoleName;
use App\Models\Category;

it('forbids a kontributor from managing categories', function () {
    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->post(route('admin.categories.store'), ['name' => 'Sosial'])
        ->assertForbidden();
});

it('creates a category with a unique slug', function () {
    Category::factory()->create(['name' => 'Sosial']);

    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.categories.store'), ['name' => 'Sosial'])
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::query()->pluck('slug')->sort()->values()->all())->toBe(['sosial', 'sosial-2']);
});
