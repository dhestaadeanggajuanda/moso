<?php

namespace App\Enums;

use App\Support\Settings;

/**
 * Alur penerbitan artikel per website, diatur Super Admin di halaman Pengaturan.
 *
 * - Simple (bawaan): seperti WordPress. Editor, Approver, dan Penulis menerbitkan
 *   langsung dari form artikel; hanya Kontributor yang perlu persetujuan.
 * - Review: alur bertahap empat mata (ajukan, setujui, terbitkan oleh orang lain).
 */
enum ArticleWorkflowMode: string
{
    case Simple = 'simple';
    case Review = 'review';

    public const SETTING_KEY = 'articles.workflow';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Sederhana (seperti WordPress)',
            self::Review => 'Bertahap (ajukan, tinjau, terbitkan)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Simple => 'Editor, Approver, dan Penulis langsung menerbitkan dari form artikel. Tulisan Kontributor tetap diterbitkan oleh Editor.',
            self::Review => 'Setiap artikel diajukan, disetujui Editor, lalu diterbitkan Approver. Penulis tidak bisa menyetujui atau menerbitkan tulisannya sendiri.',
        };
    }

    public static function current(): self
    {
        return self::tryFrom((string) app(Settings::class)->get(self::SETTING_KEY)) ?? self::Simple;
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $mode) => ['value' => $mode->value, 'label' => $mode->label(), 'description' => $mode->description()],
            self::cases(),
        );
    }
}
