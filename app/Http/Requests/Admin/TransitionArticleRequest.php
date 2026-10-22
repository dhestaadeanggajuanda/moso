<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleAction;
use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aksi alur persetujuan pada satu artikel. Hak atas aksi dicek lewat ability
 * ArticlePolicy dengan nama yang sama; aksi yang tidak dikenal ditolak validasi.
 */
class TransitionArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');
        $action = ArticleAction::tryFrom((string) $this->input('action'));

        if (! $article instanceof Article) {
            return false;
        }

        return $action === null || ($this->user()?->can($action->ability(), $article) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $action = ArticleAction::tryFrom((string) $this->input('action'));

        return [
            'action' => ['required', Rule::enum(ArticleAction::class)],
            'note' => [Rule::requiredIf($action?->requiresNote() ?? false), 'nullable', 'string', 'max:2000'],
            'published_at' => [
                Rule::prohibitedIf(! ($action?->allowsSchedule() ?? false)),
                'nullable',
                // Tanggal lampau boleh (kegiatan lama yang baru diberitakan); tanggal depan = dijadwalkan.
                'date',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required' => 'Tulis catatan revisi agar penulis tahu apa yang perlu diperbaiki.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'action' => 'aksi',
            'note' => 'catatan',
            'published_at' => 'tanggal terbit',
        ];
    }
}
