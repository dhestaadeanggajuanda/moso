<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use App\Services\Storage\SecureUploadService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Unggahan foto untuk blok Page Builder (mis. foto latar banner). Hanya
 * pengguna yang boleh mengubah halaman tersebut yang boleh mengunggah.
 */
class UploadBuilderImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->route('page');

        return $page instanceof Page && ($this->user()?->can('update', $page) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', ...SecureUploadService::imageRules()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['image' => 'foto'];
    }
}
