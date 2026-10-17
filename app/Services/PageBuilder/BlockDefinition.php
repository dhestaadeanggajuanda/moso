<?php

namespace App\Services\PageBuilder;

/**
 * Kontrak satu tipe blok Page Builder.
 *
 * Props blok disimpan di pages.content_blocks; data dinamis (berita, agenda)
 * tidak pernah disimpan, melainkan diambil lewat hydrate() saat render.
 */
interface BlockDefinition
{
    /**
     * Nama tipe, sama persis dengan nama komponen tema (mis. "NewsGridHybrid").
     */
    public function type(): string;

    public function label(): string;

    public function description(): string;

    /**
     * Nilai bawaan sekaligus daftar props yang diizinkan.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array;

    /**
     * Aturan validasi props.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * @return array<string, string>
     */
    public function messages(): array;

    /**
     * @return array<string, string>
     */
    public function attributes(): array;

    /**
     * Bersihkan props yang sudah tervalidasi sebelum disimpan.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public function normalize(array $props): array;

    /**
     * Data dinamis untuk dirender, atau null bila blok tidak butuh data.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>|null
     */
    public function hydrate(array $props): ?array;
}
