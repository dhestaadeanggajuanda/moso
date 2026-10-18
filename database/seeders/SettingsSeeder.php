<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Pengaturan bawaan. Hanya menambah key yang belum ada, jadi nilai yang sudah
 * diubah admin tidak tertimpa.
 */
class SettingsSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'site.name' => 'Portal Dinas Kota Singkawang',
        'site.tagline' => 'Informasi dan layanan publik',
        'site.description' => 'Portal resmi berita, layanan, dan informasi publik.',
        'contact.address' => 'Jalan ..., Kota Singkawang, Kalimantan Barat',
        'contact.phone' => '',
        'contact.email' => '',
        'contact.hours' => '',
        // Kanal pengaduan nasional SP4N-LAPOR!; dapat diganti kanal milik dinas.
        'contact.complaint_url' => 'https://www.lapor.go.id',
        // Path logo di disk s3; diisi lewat unggahan di halaman Pengaturan.
        'site.logo_pemkot' => '',
        'site.logo_dinas' => '',
        'social.facebook' => '',
        'social.instagram' => '',
        'social.youtube' => '',
        'site.home_page' => 'beranda',
        'theme.active' => 'TemaModern',
    ];

    public function run(Settings $settings): void
    {
        $existing = Setting::query()->pluck('key')->all();

        foreach (self::DEFAULTS as $key => $value) {
            if (! in_array($key, $existing, true)) {
                $settings->put($key, $value);
            }
        }
    }
}
