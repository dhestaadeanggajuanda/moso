<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PublicDocumentCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicDocumentResource;
use App\Models\PublicDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PublicDocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category' => ['nullable', Rule::enum(PublicDocumentCategory::class)],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        return PublicDocumentResource::collection(
            PublicDocument::query()
                ->published()
                ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
                ->when($filters['year'] ?? null, fn (Builder $query, int|string $year) => $query->where('year', $year))
                ->orderByDesc('year')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
        );
    }
}
