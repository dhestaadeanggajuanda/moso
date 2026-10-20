<?php

namespace App\Http\Requests\Admin;

use App\Enums\AiDraftKind;
use App\Models\Article;
use App\Services\Storage\SecureUploadService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Permintaan draf ke asisten AI dari form artikel.
 *
 * Untuk artikel baru cukup hak membuat artikel; bila `article_id` dikirim,
 * pengguna harus boleh mengubah artikel itu (anti-IDOR, A01), karena gambar
 * artikel tersebut bisa ikut dikirim ke agentic.
 */
class StoreArticleAiDraftRequest extends FormRequest
{
    private ?Article $resolvedArticle = null;

    public function authorize(): bool
    {
        if (! $this->filled('article_id')) {
            return $this->user()?->can('create', Article::class) ?? false;
        }

        $article = $this->article();

        return $article !== null && ($this->user()?->can('update', $article) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(AiDraftKind::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'extra_facts' => ['nullable', 'string', 'max:1000'],
            'instruction' => ['nullable', 'string', 'max:300'],
            // Tulis ulang hanya dari draf milik sendiri.
            'previous_draft_id' => [
                'nullable',
                'uuid',
                Rule::exists('article_ai_drafts', 'id')->where('user_id', $this->user()?->id),
            ],
            'image' => ['nullable', ...SecureUploadService::imageRules()],
            'article_id' => ['nullable', 'integer'],
            // false = gambar artikel yang tersimpan tidak dipakai (mis. akan dihapus).
            'use_article_image' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'kind' => 'jenis tulisan',
            'title' => 'judul',
            'description' => 'ringkasan',
            'extra_facts' => 'info tambahan',
            'instruction' => 'instruksi',
            'previous_draft_id' => 'draf sebelumnya',
            'image' => 'foto',
        ];
    }

    /**
     * Artikel yang sedang diubah, bila ada.
     */
    public function article(): ?Article
    {
        if ($this->resolvedArticle === null && $this->filled('article_id')) {
            $this->resolvedArticle = Article::query()->find($this->integer('article_id'));
        }

        return $this->resolvedArticle;
    }
}
