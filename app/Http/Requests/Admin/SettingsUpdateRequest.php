<?php

namespace App\Http\Requests\Admin;

use App\Enums\SiteLogo;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Nilai terenkripsi yang dikirim kosong berarti "tidak diubah".
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'max:200'],
            'settings.*.key' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9_]+(\.[a-z0-9_]+)*$/', 'distinct',
                // Path logo hanya boleh berasal dari unggahan (SiteLogoController).
                Rule::notIn(SiteLogo::settingKeys()),
            ],
            'settings.*.value' => ['nullable', 'string', 'max:5000'],
            'settings.*.is_encrypted' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.*.key.regex' => 'Key hanya boleh huruf kecil, angka, garis bawah, dipisah titik (mis. contact.phone).',
            'settings.*.key.not_in' => 'Logo diatur lewat unggahan di bagian Logo, bukan sebagai teks.',
        ];
    }
}
