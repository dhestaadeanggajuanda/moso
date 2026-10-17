<?php

namespace App\Http\Resources;

use App\Models\PublicDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dokumen PPID untuk API publik. Path berkas di storage tidak pernah dikirim;
 * unduhan selalu lewat download_url.
 *
 * @mixin PublicDocument
 */
class PublicDocumentResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'category' => [
                'value' => $this->category->value,
                'label' => $this->category->label(),
            ],
            'year' => $this->year,
            'file_size' => $this->file_size,
            'download_url' => $this->download_url,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
