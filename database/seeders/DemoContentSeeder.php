<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\MenuLocation;
use App\Enums\RoleName;
use App\Enums\SakipDocumentType;
use App\Models\Agenda;
use App\Models\Article;
use App\Models\Category;
use App\Models\ESakip;
use App\Models\Menu;
use App\Models\PublicDocument;
use App\Models\Service;
use App\Models\User;
use App\Services\Storage\SecureDocumentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Konten contoh untuk development. Hanya dipanggil di environment local.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('seeding.demo_password') ?: Str::password(20);

        $users = collect([
            RoleName::Approver->value => 'approver@portal-dinas.test',
            RoleName::Editor->value => 'editor@portal-dinas.test',
            RoleName::Kontributor->value => 'kontributor@portal-dinas.test',
        ])->map(function (string $email, string $role) use ($password) {
            $user = User::query()->firstOrCreate(['email' => $email], [
                'name' => "Demo {$role}",
                'password' => $password,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user->syncRoles([$role]);
        });

        $categories = collect(['Berita Daerah', 'Sosial', 'Kesehatan', 'Pendidikan', 'Pengumuman'])
            ->map(fn (string $name) => Category::query()->firstOrCreate(['name' => $name]));

        /** @var User $kontributor */
        $kontributor = $users[RoleName::Kontributor->value];
        /** @var User $editor */
        $editor = $users[RoleName::Editor->value];

        Article::factory()->count(8)->published()->for($editor, 'author')->create()
            ->each(fn (Article $article) => $article->categories()->sync($categories->random(2)->pluck('id')));

        Article::factory()->count(3)->for($kontributor, 'author')->create();
        Article::factory()->status(ArticleStatus::Submitted)->for($kontributor, 'author')->create();
        Article::factory()->status(ArticleStatus::RevisionRequired)->for($kontributor, 'author')->create();

        Agenda::factory()->count(6)->create();

        // Beranda dan menu dasar dibuat DefaultHomePageSeeder (dipanggil lebih dulu).
        $this->seedCompliance();
    }

    /**
     * Contoh modul kepatuhan: layanan, menu bertingkat, serta dokumen PPID
     * dan SAKIP dengan PDF contoh di disk privat.
     */
    private function seedCompliance(): void
    {
        Service::factory()->published()->create(['name' => 'Surat Keterangan Tidak Mampu', 'sort_order' => 1]);
        Service::factory()->published()->create(['name' => 'Rekomendasi Bantuan Sosial', 'sort_order' => 2]);
        Service::factory()->create(['name' => 'Layanan Uji Coba (draf)']);

        foreach (['Daftar Informasi Publik 2026' => 'berkala', 'Laporan Penanganan Bencana' => 'serta_merta', 'SOP Pelayanan Informasi' => 'setiap_saat'] as $title => $category) {
            PublicDocument::factory()->published()->create([
                'title' => $title,
                'category' => $category,
                'file_path' => $this->storeSamplePdf('ppid', $title),
            ]);
        }

        ESakip::factory()->published()->create([
            'year' => 2025,
            'document_type' => SakipDocumentType::Lkjip,
            'document_title' => 'Laporan Kinerja Instansi Pemerintah Tahun 2025',
            'file_path_s3' => $this->storeSamplePdf('sakip', 'LKjIP 2025'),
        ]);

        // Beranda, Berita, Layanan, Informasi Publik, dan E-SAKIP sudah dibuat DefaultHomePageSeeder.
        $profil = Menu::factory()->create(['label' => 'Profil', 'url' => '/profil', 'sort_order' => 6]);
        Menu::factory()->childOf($profil)->create(['label' => 'Visi dan Misi', 'url' => '/profil/visi-misi', 'sort_order' => 1]);
        Menu::factory()->childOf($profil)->create(['label' => 'Struktur Organisasi', 'url' => '/profil/struktur', 'sort_order' => 2]);
        Menu::factory()->create(['label' => 'Pemerintah Kota', 'url' => 'https://singkawangkota.go.id', 'open_in_new_tab' => true, 'sort_order' => 7]);
        Menu::factory()->create(['label' => 'Kebijakan Privasi', 'url' => '/kebijakan-privasi', 'location' => MenuLocation::Footer]);
    }

    private function storeSamplePdf(string $directory, string $title): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $title);
        $stream = "BT /F1 20 Tf 72 760 Td ({$text}) Tj ET";
        $pdf = "%PDF-1.4\n"
            ."1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            ."3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n"
            .'4 0 obj << /Length '.strlen($stream)." >> stream\n{$stream}\nendstream endobj\n"
            ."5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
            ."trailer << /Root 1 0 R >>\n%%EOF\n";

        $path = $directory.'/demo/'.Str::uuid().'.pdf';
        Storage::disk(SecureDocumentService::DISK)->put($path, $pdf, ['ContentType' => 'application/pdf']);

        return $path;
    }
}
