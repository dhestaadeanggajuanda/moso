<?php

namespace App\Services\MediaCenter;

use App\Enums\MediaCenterMode;
use App\Services\News\NewsItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use UnexpectedValueException;

/**
 * Mengambil berita lintas sektoral dari Media Center Kota Singkawang (WordPress REST API).
 *
 * Keamanan:
 * - Permintaan hanya ke base_url dari config; kata kunci divalidasi ketat (anti-SSRF, A01).
 * - Judul/ringkasan dibersihkan menjadi teks polos; tautan dan gambar hanya
 *   diterima dari host Media Center sendiri (A05).
 * - Kegagalan tidak di-cache dan tidak memutus halaman: pemanggil menerima
 *   daftar kosong dan kejadian dicatat di log (A10).
 */
class MediaCenterApiService
{
    public const KEYWORD_PATTERN = '/^[a-z0-9-]{1,50}$/';

    /**
     * Berita terbaru untuk satu kata kunci. Tidak pernah melempar exception.
     *
     * @return list<NewsItem>
     */
    public function fetch(string $keyword, MediaCenterMode $mode = MediaCenterMode::Tag, int $limit = 6): array
    {
        if (! preg_match(self::KEYWORD_PATTERN, $keyword)) {
            Log::warning('Kata kunci Media Center ditolak.', ['keyword' => Str::limit($keyword, 60)]);

            return [];
        }

        $limit = max(1, min($limit, (int) config('mediacenter.max_items')));

        try {
            /** @var list<array{id: string, source: string, title: string, excerpt: ?string, url: string, image_url: ?string, image_alt: ?string, published_at: ?string}> $rows */
            $rows = Cache::flexible(
                $this->cacheKey($keyword, $mode, $limit),
                [(int) config('mediacenter.cache.fresh'), (int) config('mediacenter.cache.stale')],
                fn (): array => $this->request($keyword, $mode, $limit),
            );
        } catch (Throwable $e) {
            Log::warning('Media Center tidak dapat diakses; menampilkan berita lokal saja.', [
                'keyword' => $keyword,
                'mode' => $mode->value,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        return array_map(NewsItem::fromArray(...), $rows);
    }

    /**
     * Ambil ulang dari Media Center dan ganti isi cache (dipakai mediacenter:warm).
     *
     * Cache lama hanya diganti bila permintaan berhasil, sehingga gangguan
     * sesaat di Media Center tidak menghapus berita yang sudah tersimpan.
     *
     * @throws Throwable bila Media Center tidak dapat diakses atau kata kunci tidak valid
     */
    public function refresh(string $keyword, MediaCenterMode $mode, int $limit): int
    {
        if (! preg_match(self::KEYWORD_PATTERN, $keyword)) {
            throw new UnexpectedValueException('Kata kunci Media Center tidak valid.');
        }

        $limit = max(1, min($limit, (int) config('mediacenter.max_items')));
        $rows = $this->request($keyword, $mode, $limit);
        $key = $this->cacheKey($keyword, $mode, $limit);

        Cache::forget($key);
        Cache::flexible($key, [(int) config('mediacenter.cache.fresh'), (int) config('mediacenter.cache.stale')], fn (): array => $rows);

        return count($rows);
    }

    private function cacheKey(string $keyword, MediaCenterMode $mode, int $limit): string
    {
        return "mediacenter:posts:{$mode->value}:{$keyword}:{$limit}";
    }

    /**
     * @return list<array{id: string, source: string, title: string, excerpt: ?string, url: string, image_url: ?string, image_alt: ?string, published_at: ?string}>
     */
    private function request(string $keyword, MediaCenterMode $mode, int $limit): array
    {
        $query = [
            'per_page' => $limit,
            '_embed' => 'wp:featuredmedia',
            '_fields' => 'id,date_gmt,link,title,excerpt,_links,_embedded',
        ];

        $taxonomy = $mode->taxonomy();

        if ($taxonomy === null) {
            $query['search'] = $keyword;
        } else {
            $termId = $this->resolveTermId($taxonomy, $keyword);

            if ($termId === null) {
                return [];
            }

            $query[$taxonomy] = $termId;
        }

        $posts = $this->client()->get('/wp-json/wp/v2/posts', $query)->throw()->json();

        if (! is_array($posts)) {
            throw new UnexpectedValueException('Respons Media Center bukan daftar post.');
        }

        return array_values(array_filter(array_map($this->normalize(...), $posts)));
    }

    /**
     * ID term WordPress untuk slug tag/kategori (di-cache sehari).
     */
    private function resolveTermId(string $taxonomy, string $slug): ?int
    {
        /** @var int|null */
        return Cache::remember("mediacenter:term:{$taxonomy}:{$slug}", now()->plus(days: 1), function () use ($taxonomy, $slug): ?int {
            $terms = $this->client()
                ->get("/wp-json/wp/v2/{$taxonomy}", ['slug' => $slug, '_fields' => 'id'])
                ->throw()
                ->json();

            $id = is_array($terms) ? ($terms[0]['id'] ?? null) : null;

            return is_int($id) ? $id : null;
        });
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('mediacenter.base_url'))
            ->acceptJson()
            ->withUserAgent('MosoPortal/3 (+'.config('app.url').')')
            ->connectTimeout((int) config('mediacenter.connect_timeout'))
            ->timeout((int) config('mediacenter.timeout'))
            // GET aman diulang: hanya untuk gangguan koneksi, error server, dan 429.
            ->retry([200, 500], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException
                    && ($exception->response->serverError() || $exception->response->status() === 429)));
    }

    /**
     * @param  mixed  $post  Satu item dari respons WordPress
     * @return array{id: string, source: string, title: string, excerpt: ?string, url: string, image_url: ?string, image_alt: ?string, published_at: ?string}|null
     */
    private function normalize(mixed $post): ?array
    {
        if (! is_array($post) || ! isset($post['id'], $post['link'])) {
            return null;
        }

        $url = $this->trustedUrl($post['link']);
        $title = $this->plainText($post['title']['rendered'] ?? null);

        if ($url === null || $title === null) {
            return null;
        }

        $media = $post['_embedded']['wp:featuredmedia'][0] ?? null;
        $imageUrl = is_array($media)
            ? $this->trustedUrl($media['media_details']['sizes']['medium']['source_url'] ?? $media['source_url'] ?? null)
            : null;

        return [
            'id' => 'mc-'.$post['id'],
            'source' => NewsItem::SOURCE_MEDIA_CENTER,
            'title' => Str::limit($title, 200),
            'excerpt' => ($excerpt = $this->plainText($post['excerpt']['rendered'] ?? null)) !== null
                ? Str::limit($excerpt, 220)
                : null,
            'url' => $url,
            'image_url' => $imageUrl,
            'image_alt' => $imageUrl !== null ? ($this->plainText($media['alt_text'] ?? null) ?? $title) : null,
            'published_at' => is_string($post['date_gmt'] ?? null)
                ? CarbonImmutable::parse($post['date_gmt'], 'UTC')->setTimezone(config('app.timezone'))->toIso8601String()
                : null,
        ];
    }

    /**
     * URL http(s) yang hostnya sama dengan Media Center; selain itu ditolak.
     */
    private function trustedUrl(mixed $url): ?string
    {
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        $trustedHost = parse_url((string) config('mediacenter.base_url'), PHP_URL_HOST);

        return in_array($parts['scheme'] ?? null, ['http', 'https'], true) && ($parts['host'] ?? null) === $trustedHost
            ? $url
            : null;
    }

    private function plainText(mixed $html): ?string
    {
        if (! is_string($html)) {
            return null;
        }

        $text = Str::squish(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = trim((string) preg_replace('/\s*\[(…|&hellip;|\.\.\.)\]\s*$/u', '…', $text));

        return $text === '' ? null : $text;
    }
}
