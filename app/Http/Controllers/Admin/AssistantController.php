<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssistantProfileRequest;
use App\Http\Requests\Admin\AssistantStyleNotesRequest;
use App\Models\Setting;
use App\Services\Agentic\AgenticClient;
use App\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman "Asisten AI": profil dinas, gaya tulisan hasil belajar, dan status
 * memori di moso-agentic. Datanya tersimpan di agentic, bukan di moso.
 * Hanya Super Admin (hak `settings.manage` tidak diberikan ke role lain).
 */
class AssistantController extends Controller
{
    private const UNAVAILABLE = 'Asisten AI tidak dapat dihubungi, perubahan belum tersimpan. Coba lagi beberapa saat lagi.';

    public function index(AgenticClient $agentic): Response
    {
        Gate::authorize('update', Setting::class);

        $enabled = $agentic->enabled();

        return Inertia::render('admin/assistant/Index', [
            'enabled' => $enabled,
            // Dimuat terpisah setelah halaman tampil, agar agentic yang lambat tidak menahan halaman.
            'profile' => $enabled ? Inertia::defer(fn (): ?array => $this->profile($agentic->profile()), 'profile') : null,
            'styleNotes' => $enabled ? Inertia::defer(fn (): ?array => $agentic->styleNotes(), 'style') : null,
            'stats' => $enabled ? Inertia::defer(fn (): ?array => $agentic->stats(), 'stats') : null,
        ]);
    }

    public function updateProfile(AssistantProfileRequest $request, AgenticClient $agentic): RedirectResponse
    {
        abort_unless($agentic->enabled(), 404);

        if ($agentic->putProfile($request->profile()) === null) {
            throw ValidationException::withMessages(['agentic' => self::UNAVAILABLE]);
        }

        AuditLog::record('agentic.profile_updated', ['user_id' => $request->user()?->id]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil dinas disimpan.']);

        return to_route('admin.assistant.index');
    }

    public function updateStyleNotes(AssistantStyleNotesRequest $request, AgenticClient $agentic): RedirectResponse
    {
        abort_unless($agentic->enabled(), 404);

        /** @var list<string> $notes */
        $notes = array_values(array_map('strval', $request->validated('notes', [])));

        if ($agentic->putStyleNotes($notes, $request->boolean('locked')) === null) {
            throw ValidationException::withMessages(['agentic' => self::UNAVAILABLE]);
        }

        AuditLog::record('agentic.style_notes_updated', ['user_id' => $request->user()?->id, 'locked' => $request->boolean('locked')]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gaya tulisan disimpan.']);

        return to_route('admin.assistant.index');
    }

    public function resetStyleNotes(Request $request, AgenticClient $agentic): RedirectResponse
    {
        Gate::authorize('update', Setting::class);
        abort_unless($agentic->enabled(), 404);

        if (! $agentic->resetStyleNotes()) {
            throw ValidationException::withMessages(['agentic' => self::UNAVAILABLE]);
        }

        AuditLog::record('agentic.style_notes_reset', ['user_id' => $request->user()?->id]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gaya tulisan dikosongkan. Asisten AI akan belajar ulang dari koreksi editor.']);

        return to_route('admin.assistant.index');
    }

    /**
     * Profil dari agentic dalam bentuk yang selalu lengkap untuk form.
     *
     * @param  array{profile: array<string, mixed>, updated_at: string|null}|null  $response
     * @return array{official_name: string, short_name: string, duties: string, officials: list<array{name: string, position: string}>, programs: list<string>, address: string, contact: string, style_guide: string, updated_at: string|null}|null
     */
    private function profile(?array $response): ?array
    {
        if ($response === null) {
            return null;
        }

        $profile = $response['profile'];
        $text = fn (string $key): string => is_string($profile[$key] ?? null) ? $profile[$key] : '';

        return [
            'official_name' => $text('official_name'),
            'short_name' => $text('short_name'),
            'duties' => $text('duties'),
            'officials' => array_values(array_filter(array_map(
                fn (mixed $official): ?array => is_array($official)
                    ? ['name' => (string) ($official['name'] ?? ''), 'position' => (string) ($official['position'] ?? '')]
                    : null,
                is_array($profile['officials'] ?? null) ? $profile['officials'] : [],
            ))),
            'programs' => array_values(array_filter(is_array($profile['programs'] ?? null) ? $profile['programs'] : [], is_string(...))),
            'address' => $text('address'),
            'contact' => $text('contact'),
            'style_guide' => $text('style_guide'),
            'updated_at' => $response['updated_at'],
        ];
    }
}
