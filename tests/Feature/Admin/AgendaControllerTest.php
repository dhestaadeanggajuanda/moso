<?php

use App\Enums\RoleName;
use App\Models\Agenda;

it('forbids a kontributor from managing agendas', function () {
    $this->actingAs(userWithRole(RoleName::Kontributor))
        ->post(route('admin.agendas.store'), ['title' => 'Rapat', 'start_at' => '2026-11-01 09:00'])
        ->assertForbidden();

    expect(Agenda::query()->count())->toBe(0);
});

it('unpublishes an agenda when the checkbox is left unchecked', function () {
    $agenda = Agenda::factory()->create(['is_published' => true]);

    // Form mengirim nilai tersembunyi "0" bila kotak centang kosong.
    $this->actingAs(userWithRole(RoleName::Editor))
        ->put(route('admin.agendas.update', $agenda), [
            'title' => $agenda->title,
            'start_at' => '2026-11-01T09:00',
            'is_published' => '0',
        ])
        ->assertRedirect(route('admin.agendas.index'));

    expect($agenda->fresh()->is_published)->toBeFalse();
});

it('rejects an end time before the start time', function () {
    $this->actingAs(userWithRole(RoleName::Editor))
        ->post(route('admin.agendas.store'), [
            'title' => 'Rapat koordinasi',
            'start_at' => '2026-11-01T09:00',
            'end_at' => '2026-11-01T08:00',
        ])
        ->assertInvalid(['end_at']);
});
