<?php

namespace App\Http\Requests\Admin;

use App\Enums\SakipDocumentType;
use App\Models\ESakip;
use App\Services\Storage\SecureDocumentService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SakipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sakip = $this->sakip();

        return $sakip
            ? $this->user()?->can('update', $sakip) ?? false
            : $this->user()?->can('create', ESakip::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:2000,'.((int) now()->format('Y') + 1)],
            'document_type' => ['required', Rule::enum(SakipDocumentType::class)],
            'document_title' => ['required', 'string', 'max:255'],
            'file' => [$this->sakip() ? 'nullable' : 'required', ...SecureDocumentService::pdfRules()],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'year' => 'tahun',
            'document_type' => 'jenis dokumen',
            'document_title' => 'judul dokumen',
            'file' => 'berkas PDF',
        ];
    }

    private function sakip(): ?ESakip
    {
        $sakip = $this->route('sakip');

        return $sakip instanceof ESakip ? $sakip : null;
    }
}
