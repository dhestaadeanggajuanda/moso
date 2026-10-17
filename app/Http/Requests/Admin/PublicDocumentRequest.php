<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicDocumentCategory;
use App\Models\PublicDocument;
use App\Services\Storage\SecureDocumentService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->document();

        return $document
            ? $this->user()?->can('update', $document) ?? false
            : $this->user()?->can('create', PublicDocument::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $document = $this->document();

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash:ascii', Rule::unique(PublicDocument::class)->ignore($document)],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::enum(PublicDocumentCategory::class)],
            'year' => ['nullable', 'integer', 'between:2000,'.((int) now()->format('Y') + 1)],
            'file' => [$document ? 'nullable' : 'required', ...SecureDocumentService::pdfRules()],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'description' => 'deskripsi',
            'category' => 'kategori informasi',
            'year' => 'tahun',
            'file' => 'berkas PDF',
        ];
    }

    private function document(): ?PublicDocument
    {
        $document = $this->route('publicDocument');

        return $document instanceof PublicDocument ? $document : null;
    }
}
