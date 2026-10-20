<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Profil dinas untuk asisten AI: dipakai agentic sebagai sumber fakta tetap
 * (nama resmi, tugas, pejabat) saat menyusun draf. Hanya Super Admin.
 */
class AssistantProfileRequest extends FormRequest
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
            'official_name' => ['required', 'string', 'max:200'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'duties' => ['nullable', 'string', 'max:3000'],
            'officials' => ['array', 'max:30'],
            'officials.*.name' => ['required', 'string', 'max:150'],
            'officials.*.position' => ['required', 'string', 'max:150'],
            'programs' => ['array', 'max:30'],
            'programs.*' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact' => ['nullable', 'string', 'max:500'],
            'style_guide' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'official_name' => 'nama resmi',
            'short_name' => 'singkatan',
            'duties' => 'tugas dan fungsi',
            'officials' => 'pejabat',
            'officials.*.name' => 'nama pejabat',
            'officials.*.position' => 'jabatan',
            'programs' => 'program rutin',
            'programs.*' => 'program',
            'address' => 'alamat',
            'contact' => 'kontak',
            'style_guide' => 'catatan gaya',
        ];
    }

    /**
     * Profil dalam bentuk kontrak API agentic.
     *
     * @return array{official_name: string, short_name: string|null, duties: string|null, officials: list<array{name: string, position: string}>, programs: list<string>, address: string|null, contact: string|null, style_guide: string|null}
     */
    public function profile(): array
    {
        return [
            'official_name' => (string) $this->validated('official_name'),
            'short_name' => $this->validated('short_name'),
            'duties' => $this->validated('duties'),
            'officials' => array_values(array_map(
                fn (array $official) => ['name' => (string) $official['name'], 'position' => (string) $official['position']],
                $this->validated('officials', []),
            )),
            'programs' => array_values(array_map('strval', $this->validated('programs', []))),
            'address' => $this->validated('address'),
            'contact' => $this->validated('contact'),
            'style_guide' => $this->validated('style_guide'),
        ];
    }
}
