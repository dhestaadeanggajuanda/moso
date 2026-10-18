<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SiteLogo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteLogoRequest;
use App\Models\Setting;
use App\Services\Storage\SecureUploadService;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Logo Pemkot dan logo dinas di header situs publik.
 *
 * Gambar selalu lewat SecureUploadService (di-encode ulang ke WebP, nama UUID)
 * ke disk s3, lalu path-nya disimpan di pengaturan `site.logo_*`. Logo tidak
 * bisa diisi path manual dari formulir pengaturan.
 */
class SiteLogoController extends Controller
{
    /**
     * Header menampilkan logo setinggi ±56px; 800px cukup untuk layar retina.
     */
    public const MAX_WIDTH = 800;

    /**
     * Bentuk path hasil unggahan logo. Hanya berkas seperti ini yang dihapus
     * otomatis saat logo diganti, sehingga berkas yang ditaruh manual aman.
     */
    private const UPLOADED_PATH_PATTERN = '#^'.SiteLogo::DIRECTORY.'/\d{4}/\d{2}/[0-9a-f-]{36}\.webp$#';

    public function update(SiteLogoRequest $request, SiteLogo $logo, Settings $settings, SecureUploadService $uploads): RedirectResponse
    {
        $previous = $settings->get($logo->settingKey());

        $path = $uploads->storeImage(
            $request->file('logo'),
            SiteLogo::DIRECTORY,
            attribute: 'logo',
            maxWidth: self::MAX_WIDTH,
        );

        $settings->put($logo->settingKey(), $path);
        $this->deletePreviousUpload($previous, $uploads);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$logo->label()} diperbarui."]);

        return to_route('admin.settings.index');
    }

    public function destroy(SiteLogo $logo, Settings $settings, SecureUploadService $uploads): RedirectResponse
    {
        Gate::authorize('update', Setting::class);

        $previous = $settings->get($logo->settingKey());

        $settings->put($logo->settingKey(), null);
        $this->deletePreviousUpload($previous, $uploads);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$logo->label()} dihapus. Header menampilkan lambang netral."]);

        return to_route('admin.settings.index');
    }

    private function deletePreviousUpload(?string $path, SecureUploadService $uploads): void
    {
        if ($path !== null && preg_match(self::UPLOADED_PATH_PATTERN, $path) === 1) {
            // Logo baru sudah tersimpan; berkas lama yang gagal dihapus cukup dilaporkan.
            rescue(fn () => $uploads->delete($path));
        }
    }
}
