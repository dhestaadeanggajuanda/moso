<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleWorkflowMode;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mengganti alur penerbitan artikel (Sederhana / Bertahap). Hanya Super Admin.
 */
class ArticleWorkflowSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(ArticleWorkflowMode::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['mode' => 'alur penerbitan'];
    }
}
