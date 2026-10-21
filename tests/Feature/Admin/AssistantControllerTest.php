<?php

use App\Enums\RoleName;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    fakeAgenticHost();
});

/**
 * Isian profil dinas yang lolos validasi.
 *
 * @return array<string, mixed>
 */
function assistantProfilePayload(): array
{
    return [
        'official_name' => 'Dinas Kesehatan Kota Singkawang',
        'short_name' => 'Dinkes',
        'duties' => 'Urusan kesehatan masyarakat.',
        'officials' => [['name' => 'dr. Contoh, M.Kes.', 'position' => 'Kepala Dinas']],
        'programs' => ['Posyandu balita bulanan'],
        'address' => 'Jl. Contoh No. 1',
        'contact' => '(0562) 000000',
        'style_guide' => '',
    ];
}

it('is only open to the super admin', function (RoleName $role) {
    Http::fake();

    $this->actingAs(userWithRole($role))
        ->get(route('admin.assistant.index'))
        ->assertForbidden();

    $this->actingAs(userWithRole($role))
        ->put(route('admin.assistant.profile.update'), assistantProfilePayload())
        ->assertForbidden();

    $this->actingAs(userWithRole($role))
        ->delete(route('admin.assistant.style-notes.destroy'))
        ->assertForbidden();

    Http::assertNothingSent();
})->with([RoleName::Approver, RoleName::Editor, RoleName::Kontributor]);

it('loads the profile, style notes, and stats as deferred props', function () {
    Http::fake([
        'agentic.test/api/v1/profile' => Http::response(['data' => [
            'profile' => ['official_name' => 'Dinas Kesehatan', 'officials' => [['name' => 'dr. Contoh', 'position' => 'Kepala Dinas']], 'programs' => ['Posyandu']],
            'updated_at' => '2026-10-20T09:00:00+07:00',
        ]]),
        'agentic.test/api/v1/style-notes' => Http::response(['data' => ['notes' => ['Pakai kalimat aktif.'], 'locked' => false, 'sample_count' => 4, 'updated_at' => null]]),
        'agentic.test/api/v1/stats' => Http::response(['data' => ['indexed_articles' => 120, 'drafts_30d' => 9, 'last_draft_at' => null]]),
    ]);

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->get(route('admin.assistant.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/assistant/Index')
            ->where('enabled', true)
            ->missing('profile')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('profile.official_name', 'Dinas Kesehatan')
                ->where('profile.short_name', '')
                ->where('profile.officials.0.position', 'Kepala Dinas')
                ->where('styleNotes.notes', ['Pakai kalimat aktif.'])
                ->where('stats.indexed_articles', 120)));
});

it('shows a disabled notice without calling agentic', function () {
    config(['agentic.enabled' => false]);
    Http::fake();

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->get(route('admin.assistant.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('enabled', false)
            ->where('profile', null));

    Http::assertNothingSent();
});

it('saves the dinas profile to agentic', function () {
    Http::fake(['agentic.test/api/v1/profile' => Http::response(['data' => ['profile' => [], 'updated_at' => null]])]);

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->put(route('admin.assistant.profile.update'), assistantProfilePayload())
        ->assertRedirect(route('admin.assistant.index'))
        ->assertSessionHasNoErrors();

    Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
        && $request['profile']['official_name'] === 'Dinas Kesehatan Kota Singkawang'
        && $request['profile']['officials'] === [['name' => 'dr. Contoh, M.Kes.', 'position' => 'Kepala Dinas']]
        && $request['profile']['style_guide'] === null);
});

it('requires a name and position for every official', function () {
    Http::fake();

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->put(route('admin.assistant.profile.update'), [...assistantProfilePayload(), 'officials' => [['name' => 'dr. Contoh', 'position' => '']]])
        ->assertSessionHasErrors(['officials.0.position']);

    Http::assertNothingSent();
});

it('keeps the form and explains when agentic cannot save', function () {
    Http::fake(['agentic.test/*' => Http::failedConnection()]);

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->from(route('admin.assistant.index'))
        ->put(route('admin.assistant.profile.update'), assistantProfilePayload())
        ->assertSessionHasErrors(['agentic' => 'Asisten AI tidak dapat dihubungi, perubahan belum tersimpan. Coba lagi beberapa saat lagi.']);
});

it('locks edited style notes and resets them', function () {
    Http::fake([
        'agentic.test/api/v1/style-notes' => fn (Request $request) => $request->method() === 'DELETE'
            ? Http::response(status: 204)
            : Http::response(['data' => ['notes' => ['Pakai kalimat aktif.'], 'locked' => true, 'sample_count' => 4, 'updated_at' => null]]),
    ]);
    $superAdmin = userWithRole(RoleName::SuperAdmin);

    $this->actingAs($superAdmin)
        ->put(route('admin.assistant.style-notes.update'), ['notes' => ['Pakai kalimat aktif.'], 'locked' => true])
        ->assertRedirect(route('admin.assistant.index'));

    $this->actingAs($superAdmin)
        ->delete(route('admin.assistant.style-notes.destroy'))
        ->assertRedirect(route('admin.assistant.index'));

    Http::assertSent(fn (Request $request) => $request->method() === 'PUT' && $request->data() === ['notes' => ['Pakai kalimat aktif.'], 'locked' => true]);
    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/style-notes'));
});

it('accepts at most twelve style notes', function () {
    Http::fake();

    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->put(route('admin.assistant.style-notes.update'), ['notes' => array_fill(0, 13, 'Aturan.'), 'locked' => false])
        ->assertSessionHasErrors(['notes']);

    Http::assertNothingSent();
});

it('shows the sidebar link only to the super admin while the assistant is enabled', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->get(route('admin.articles.index'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.can.assistant', true));

    $this->actingAs(userWithRole(RoleName::Approver))
        ->get(route('admin.articles.index'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.can.assistant', false));
});
