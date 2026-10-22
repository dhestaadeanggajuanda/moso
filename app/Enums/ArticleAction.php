<?php

namespace App\Enums;

/**
 * Aksi alur persetujuan artikel. Setiap aksi memindahkan artikel dari satu
 * atau beberapa status asal ke satu status tujuan, dan dicek lewat ability
 * dengan nama yang sama di ArticlePolicy.
 *
 * draft / perlu revisi --ajukan--> menunggu review --setujui--> siap terbit --terbitkan--> terbit --arsipkan--> diarsipkan
 *                                  menunggu review / siap terbit --minta revisi--> perlu revisi
 *                                  diarsipkan --terbitkan ulang--> terbit
 */
enum ArticleAction: string
{
    case Submit = 'submit';
    case Approve = 'approve';
    case RequestRevision = 'request_revision';
    case Publish = 'publish';
    case Archive = 'archive';
    case Unarchive = 'unarchive';

    public function label(): string
    {
        return match ($this) {
            self::Submit => 'Ajukan untuk review',
            self::Approve => 'Setujui, siap terbit',
            self::RequestRevision => 'Minta revisi',
            self::Publish => 'Terbitkan',
            self::Archive => 'Arsipkan',
            self::Unarchive => 'Terbitkan ulang',
        };
    }

    /**
     * Status asal yang mengizinkan aksi ini. Di alur Sederhana artikel bisa
     * langsung diterbitkan dari draf, dan langkah "Setujui" tidak dipakai.
     *
     * @return list<ArticleStatus>
     */
    public function fromStatuses(?ArticleWorkflowMode $mode = null): array
    {
        $simple = ($mode ?? ArticleWorkflowMode::current()) === ArticleWorkflowMode::Simple;

        return match ($this) {
            self::Submit => [ArticleStatus::Draft, ArticleStatus::RevisionRequired],
            self::Approve => $simple ? [] : [ArticleStatus::Submitted],
            self::RequestRevision => [ArticleStatus::Submitted, ArticleStatus::ReadyToPublish],
            self::Publish => $simple
                ? [ArticleStatus::Draft, ArticleStatus::Submitted, ArticleStatus::RevisionRequired, ArticleStatus::ReadyToPublish]
                : [ArticleStatus::ReadyToPublish],
            self::Archive => [ArticleStatus::Published],
            self::Unarchive => [ArticleStatus::Archived],
        };
    }

    public function to(): ArticleStatus
    {
        return match ($this) {
            self::Submit => ArticleStatus::Submitted,
            self::Approve => ArticleStatus::ReadyToPublish,
            self::RequestRevision => ArticleStatus::RevisionRequired,
            self::Publish, self::Unarchive => ArticleStatus::Published,
            self::Archive => ArticleStatus::Archived,
        };
    }

    /**
     * Pesan setelah aksi berhasil.
     */
    public function doneMessage(): string
    {
        return match ($this) {
            self::Submit => 'Artikel diajukan untuk review.',
            self::Approve => 'Artikel disetujui dan siap diterbitkan.',
            self::RequestRevision => 'Artikel dikembalikan ke penulis untuk revisi.',
            self::Publish => 'Artikel diterbitkan.',
            self::Archive => 'Artikel diarsipkan.',
            self::Unarchive => 'Artikel diterbitkan ulang.',
        };
    }

    public function allowsFrom(ArticleStatus $status, ?ArticleWorkflowMode $mode = null): bool
    {
        return in_array($status, $this->fromStatuses($mode), true);
    }

    /**
     * Permintaan revisi wajib menyertakan catatan agar penulis tahu apa yang diperbaiki.
     */
    public function requiresNote(): bool
    {
        return $this === self::RequestRevision;
    }

    /**
     * Hanya penerbitan yang boleh dijadwalkan.
     */
    public function allowsSchedule(): bool
    {
        return $this === self::Publish;
    }

    /**
     * Nama ability di ArticlePolicy.
     */
    public function ability(): string
    {
        return lcfirst(str_replace('_', '', ucwords($this->value, '_')));
    }
}
