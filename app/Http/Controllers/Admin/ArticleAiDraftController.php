<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleAiDraftRequest;
use App\Models\Article;
use App\Models\ArticleAiDraft;
use App\Models\Category;
use App\Models\User;
use App\Services\Agentic\AgenticClient;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Draf artikel dari asisten AI (moso-agentic).
 *
 * Penyusunan draf berjalan asinkron di agentic (bisa ±1 menit), sedangkan
 * request Octane dibatasi 30 detik: `store` hanya mengantrekan, lalu browser
 * menanyakan `show` setiap 2 detik sampai draf selesai.
 */
class ArticleAiDraftController extends Controller
{
    private const UNAVAILABLE = 'Asisten AI sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.';

    private const STATUSES = ['queued', 'processing', 'done', 'failed'];

    private const STAGES = ['observing', 'recalling', 'writing', 'reviewing'];

    public function store(StoreArticleAiDraftRequest $request, AgenticClient $agentic): JsonResponse
    {
        abort_unless($agentic->enabled(), 404);

        /** @var User $user */
        $user = $request->user();
        $article = $request->article();
        $previousDraftId = $request->validated('previous_draft_id');

        [$image, $imageName] = $this->image($request, $article);

        $remote = $agentic->createDraft([
            'kind' => (string) $request->validated('kind'),
            'title' => (string) $request->validated('title'),
            'description' => $request->validated('description'),
            'extra_facts' => $request->validated('extra_facts'),
            'instruction' => $request->validated('instruction'),
            'previous_draft_id' => $previousDraftId !== null
                ? ArticleAiDraft::query()->whereBelongsTo($user)->whereKey($previousDraftId)->value('remote_id')
                : null,
            'user_ref' => $agentic->userReference($user->id),
            'categories' => $this->categories(),
        ], $image, $imageName);

        if ($remote === null) {
            return response()->json(['message' => self::UNAVAILABLE], 503);
        }

        $draft = new ArticleAiDraft;
        $draft->user()->associate($user);
        $draft->article()->associate($article);
        $draft->remote_id = $remote['id'];
        $draft->save();

        return response()->json([
            'id' => $draft->id,
            'status' => in_array($remote['status'], self::STATUSES, true) ? $remote['status'] : 'queued',
            'stage' => in_array($remote['stage'], self::STAGES, true) ? $remote['stage'] : null,
        ], 202);
    }

    /**
     * Status dan hasil draf. Draf bersifat pribadi: hanya pemiliknya yang boleh
     * membaca, selain itu 404 (termasuk Super Admin, yang melewati Policy lewat Gate::before).
     */
    public function show(Request $request, ArticleAiDraft $aiDraft, AgenticClient $agentic, HtmlSanitizer $sanitizer): JsonResponse
    {
        abort_unless($agentic->enabled(), 404);
        abort_unless($aiDraft->user_id === $request->user()?->id, 404);
        Gate::authorize('create', Article::class);

        $remote = $agentic->draft($aiDraft->remote_id);

        if ($remote === null) {
            return response()->json(['message' => self::UNAVAILABLE], 503);
        }

        $status = in_array($remote['status'] ?? null, self::STATUSES, true) ? $remote['status'] : 'processing';

        return response()->json([
            'id' => $aiDraft->id,
            'status' => $status,
            'stage' => in_array($remote['stage'] ?? null, self::STAGES, true) ? $remote['stage'] : null,
            'error' => $status === 'failed'
                ? (is_string($remote['error'] ?? null) && $remote['error'] !== '' ? Str::limit($remote['error'], 200) : 'Asisten AI gagal menyusun draf. Coba tulis ulang.')
                : null,
            'result' => $status === 'done' && is_array($remote['result'] ?? null)
                ? $this->result($remote['result'], $sanitizer)
                : null,
        ]);
    }

    /**
     * Hasil draf dalam bentuk yang aman untuk browser: HTML disanitasi ulang di
     * moso (agentic adalah layanan eksternal, A05), teks lain berupa teks polos.
     *
     * @param  array<string, mixed>  $result
     * @return array{title_suggestion: string|null, excerpt: string|null, body_html: string, image_alt: string|null, needs_confirmation: list<array{item: string, question: string}>, category_ids: list<int>, word_count: int}
     */
    private function result(array $result, HtmlSanitizer $sanitizer): array
    {
        $needsConfirmation = array_values(array_filter(array_map(
            fn (mixed $entry): ?array => is_array($entry) && filled($entry['item'] ?? null)
                ? ['item' => $this->plainText($entry['item'], 200) ?? '', 'question' => $this->plainText($entry['question'] ?? null, 300) ?? '']
                : null,
            is_array($result['needs_confirmation'] ?? null) ? array_slice($result['needs_confirmation'], 0, 30) : [],
        )));

        $slugs = array_values(array_filter(is_array($result['category_slugs'] ?? null) ? $result['category_slugs'] : [], is_string(...)));

        return [
            'title_suggestion' => $this->plainText($result['title_suggestion'] ?? null, 255),
            'excerpt' => $this->plainText($result['excerpt'] ?? null, 500),
            'body_html' => $sanitizer->sanitize(is_string($result['body_html'] ?? null) ? $result['body_html'] : ''),
            'image_alt' => $this->plainText($result['image_alt'] ?? null, 255),
            'needs_confirmation' => $needsConfirmation,
            'category_ids' => $slugs === [] ? [] : array_values(array_map(
                intval(...),
                Category::query()->whereIn('slug', $slugs)->orderBy('name')->pluck('id')->all(),
            )),
            'word_count' => (int) ($result['word_count'] ?? 0),
        ];
    }

    /**
     * Byte foto untuk agentic: unggahan baru, atau gambar artikel yang tersimpan.
     * Agentic tidak pernah menerima URL, jadi tidak ada permintaan keluar dari sana (SSRF).
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function image(StoreArticleAiDraftRequest $request, ?Article $article): array
    {
        $upload = $request->file('image');

        if ($upload !== null) {
            $contents = file_get_contents((string) $upload->getRealPath());

            return $contents === false ? [null, null] : [$contents, 'foto.'.($upload->extension() ?: 'jpg')];
        }

        if ($article?->image_path === null || ! $request->boolean('use_article_image', true)) {
            return [null, null];
        }

        try {
            $contents = Storage::disk('s3')->get($article->image_path);
        } catch (Throwable $e) {
            $contents = null;
        }

        if ($contents === null) {
            Log::warning('Gambar artikel tidak dapat dibaca untuk asisten AI.', ['article_id' => $article->id]);

            return [null, null];
        }

        return [$contents, 'foto.'.(pathinfo($article->image_path, PATHINFO_EXTENSION) ?: 'webp')];
    }

    /**
     * Daftar kategori yang boleh dipilih agentic (dicocokkan lagi lewat slug saat hasil diterima).
     *
     * @return list<array{slug: string, name: string}>
     */
    private function categories(): array
    {
        return array_values(Category::query()->orderBy('name')->limit(50)->get(['slug', 'name'])
            ->map(fn (Category $category) => ['slug' => $category->slug, 'name' => $category->name])
            ->all());
    }

    private function plainText(mixed $value, int $limit): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = Str::squish(strip_tags($value));

        return $text === '' ? null : Str::limit($text, $limit, '');
    }
}
