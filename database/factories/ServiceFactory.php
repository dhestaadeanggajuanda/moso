<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => rtrim(fake()->unique()->sentence(3), '.'),
            'description' => fake()->paragraph(),
            'requirements' => ['Fotokopi KTP', 'Fotokopi Kartu Keluarga', 'Surat pengantar RT/RW'],
            'procedure' => ['Pemohon mengisi formulir', 'Petugas memverifikasi berkas', 'Dokumen diterbitkan'],
            'processing_time' => '3 hari kerja',
            'cost' => 'Gratis',
            'product' => 'Surat keterangan',
            'complaint_channel' => 'Kotak pengaduan, SP4N-LAPOR!, atau email dinas',
            'sort_order' => 0,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => true]);
    }
}
