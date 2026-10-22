<?php

namespace App\Http\Requests\Admin;

use App\Models\Article;
use App\Services\Storage\SecureUploadService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->article();

        return $article
            ? $this->user()?->can('update', $article) ?? false
            : $this->user()?->can('create', Article::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->article();

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash:ascii', Rule::unique(Article::class)->ignore($article)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:500000'],
            'image' => ['nullable', ...SecureUploadService::imageRules()],
            'remove_image' => ['boolean'],
            // Teks alternatif wajib bila artikel punya gambar (WCAG 1.1.1).
            'image_alt' => [
                Rule::requiredIf(fn () => $this->hasFile('image')
                    || ($article?->image_path && ! $this->boolean('remove_image'))),
                'nullable',
                'string',
                'max:255',
            ],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            // Draf asisten AI yang dipakai; kepemilikannya dicek saat ditautkan (ArticleController).
            'ai_draft_id' => ['nullable', 'uuid'],
            // Tombol "Terbitkan" di form (alur Sederhana); haknya dicek ArticlePolicy::publish.
            'publish' => ['boolean'],
            // Tanggal lampau boleh (kegiatan lama yang baru diberitakan); tanggal depan = dijadwalkan.
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'body' => 'isi artikel',
            'excerpt' => 'ringkasan',
            'image' => 'gambar utama',
            'image_alt' => 'teks alternatif gambar',
            'category_ids' => 'kategori',
            'published_at' => 'tanggal terbit',
        ];
    }

    private function article(): ?Article
    {
        $article = $this->route('article');

        return $article instanceof Article ? $article : null;
    }
}
