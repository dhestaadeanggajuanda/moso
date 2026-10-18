<?php

namespace App\Support;

use App\Enums\MenuLocation;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Data bersama untuk tema publik: identitas situs, kontak, media sosial, dan
 * menu. Hanya pengaturan yang memang publik yang dikirim (bukan yang terenkripsi).
 */
class SiteContext
{
    /**
     * Path logo di disk s3, mis. "branding/logo-pemkot.webp".
     */
    private const LOGO_PATH_PATTERN = '/^[a-z0-9-]+(\/[A-Za-z0-9_-]+)*\/[A-Za-z0-9_-]+\.(webp|png|jpe?g)$/';

    public function __construct(private Settings $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->settings->get('site.name', (string) config('app.name')),
            'tagline' => $this->settings->get('site.tagline'),
            'description' => $this->settings->get('site.description'),
            'logos' => [
                'pemkot' => $this->logoUrl('site.logo_pemkot'),
                'dinas' => $this->logoUrl('site.logo_dinas'),
            ],
            'contact' => [
                'address' => $this->settings->get('contact.address'),
                'phone' => $this->settings->get('contact.phone'),
                'email' => $this->settings->get('contact.email'),
                'hours' => $this->settings->get('contact.hours'),
                'complaint_url' => $this->safeUrl('contact.complaint_url'),
            ],
            'social' => array_filter([
                'facebook' => $this->safeUrl('social.facebook'),
                'instagram' => $this->safeUrl('social.instagram'),
                'youtube' => $this->safeUrl('social.youtube'),
            ]),
            // Route sistem yang dipakai tema, supaya tema tidak menanam path sendiri.
            'links' => [
                'home' => route('home'),
                'services' => route('site.services.index'),
            ],
            'menus' => [
                'header' => MenuResource::collection(Menu::treeFor(MenuLocation::Header, publicOnly: true))->resolve($request),
                'footer' => MenuResource::collection(Menu::treeFor(MenuLocation::Footer, publicOnly: true))->resolve($request),
            ],
        ];
    }

    /**
     * Tautan dari pengaturan hanya dikirim bila skemanya aman (OWASP A05):
     * nilai seperti "javascript:..." tidak pernah sampai ke atribut href.
     */
    private function safeUrl(string $key): ?string
    {
        $value = $this->settings->get($key);

        return filled($value) && preg_match(SafeUrl::PATTERN, $value) === 1 ? $value : null;
    }

    private function logoUrl(string $key): ?string
    {
        $path = $this->settings->get($key);

        if (blank($path) || preg_match(self::LOGO_PATH_PATTERN, $path) !== 1) {
            return null;
        }

        return Storage::disk('s3')->url($path);
    }
}
