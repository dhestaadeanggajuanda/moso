<?php

namespace App\Models;

use App\Enums\ArticleCommentType;
use App\Enums\ArticleStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ArticleCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entri riwayat artikel: komentar reviewer/penulis atau catatan perubahan status.
 *
 * `article_id` dan `user_id` sengaja tidak fillable: keduanya diisi dari
 * artikel dan pengguna yang login, bukan dari input.
 *
 * @property int $id
 * @property int $article_id
 * @property int $user_id
 * @property ArticleCommentType $type
 * @property ArticleStatus|null $from_status
 * @property ArticleStatus|null $to_status
 * @property string|null $body
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['type', 'from_status', 'to_status', 'body'])]
class ArticleComment extends Model
{
    /** @use HasFactory<ArticleCommentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ArticleCommentType::class,
            'from_status' => ArticleStatus::class,
            'to_status' => ArticleStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
