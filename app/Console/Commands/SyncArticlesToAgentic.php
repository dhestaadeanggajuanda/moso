<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Services\Agentic\AgenticClient;
use App\Services\Agentic\ArticleMemorySync;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * Mengisi memori asisten AI dengan berita yang sudah terbit, mis. saat
 * moso-agentic baru dipasang. Aman diulang: agentic melakukan upsert per artikel.
 */
#[Signature('agentic:sync-articles {--since= : Hanya artikel yang terbit sejak tanggal ini (mis. 2025-01-01)}')]
#[Description('Kirim artikel terbit ke memori asisten AI (moso-agentic)')]
class SyncArticlesToAgentic extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AgenticClient $agentic, ArticleMemorySync $memory): int
    {
        if (! $agentic->enabled()) {
            $this->components->error('Asisten AI belum aktif. Isi AGENTIC_ENABLED, AGENTIC_URL, dan AGENTIC_TOKEN di .env.');

            return self::FAILURE;
        }

        $sinceOption = $this->option('since');

        try {
            $since = is_string($sinceOption) && $sinceOption !== '' ? CarbonImmutable::parse($sinceOption)->startOfDay() : null;
        } catch (Throwable) {
            $this->components->error('Format --since tidak dikenali. Contoh: --since=2025-01-01');

            return self::FAILURE;
        }

        $synced = 0;
        $failed = 0;

        Article::query()
            ->where('status', ArticleStatus::Published)
            ->whereNotNull('published_at')
            ->when($since, fn (Builder $query, CarbonImmutable $since) => $query->where('published_at', '>=', $since))
            ->chunkById(100, function (Collection $articles) use ($memory, &$synced, &$failed): void {
                foreach ($articles as $article) {
                    if ($memory->upsert($article)) {
                        $synced++;
                    } else {
                        $failed++;
                    }
                }

                $this->components->twoColumnDetail("{$synced} terkirim", $failed > 0 ? "<fg=yellow>{$failed} gagal</>" : '');
            });

        if ($failed > 0) {
            $this->components->warn("{$synced} artikel terkirim, {$failed} gagal. Jalankan ulang perintah ini nanti.");

            return self::FAILURE;
        }

        $this->components->info("{$synced} artikel terkirim ke memori asisten AI.");

        return self::SUCCESS;
    }
}
