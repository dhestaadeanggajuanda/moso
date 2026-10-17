<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Metadata halaman. Isi `content_blocks` disunting lewat Page Builder (Fase 3)
 * dan divalidasi terhadap BlockRegistry di sana, jadi tidak diterima di sini.
 */
class PageRequest extends FormRequest
{
    /**
     * Slug yang bentrok dengan route aplikasi atau modul publik.
     */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'agenda', 'berita', 'build', 'dashboard', 'email', 'forgot-password',
        'layanan', 'login', 'logout', 'ppid', 'register', 'reset-password', 'sakip', 'settings',
        'storage', 'two-factor-challenge', 'up', 'user',
    ];

    public function authorize(): bool
    {
        $page = $this->page();

        return $page
            ? $this->user()?->can('update', $page) ?? false
            : $this->user()?->can('create', Page::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:200', 'alpha_dash:ascii',
                Rule::notIn(self::RESERVED_SLUGS),
                Rule::unique(Page::class)->ignore($this->page()),
            ],
            'status' => ['required', Rule::enum(PageStatus::class)],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['slug.not_in' => 'Slug ini dipakai oleh sistem. Gunakan slug lain.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'judul', 'meta_description' => 'deskripsi meta'];
    }

    private function page(): ?Page
    {
        $page = $this->route('page');

        return $page instanceof Page ? $page : null;
    }
}
