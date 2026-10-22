<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleWorkflowMode;
use App\Enums\SiteLogo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleWorkflowSettingRequest;
use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Models\Setting;
use App\Support\AuditLog;
use App\Support\Settings;
use App\Support\SiteContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Settings $settings, SiteContext $site): Response
    {
        Gate::authorize('viewAny', Setting::class);

        return Inertia::render('admin/settings/Index', [
            'settings' => Setting::query()
                // Logo diatur lewat unggahan dan alur artikel lewat pilihan sendiri, bukan kolom teks.
                ->whereNotIn('key', [...SiteLogo::settingKeys(), ArticleWorkflowMode::SETTING_KEY])
                ->orderBy('key')
                ->get()
                ->map(fn (Setting $setting) => [
                    'key' => $setting->key,
                    // Nilai rahasia tidak pernah dikirim ke browser.
                    'value' => $setting->is_encrypted ? null : $settings->get($setting->key),
                    'is_encrypted' => $setting->is_encrypted,
                    'has_value' => $setting->value !== null && $setting->value !== '',
                ]),
            'logos' => array_map(fn (SiteLogo $logo) => [
                'slot' => $logo->value,
                'label' => $logo->label(),
                'url' => $site->logoUrl($logo->settingKey()),
            ], SiteLogo::cases()),
            'workflow' => [
                'mode' => ArticleWorkflowMode::current()->value,
                'options' => ArticleWorkflowMode::options(),
            ],
        ]);
    }

    public function updateWorkflow(ArticleWorkflowSettingRequest $request, Settings $settings): RedirectResponse
    {
        $mode = ArticleWorkflowMode::from((string) $request->validated('mode'));
        $settings->put(ArticleWorkflowMode::SETTING_KEY, $mode->value);

        AuditLog::record('settings.article_workflow_changed', [
            'user_id' => $request->user()?->id,
            'mode' => $mode->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Alur penerbitan diubah ke {$mode->label()}."]);

        return to_route('admin.settings.index');
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
