<?php

namespace App\Exceptions;

use App\Enums\ArticleAction;
use App\Enums\ArticleStatus;
use App\Services\Agentic\PlaceholderGuard;
use DomainException;

/**
 * Aksi alur persetujuan tidak berlaku untuk artikel saat ini, misalnya artikel
 * sudah diubah orang lain sejak halaman dibuka, atau isinya belum lengkap.
 */
class InvalidArticleTransition extends DomainException
{
    public static function for(ArticleAction $action, ArticleStatus $current): self
    {
        return new self("Artikel berstatus \"{$current->label()}\" tidak bisa di-\"{$action->label()}\". Muat ulang halaman untuk melihat status terbaru.");
    }

    /**
     * Isi masih memuat penanda "[Perlu dikonfirmasi: …]" dari draf asisten AI.
     */
    public static function unconfirmedPlaceholders(): self
    {
        return new self(PlaceholderGuard::MESSAGE);
    }
}
