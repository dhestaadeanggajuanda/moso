<?php

namespace App\Exceptions;

use App\Enums\ArticleAction;
use App\Enums\ArticleStatus;
use DomainException;

/**
 * Aksi alur persetujuan tidak berlaku untuk status artikel saat ini,
 * misalnya artikel sudah diubah orang lain sejak halaman dibuka.
 */
class InvalidArticleTransition extends DomainException
{
    public static function for(ArticleAction $action, ArticleStatus $current): self
    {
        return new self("Artikel berstatus \"{$current->label()}\" tidak bisa di-\"{$action->label()}\". Muat ulang halaman untuk melihat status terbaru.");
    }
}
