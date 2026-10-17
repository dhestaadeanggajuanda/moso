<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Standar Pelayanan untuk API publik. Hanya kolom yang memang untuk publik.
 *
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'procedure' => $this->procedure,
            'processing_time' => $this->processing_time,
            'cost' => $this->cost,
            'product' => $this->product,
            'complaint_channel' => $this->complaint_channel,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
