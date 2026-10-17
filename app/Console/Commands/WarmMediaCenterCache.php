<?php

namespace App\Console\Commands;

use App\Enums\MediaCenterMode;
use App\Models\Page;
use App\Services\MediaCenter\MediaCenterApiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('mediacenter:warm')]
#[Description('Perbarui cache berita Media Center untuk semua blok NewsGridHybrid di halaman terbit')]
class WarmMediaCenterCache extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MediaCenterApiService $mediaCenter): int
    {
        $failures = 0;

        foreach ($this->targets() as ['keyword' => $keyword, 'mode' => $mode, 'limit' => $limit]) {
            try {
                $count = $mediaCenter->refresh($keyword, $mode, $limit);
                $this->components->info("{$keyword} ({$mode->value}, {$limit}): {$count} berita");
            } catch (Throwable $e) {
                $failures++;
                report($e);
                $this->components->warn("{$keyword} ({$mode->value}): gagal, cache lama dipertahankan");
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Kombinasi kata kunci unik dari blok NewsGridHybrid yang mengambil Media Center.
     *
     * @return list<array{keyword: string, mode: MediaCenterMode, limit: int}>
     */
    private function targets(): array
    {
        $targets = [];

        foreach (Page::query()->published()->get(['id', 'content_blocks']) as $page) {
            foreach ($page->content_blocks as $block) {
                $props = $block['props'];

                if ($block['type'] !== 'NewsGridHybrid' || ! ($props['fetch_media_center'] ?? false) || ! is_string($props['media_center_keyword'] ?? null)) {
                    continue;
                }

                $mode = MediaCenterMode::tryFrom((string) ($props['media_center_mode'] ?? '')) ?? MediaCenterMode::Tag;
                $limit = (int) ($props['limit'] ?? 6);

                $targets["{$mode->value}:{$props['media_center_keyword']}:{$limit}"] = [
                    'keyword' => $props['media_center_keyword'],
                    'mode' => $mode,
                    'limit' => $limit,
                ];
            }
        }

        return array_values($targets);
    }
}
