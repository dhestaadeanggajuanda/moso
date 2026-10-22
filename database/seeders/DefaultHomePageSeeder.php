<?php

namespace Database\Seeders;

use App\Enums\MenuLocation;
use App\Enums\PageStatus;
use App\Models\Menu;
use App\Models\Page;
use App\Services\PageBuilder\BlockRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Tampilan awal setiap website dinas: beranda tersusun dan menu header dasar,
 * tanpa konten contoh. Aman dijalankan berulang dan di produksi: halaman
 * beranda dan menu hanya dibuat bila belum ada, sehingga susunan yang sudah
 * diubah admin tidak pernah ditimpa.
 */
class DefaultHomePageSeeder extends Seeder
{
    public function __construct(private BlockRegistry $blocks) {}

    public function run(): void
    {
        $beranda = Page::query()->withTrashed()->firstOrCreate(['slug' => 'beranda'], [
            'title' => 'Beranda',
            'status' => PageStatus::Published,
            'content_blocks' => $this->blocks->normalizeBlocks($this->homeBlocks()),
        ]);

        if (Menu::query()->doesntExist()) {
            $this->seedHeaderMenu($beranda);
        }
    }

    /**
     * Susunan beranda bawaan. Nilai yang tidak ditulis memakai defaults() blok.
     *
     * @return list<array{id: string, type: string, props: array<string, mixed>}>
     */
    private function homeBlocks(): array
    {
        return array_map($this->block(...), [
            ['HeroBanner', [
                'heading' => 'Layanan publik yang mudah dijangkau warga Singkawang',
                'subheading' => 'Cek persyaratan, jangka waktu, dan biaya layanan sebelum datang ke kantor.',
                'cta_label' => 'Lihat semua layanan',
                'cta_url' => '/layanan',
            ]],
            ['AksesCepat', [
                'items' => [
                    ['label' => 'Cari layanan', 'url' => '/layanan', 'icon' => 'layanan'],
                    ['label' => 'Informasi publik', 'url' => '/ppid', 'icon' => 'dokumen'],
                    ['label' => 'Pengaduan', 'url' => 'https://www.lapor.go.id', 'icon' => 'pengaduan'],
                ],
            ]],
            ['LayananList', ['show_search' => false]],
            // Berita Media Center menyala setelah admin mengisi kata kunci dinasnya di Page Builder.
            ['NewsGridHybrid', []],
            ['AgendaKegiatan', []],
        ]);
    }

    /**
     * @param  array{0: string, 1: array<string, mixed>}  $spec
     * @return array{id: string, type: string, props: array<string, mixed>}
     */
    private function block(array $spec): array
    {
        [$type, $overrides] = $spec;
        $definition = $this->blocks->get($type) ?? throw new \LogicException("Blok {$type} tidak terdaftar.");
        $props = [...$definition->defaults(), ...$overrides];

        // Sama seperti simpan dari Page Builder: susunan bawaan wajib lolos aturan blok.
        Validator::make($props, $definition->rules(), $definition->messages())->validate();

        return ['id' => (string) Str::uuid(), 'type' => $type, 'props' => $props];
    }

    private function seedHeaderMenu(Page $beranda): void
    {
        $items = [
            ['label' => 'Beranda', 'page_id' => $beranda->id, 'url' => null],
            ['label' => 'Berita', 'page_id' => null, 'url' => '/berita'],
            ['label' => 'Layanan', 'page_id' => null, 'url' => '/layanan'],
            ['label' => 'Informasi Publik', 'page_id' => null, 'url' => '/ppid'],
            ['label' => 'E-SAKIP', 'page_id' => null, 'url' => '/sakip'],
        ];

        foreach ($items as $order => $item) {
            Menu::query()->create([
                ...$item,
                'location' => MenuLocation::Header,
                'open_in_new_tab' => false,
                'sort_order' => $order + 1,
                'is_active' => true,
            ]);
        }
    }
}
