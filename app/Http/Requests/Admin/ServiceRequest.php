<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->service();

        return $service
            ? $this->user()?->can('update', $service) ?? false
            : $this->user()?->can('create', Service::class) ?? false;
    }

    /**
     * Persyaratan dan prosedur diisi lewat textarea (satu baris satu butir),
     * lalu diubah menjadi list sebelum divalidasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'requirements' => $this->linesToList($this->input('requirements')),
            'procedure' => $this->linesToList($this->input('procedure')),
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash:ascii', Rule::unique(Service::class)->ignore($this->service())],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['required', 'array', 'min:1', 'max:50'],
            'requirements.*' => ['string', 'max:500'],
            'procedure' => ['required', 'array', 'min:1', 'max:50'],
            'procedure.*' => ['string', 'max:500'],
            'processing_time' => ['required', 'string', 'max:100'],
            'cost' => ['required', 'string', 'max:100'],
            'product' => ['nullable', 'string', 'max:255'],
            'complaint_channel' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama layanan',
            'description' => 'deskripsi',
            'requirements' => 'persyaratan',
            'procedure' => 'prosedur',
            'processing_time' => 'jangka waktu',
            'cost' => 'biaya/tarif',
            'product' => 'produk layanan',
            'complaint_channel' => 'saluran pengaduan',
            'sort_order' => 'urutan',
        ];
    }

    /**
     * @return list<string>|mixed
     */
    private function linesToList(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\R/', $value) ?: []),
            fn (string $line) => $line !== '',
        ));
    }

    private function service(): ?Service
    {
        $service = $this->route('service');

        return $service instanceof Service ? $service : null;
    }
}
