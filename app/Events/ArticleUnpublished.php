<?php

namespace App\Events;

use App\Models\Article;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Artikel terbit diturunkan dari situs: diarsipkan atau dihapus.
 *
 * Dikirim oleh ArticleWorkflow dan ArticleController setelah transaksi selesai.
 */
class ArticleUnpublished implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Article $article) {}
}
