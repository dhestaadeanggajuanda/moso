<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Support\ReservedSlugs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Metadata halaman. Isi `content_blocks` disunting lewat Page Builder (Fase 3)
 * dan divalidasi terhadap BlockRegistry di sana, jadi tidak diterima di sini.
 */
class PageRequest extends FormRequest
{
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
                Rule::notIn(ReservedSlugs::LIST),
                Rule::unique(Page::class)->ignore($this->page()),
            ],
            'status' => ['required', Rule::enum(PageStatus::class)],
            // Kosong = ikut tema aktif situs. Hanya tema yang dikompilasi yang boleh dipilih.
            'theme' => ['nullable', 'string', Rule::in(config('themes.enabled'))],
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
        return ['title' => 'judul', 'meta_description' => 'deskripsi meta', 'theme' => 'tema'];
    }

    private function page(): ?Page
    {
        $page = $this->route('page');

        return $page instanceof Page ? $page : null;
    }
}
