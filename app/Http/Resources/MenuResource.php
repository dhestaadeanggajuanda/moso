<?php

namespace App\Http\Resources;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Item menu publik beserta sub-menunya (rekursif).
 *
 * @mixin Menu
 */
class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->label,
            'href' => $this->href,
            'open_in_new_tab' => $this->open_in_new_tab,
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
