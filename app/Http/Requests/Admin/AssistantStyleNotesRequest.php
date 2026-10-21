<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Aturan gaya tulisan hasil belajar asisten AI. Bila dikunci, agentic tidak
 * lagi memperbaruinya dari koreksi editor. Hanya Super Admin.
 */
class AssistantStyleNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['array', 'max:12'],
            'notes.*' => ['required', 'string', 'max:300'],
            'locked' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'notes' => 'aturan gaya',
            'notes.*' => 'aturan',
            'locked' => 'kunci',
        ];
    }
}
