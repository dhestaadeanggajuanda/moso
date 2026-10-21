<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Events\ArticleUnpublished;
use App\Services\Agentic\AgenticClient;
use App\Services\Agentic\ArticleMemorySync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

/**
 * Menyalin artikel terbit ke memori asisten AI (moso-agentic), dan
 * menghapusnya saat diarsipkan atau dihapus. Berjalan di antrean setelah
 * transaksi selesai, jadi penerbitan tidak menunggu agentic.
 */
class SyncArticleToAgentic implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120, 600, 1800];

    public function __construct(
        private AgenticClient $agentic,
        private ArticleMemorySync $memory,
    ) {}

    /**
     * Tidak mengantre apa pun selama asisten AI dimatikan.
     */
    public function shouldQueue(ArticlePublished|ArticleUnpublished $event): bool
    {
        return $this->agentic->enabled();
    }

    public function handle(ArticlePublished|ArticleUnpublished $event): void
    {
        if (! $this->agentic->enabled()) {
            return;
        }

        if (! $this->memory->sync($event->article)) {
            // Diulang oleh antrean sesuai $backoff; rinciannya sudah dicatat AgenticClient.
            throw new RuntimeException("Sinkron artikel {$event->article->getKey()} ke asisten AI gagal.");
        }
    }
}
