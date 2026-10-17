<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use App\Services\PageBuilder\BlockRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi isi Page Builder terhadap BlockRegistry (OWASP A08): tipe blok
 * harus terdaftar dan props tiap blok divalidasi dengan aturan tipenya.
 */
class SavePageBlocksRequest extends FormRequest
{
    public const MAX_BLOCKS = 50;

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
            'blocks' => ['present', 'array', 'max:'.self::MAX_BLOCKS],
            'blocks.*.id' => ['required', 'uuid', 'distinct'],
            'blocks.*.type' => ['required', 'string', Rule::in($this->registry()->types())],
            'blocks.*.props' => ['present', 'array'],
        ];
    }

    /**
     * Validasi props setiap blok dengan aturan dari definisi tipenya.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $blocks = $this->input('blocks');

                if (! is_array($blocks)) {
                    return;
                }

                foreach ($blocks as $index => $block) {
                    $definition = is_array($block) && is_string($block['type'] ?? null)
                        ? $this->registry()->get($block['type'])
                        : null;

                    if ($definition === null || ! is_array($block['props'] ?? null)) {
                        continue;
                    }

                    $props = ValidatorFactory::make(
                        $block['props'],
                        $definition->rules(),
                        $definition->messages(),
                        $definition->attributes(),
                    );

                    foreach ($props->errors()->messages() as $key => $messages) {
                        foreach ($messages as $message) {
                            $validator->errors()->add("blocks.{$index}.props.{$key}", $message);
                        }
                    }
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
            'blocks.max' => 'Halaman maksimal '.self::MAX_BLOCKS.' blok.',
            'blocks.*.type.in' => 'Tipe blok tidak dikenal.',
        ];
    }

    private function registry(): BlockRegistry
    {
        return $this->container->make(BlockRegistry::class);
    }
}
