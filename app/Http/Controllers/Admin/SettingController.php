<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Settings $settings): Response
    {
        Gate::authorize('viewAny', Setting::class);

        return Inertia::render('admin/settings/Index', [
            'settings' => Setting::query()
                ->orderBy('key')
                ->get()
                ->map(fn (Setting $setting) => [
                    'key' => $setting->key,
                    // Nilai rahasia tidak pernah dikirim ke browser.
                    'value' => $setting->is_encrypted ? null : $settings->get($setting->key),
                    'is_encrypted' => $setting->is_encrypted,
                    'has_value' => $setting->value !== null && $setting->value !== '',
                ]),
        ]);
    }

    public function update(SettingsUpdateRequest $request, Settings $settings): RedirectResponse
    {
        $existing = Setting::query()->pluck('value', 'key');

        foreach ($request->validated('settings') as $item) {
            $encrypted = (bool) ($item['is_encrypted'] ?? false);
            $value = $item['value'] ?? null;

            // Kolom rahasia yang dikosongkan berarti "pertahankan nilai lama".
            if ($encrypted && ($value === null || $value === '') && filled($existing[$item['key']] ?? null)) {
                continue;
            }

            $settings->put($item['key'], $value, $encrypted);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan disimpan.']);

        return to_route('admin.settings.index');
    }
}
