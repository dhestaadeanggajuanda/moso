<?php

namespace App\Events;

use App\Models\Article;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Artikel diterbitkan (atau diterbitkan ulang), atau isi artikel terbit diubah.
 *
 * Dikirim oleh ArticleWorkflow dan ArticleController; baru diteruskan ke
 * listener setelah transaksi selesai, sehingga transisi yang batal tidak ikut terkirim.
 */
class ArticlePublished implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Article $article) {}
}
