<?php

namespace App\Enums;

/**
 * Jenis entri riwayat artikel: komentar biasa atau catatan perubahan status.
 */
enum ArticleCommentType: string
{
    case Comment = 'comment';
    case StatusChange = 'status_change';

    public function label(): string
    {
        return match ($this) {
            self::Comment => 'Komentar',
            self::StatusChange => 'Perubahan status',
        };
    }
}
