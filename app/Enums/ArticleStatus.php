<?php

namespace App\Enums;

/**
 * Status artikel dalam alur persetujuan berjenjang.
 *
 * draft → submitted → ready_to_publish | revision_required → published → archived
 */
enum ArticleStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case RevisionRequired = 'revision_required';
    case ReadyToPublish = 'ready_to_publish';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Menunggu Review',
            self::RevisionRequired => 'Perlu Revisi',
            self::ReadyToPublish => 'Siap Terbit',
            self::Published => 'Terbit',
            self::Archived => 'Diarsipkan',
        };
    }

    /**
     * Status yang masih boleh diubah isinya oleh penulis (Kontributor).
     */
    public function isEditableByAuthor(): bool
    {
        return in_array($this, [self::Draft, self::RevisionRequired], true);
    }

    /**
     * Status sesudah diterbitkan Approver. Mengubah atau menghapusnya butuh hak
     * terbitkan, agar isi yang tayang tidak berubah tanpa prinsip empat mata.
     */
    public function requiresPublisher(): bool
    {
        return in_array($this, [self::Published, self::Archived], true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
