<?php

namespace App\Http\Requests\Admin;

use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Support\SafeUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MenuRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
            'page_id' => $this->filled('page_id') ? $this->input('page_id') : null,
            'url' => $this->filled('url') ? $this->input('url') : null,
        ]);
    }

    public function authorize(): bool
    {
        $menu = $this->menu();

        return $menu
            ? $this->user()?->can('update', $menu) ?? false
            : $this->user()?->can('create', Menu::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'location' => ['required', Rule::enum(MenuLocation::class)],
            'parent_id' => ['nullable', 'integer', Rule::exists('menus', 'id')],
            'label' => ['required', 'string', 'max:100'],
            'page_id' => ['nullable', 'integer', Rule::exists('pages', 'id'), 'required_without:url'],
            'url' => ['nullable', 'string', 'max:500', 'required_without:page_id', 'prohibits:page_id', 'regex:'.SafeUrl::PATTERN],
            'open_in_new_tab' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Validasi struktur tree: induk harus satu lokasi, tidak boleh membentuk
     * siklus, dan kedalaman maksimal Menu::MAX_DEPTH.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['location', 'parent_id']) || ! $this->filled('parent_id')) {
                    return;
                }

                $parent = Menu::query()->findOrFail($this->integer('parent_id'));
                $menu = $this->menu();

                if ($parent->location->value !== $this->input('location')) {
                    $validator->errors()->add('parent_id', 'Induk menu harus berada di lokasi yang sama.');

                    return;
                }

                if ($menu && ($parent->id === $menu->id || in_array($parent->id, $menu->descendantIds(), true))) {
                    $validator->errors()->add('parent_id', 'Menu tidak dapat dipindah ke bawah dirinya sendiri.');

                    return;
                }

                $subtreeHeight = $menu ? $this->subtreeHeight($menu) : 1;

                if ($parent->depth() + $subtreeHeight > Menu::MAX_DEPTH) {
                    $validator->errors()->add('parent_id', 'Menu maksimal '.Menu::MAX_DEPTH.' tingkat.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => SafeUrl::MESSAGE,
            'url.required_without' => 'Isi URL atau pilih halaman tujuan.',
            'page_id.required_without' => 'Pilih halaman tujuan atau isi URL.',
            'url.prohibits' => 'Pilih salah satu: halaman tujuan atau URL.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'location' => 'lokasi menu',
            'parent_id' => 'induk menu',
            'page_id' => 'halaman tujuan',
            'sort_order' => 'urutan',
        ];
    }

    /**
     * Tinggi subtree (item tanpa anak = 1), untuk memastikan anak-anaknya
     * tidak melewati batas kedalaman setelah dipindah.
     */
    private function subtreeHeight(Menu $menu): int
    {
        $height = 1;
        $frontier = [$menu->id];

        while ($frontier !== [] && $height <= Menu::MAX_DEPTH) {
            $frontier = Menu::query()->whereIn('parent_id', $frontier)->pluck('id')->all();

            if ($frontier !== []) {
                $height++;
            }
        }

        return $height;
    }

    private function menu(): ?Menu
    {
        $menu = $this->route('menu');

        return $menu instanceof Menu ? $menu : null;
    }
}
