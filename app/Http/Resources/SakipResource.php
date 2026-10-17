<?php

namespace App\Http\Resources;

use App\Models\ESakip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dokumen E-SAKIP untuk API publik. Path berkas di storage tidak pernah dikirim.
 *
 * @mixin ESakip
 */
class SakipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'document_type' => [
                'value' => $this->document_type->value,
                'label' => $this->document_type->label(),
            ],
            'document_title' => $this->document_title,
            'file_size' => $this->file_size,
            'download_url' => $this->download_url,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
