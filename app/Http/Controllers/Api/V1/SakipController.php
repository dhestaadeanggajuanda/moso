<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SakipDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\SakipResource;
use App\Models\ESakip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SakipController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'type' => ['nullable', Rule::enum(SakipDocumentType::class)],
        ]);

        return SakipResource::collection(
            ESakip::query()
                ->published()
                ->when($filters['year'] ?? null, fn (Builder $query, int|string $year) => $query->where('year', $year))
                ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('document_type', $type))
                ->orderByDesc('year')
                ->orderBy('document_type')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
        );
    }
}
