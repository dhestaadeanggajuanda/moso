<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleAction;
use App\Enums\ArticleCommentType;
use App\Enums\ArticleStatus;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\Category;
use App\Models\User;
use App\Services\Content\HtmlSanitizer;
use App\Services\Storage\SecureUploadService;
use App\Services\Workflow\ArticleWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Article::class);

        /** @var User $user */
        $user = $request->user();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ArticleStatus::class)],
        ]);

        $articles = Article::query()
            ->with(['author:id,name', 'categories:id,name'])
            // Kontributor hanya melihat artikelnya sendiri.
            ->unless($user->can(PermissionName::ArticlesUpdateAny), fn (Builder $query) => $query->whereBelongsTo($user, 'author'))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->searchTitle($search))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'status' => ['value' => $article->status->value, 'label' => $article->status->label()],
                'author' => $article->author->name,
                'categories' => $article->categories->pluck('name'),
                'published_at' => $article->published_at?->toIso8601String(),
                'updated_at' => $article->updated_at?->toIso8601String(),
                'can' => [
                    'view' => $user->can('view', $article),
                    'update' => $user->can('update', $article),
                    'delete' => $user->can('delete', $article),
                ],
            ]);

        return Inertia::render('admin/articles/Index', [
            'articles' => $articles,
            'filters' => (object) $filters,
            'statuses' => ArticleStatus::options(),
            'can' => ['create' => $user->can('create', Article::class)],
            'queue' => $this->reviewQueue($user),
        ]);
    }

    /**
     * Halaman tinjau: pratinjau isi, riwayat persetujuan, dan tombol aksi yang
     * boleh dipakai pengguna (dihitung Policy di server, bukan di frontend).
     */
    public function show(Request $request, Article $article, ArticleWorkflow $workflow): Response
    {
        Gate::authorize('view', $article);

        /** @var User $user */
        $user = $request->user();

        $article->load(['author:id,name', 'categories:id,name', 'comments.user:id,name']);

        return Inertia::render('admin/articles/Show', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                // Sudah disanitasi saat disimpan (HtmlSanitizer), aman untuk v-html.
                'body' => $article->body,
                'image_url' => $article->image_url,
                'image_alt' => $article->image_alt,
                'status' => ['value' => $article->status->value, 'label' => $article->status->label()],
                'author' => $article->author->name,
                'categories' => $article->categories->pluck('name'),
                'published_at' => $article->published_at?->toIso8601String(),
                'updated_at' => $article->updated_at?->toIso8601String(),
                'public_url' => $article->status === ArticleStatus::Published && $article->published_at?->lte(now())
                    ? route('site.articles.show', $article->slug)
                    : null,
            ],
            'history' => $article->comments->map(fn (ArticleComment $entry) => [
                'id' => $entry->id,
                'type' => $entry->type->value,
                'from' => $entry->from_status ? ['value' => $entry->from_status->value, 'label' => $entry->from_status->label()] : null,
                'to' => $entry->to_status ? ['value' => $entry->to_status->value, 'label' => $entry->to_status->label()] : null,
                // Teks polos: ditampilkan dengan interpolasi, bukan v-html.
                'body' => $entry->body,
                'user' => $entry->user->name,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])->values(),
            'actions' => $this->actionOptions($workflow->availableActions($article, $user)),
            'can' => [
                'update' => $user->can('update', $article),
                'delete' => $user->can('delete', $article),
                'comment' => $user->can('comment', $article),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Article::class);

        return Inertia::render('admin/articles/Form', [
            'article' => null,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(ArticleRequest $request, HtmlSanitizer $sanitizer, SecureUploadService $uploads): RedirectResponse
    {
        $article = new Article($this->contentAttributes($request, $sanitizer));
        $article->author()->associate($request->user());

        if ($request->hasFile('image')) {
            $article->image_path = $uploads->storeImage($request->file('image'), 'articles');
        }

        DB::transaction(function () use ($article, $request) {
            $article->save();
            $article->categories()->sync($request->validated('category_ids', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel disimpan sebagai draf.']);

        return to_route('admin.articles.edit', $article);
    }

    public function edit(Request $request, Article $article, ArticleWorkflow $workflow): Response
    {
        Gate::authorize('update', $article);

        /** @var User $user */
        $user = $request->user();

        $article->load('categories:id');

        return Inertia::render('admin/articles/Form', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'image_url' => $article->image_url,
                'image_alt' => $article->image_alt,
                'status' => ['value' => $article->status->value, 'label' => $article->status->label()],
                'category_ids' => $article->categories->pluck('id'),
                'updated_at' => $article->updated_at?->toIso8601String(),
                'can' => ['delete' => $user->can('delete', $article)],
                'actions' => $this->actionOptions($workflow->availableActions($article, $user)),
                'revision_note' => $this->latestRevisionNote($article),
            ],
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(ArticleRequest $request, Article $article, HtmlSanitizer $sanitizer, SecureUploadService $uploads): RedirectResponse
    {
        $article->fill($this->contentAttributes($request, $sanitizer));

        $oldImage = $article->image_path;

        if ($request->hasFile('image')) {
            $article->image_path = $uploads->storeImage($request->file('image'), 'articles');
        } elseif ($request->boolean('remove_image')) {
            $article->image_path = null;
            $article->image_alt = null;
        }

        DB::transaction(function () use ($article, $request) {
            $article->save();
            $article->categories()->sync($request->validated('category_ids', []));
        });

        if ($oldImage !== $article->image_path) {
            $uploads->delete($oldImage);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel diperbarui.']);

        return to_route('admin.articles.edit', $article);
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        // Soft delete: gambar dipertahankan supaya artikel bisa dipulihkan.
        $article->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel dihapus.']);

        return to_route('admin.articles.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function contentAttributes(ArticleRequest $request, HtmlSanitizer $sanitizer): array
    {
        $data = $request->safe()->only(['title', 'slug', 'excerpt', 'body', 'image_alt']);
        $data['body'] = $sanitizer->sanitize($data['body']);

        return $data;
    }

    /**
     * Jumlah artikel yang menunggu tindakan pengguna ini (null bila bukan tugasnya).
     *
     * @return array{submitted: int|null, ready_to_publish: int|null}
     */
    private function reviewQueue(User $user): array
    {
        $count = fn (ArticleStatus $status): int => Article::query()->where('status', $status)->count();

        return [
            'submitted' => $user->can(PermissionName::ArticlesReview) || $user->can(PermissionName::ArticlesPublish)
                ? $count(ArticleStatus::Submitted)
                : null,
            'ready_to_publish' => $user->can(PermissionName::ArticlesPublish)
                ? $count(ArticleStatus::ReadyToPublish)
                : null,
        ];
    }

    /**
     * Catatan revisi terakhir, ditampilkan di form ubah selama artikel perlu revisi
     * supaya penulis langsung tahu apa yang harus diperbaiki.
     *
     * @return array{body: string, user: string, created_at: string|null}|null
     */
    private function latestRevisionNote(Article $article): ?array
    {
        if ($article->status !== ArticleStatus::RevisionRequired) {
            return null;
        }

        $entry = ArticleComment::query()
            ->whereBelongsTo($article)
            ->where('type', ArticleCommentType::StatusChange)
            ->where('to_status', ArticleStatus::RevisionRequired)
            ->whereNotNull('body')
            ->with('user:id,name')
            ->latest('id')
            ->first();

        return $entry ? [
            'body' => (string) $entry->body,
            'user' => $entry->user->name,
            'created_at' => $entry->created_at?->toIso8601String(),
        ] : null;
    }

    /**
     * @param  list<ArticleAction>  $actions
     * @return list<array{value: string, label: string, requires_note: bool, allows_schedule: bool}>
     */
    private function actionOptions(array $actions): array
    {
        return array_map(fn (ArticleAction $action) => [
            'value' => $action->value,
            'label' => $action->label(),
            'requires_note' => $action->requiresNote(),
            'allows_schedule' => $action->allowsSchedule(),
        ], $actions);
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    private function categoryOptions(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name]);
    }
}
