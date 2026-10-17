<?php

namespace App\Http\Requests\Admin;

use App\Models\Agenda;
use App\Services\Storage\SecureUploadService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agenda = $this->agenda();

        return $agenda
            ? $this->user()?->can('update', $agenda) ?? false
            : $this->user()?->can('create', Agenda::class) ?? false;
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
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash:ascii', Rule::unique(Agenda::class)->ignore($this->agenda())],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', ...SecureUploadService::imageRules()],
            'remove_image' => ['boolean'],
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
            'location' => 'lokasi',
            'start_at' => 'waktu mulai',
            'end_at' => 'waktu selesai',
            'organizer' => 'penyelenggara',
            'image' => 'gambar',
        ];
    }

    private function agenda(): ?Agenda
    {
        $agenda = $this->route('agenda');

        return $agenda instanceof Agenda ? $agenda : null;
    }
}
