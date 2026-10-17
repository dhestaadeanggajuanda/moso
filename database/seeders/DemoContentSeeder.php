<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Agenda;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
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

        Page::query()->firstOrCreate(['slug' => 'beranda'], [
            'title' => 'Beranda',
            'content_blocks' => [],
            'status' => 'published',
        ]);
    }
}
