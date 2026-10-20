<?php

namespace App\Services\Agentic;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Klien API moso-agentic (asisten penulis berita), meniru pola
 * MediaCenterApiService.
 *
 * Keamanan:
 * - Permintaan hanya ke base_url dari config dan tidak mengikuti redirect (SSRF, A01).
 * - Foto dikirim sebagai byte, tidak pernah sebagai URL, sehingga agentic tidak mengambil alamat apa pun.
 * - Token tidak pernah di-log, dan log hanya berisi operasi + status, tanpa isi konten (A09).
 * - Tidak pernah melempar exception: kegagalan menghasilkan null/false agar pemanggil
 *   terdegradasi dengan aman (A10).
 */
class AgenticClient
{
    /**
     * Fitur aktif dan terkonfigurasi lengkap.
     */
    public function enabled(): bool
    {
        return (bool) config('agentic.enabled')
            && filled(config('agentic.base_url'))
            && filled(config('agentic.token'));
    }

    /**
     * Identitas penulis untuk agentic: HMAC ID pengguna, jadi ID asli tidak pernah dikirim.
     */
    public function userReference(int $userId): string
    {
        return substr(hash_hmac('sha256', (string) $userId, (string) config('app.key')), 0, 40);
    }

    /**
     * Antrekan pembuatan draf. Tidak diulang bila server sudah menjawab, agar draf tidak terbuat dua kali.
     *
     * @param  array{kind: string, title: string, description?: string|null, extra_facts?: string|null, instruction?: string|null, previous_draft_id?: string|null, user_ref: string, categories?: list<array{slug: string, name: string}>}  $input
     * @return array{id: string, status: string, stage: string|null}|null
     */
    public function createDraft(array $input, ?string $imageContents = null, ?string $imageFilename = null): ?array
    {
        $parts = [];

        foreach (['kind', 'title', 'description', 'extra_facts', 'instruction', 'previous_draft_id', 'user_ref'] as $field) {
            if (filled($input[$field] ?? null)) {
                $parts[] = ['name' => $field, 'contents' => (string) $input[$field]];
            }
        }

        foreach ($input['categories'] ?? [] as $index => $category) {
            $parts[] = ['name' => "categories[{$index}][slug]", 'contents' => $category['slug']];
            $parts[] = ['name' => "categories[{$index}][name]", 'contents' => $category['name']];
        }

        $data = $this->data($this->send('drafts.create', retryServerErrors: false, request: function (PendingRequest $http) use ($parts, $imageContents, $imageFilename): Response {
            $http->asMultipart();

            if ($imageContents !== null) {
                $http->attach('image', $imageContents, $imageFilename ?? 'foto.jpg');
            }

            return $http->post('drafts', $parts);
        }));

        return isset($data['id']) && is_string($data['id']) && Str::isUuid($data['id']) ? [
            'id' => $data['id'],
            'status' => (string) ($data['status'] ?? 'queued'),
            'stage' => is_string($data['stage'] ?? null) ? $data['stage'] : null,
        ] : null;
    }

    /**
     * Status dan hasil satu draf (null bila gagal atau bukan milik dinas ini).
     *
     * @return array<string, mixed>|null
     */
    public function draft(string $remoteId): ?array
    {
        return $this->data($this->send('drafts.show', fn (PendingRequest $http): Response => $http->get('drafts/'.rawurlencode($remoteId))));
    }

    /**
     * Simpan atau perbarui artikel terbit di memori agentic.
     *
     * @param  array{title: string, excerpt: string|null, body_html: string, published_at: string, url: string|null, draft_id: string|null}  $payload
     */
    public function upsertArticle(int $externalId, array $payload): bool
    {
        return $this->send('articles.upsert', fn (PendingRequest $http): Response => $http->put("articles/{$externalId}", $payload)) !== null;
    }

    /**
     * Hapus artikel dari memori. Artikel yang memang belum ada (404) dianggap berhasil.
     */
    public function deleteArticle(int $externalId): bool
    {
        return $this->send('articles.delete', fn (PendingRequest $http): Response => $http->delete("articles/{$externalId}"), allowNotFound: true) !== null;
    }

    /**
     * @return array{profile: array<string, mixed>, updated_at: string|null}|null
     */
    public function profile(): ?array
    {
        return $this->profileData($this->data($this->send('profile.show', fn (PendingRequest $http): Response => $http->get('profile'))));
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array{profile: array<string, mixed>, updated_at: string|null}|null
     */
    public function putProfile(array $profile): ?array
    {
        return $this->profileData($this->data($this->send('profile.update', fn (PendingRequest $http): Response => $http->put('profile', ['profile' => $profile]))));
    }

    /**
     * @return array{notes: list<string>, locked: bool, sample_count: int, updated_at: string|null}|null
     */
    public function styleNotes(): ?array
    {
        return $this->styleNotesData($this->data($this->send('style_notes.show', fn (PendingRequest $http): Response => $http->get('style-notes'))));
    }

    /**
     * @param  list<string>  $notes
     * @return array{notes: list<string>, locked: bool, sample_count: int, updated_at: string|null}|null
     */
    public function putStyleNotes(array $notes, bool $locked): ?array
    {
        return $this->styleNotesData($this->data($this->send('style_notes.update', fn (PendingRequest $http): Response => $http->put('style-notes', [
            'notes' => $notes,
            'locked' => $locked,
        ]))));
    }

    /**
     * Kosongkan aturan gaya hasil belajar dan buka kuncinya.
     */
    public function resetStyleNotes(): bool
    {
        return $this->send('style_notes.reset', fn (PendingRequest $http): Response => $http->delete('style-notes')) !== null;
    }

    /**
     * @return array{indexed_articles: int, drafts_30d: int, last_draft_at: string|null}|null
     */
    public function stats(): ?array
    {
        $data = $this->data($this->send('stats', fn (PendingRequest $http): Response => $http->get('stats')));

        return $data === null ? null : [
            'indexed_articles' => (int) ($data['indexed_articles'] ?? 0),
            'drafts_30d' => (int) ($data['drafts_30d'] ?? 0),
            'last_draft_at' => is_string($data['last_draft_at'] ?? null) ? $data['last_draft_at'] : null,
        ];
    }

    /**
     * Jalankan satu permintaan. Mengembalikan respons sukses, atau null bila
     * fitur mati, koneksi gagal, atau server menjawab selain 2xx.
     *
     * @param  callable(PendingRequest): Response  $request
     */
    private function send(string $operation, callable $request, bool $retryServerErrors = true, bool $allowNotFound = false): ?Response
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = $request($this->client($retryServerErrors));
        } catch (Throwable $e) {
            Log::warning('Asisten AI tidak dapat diakses.', [
                'operation' => $operation,
                'error' => class_basename($e),
            ]);

            return null;
        }

        if ($response->successful() || ($allowNotFound && $response->notFound())) {
            return $response;
        }

        Log::warning('Asisten AI menolak permintaan.', [
            'operation' => $operation,
            'status' => $response->status(),
        ]);

        return null;
    }

    private function client(bool $retryServerErrors): PendingRequest
    {
        return Http::baseUrl(config('agentic.base_url').'/api/v1')
            ->withToken((string) config('agentic.token'))
            // Jangan ikuti redirect: server tidak boleh diarahkan ke host lain/internal (SSRF, A01).
            ->withoutRedirecting()
            ->acceptJson()
            ->withUserAgent('MosoPortal/3 (+'.config('app.url').')')
            ->connectTimeout((int) config('agentic.connect_timeout'))
            ->timeout((int) config('agentic.timeout'))
            // Diulang hanya bila koneksi gagal dibuka atau server error (5xx); respons
            // lambat tidak diulang supaya total waktu tetap di bawah batas request Octane.
            ->retry([200, 500], 0, fn (Throwable $exception): bool => $this->isConnectFailure($exception)
                || ($retryServerErrors && $exception instanceof RequestException && $exception->response->serverError()), throw: false);
    }

    /**
     * Koneksi tidak pernah terbentuk (host tidak terjangkau, ditolak, DNS), sehingga
     * permintaan belum sampai ke server dan aman diulang. Batas waktu baca ("Operation
     * timed out") tidak termasuk karena server mungkin sedang memprosesnya.
     */
    private function isConnectFailure(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            && ! str_contains($exception->getMessage(), 'Operation timed out');
    }

    /**
     * Isi amplop {"data": …} dari respons sukses.
     *
     * @return array<string, mixed>|null
     */
    private function data(?Response $response): ?array
    {
        if ($response === null) {
            return null;
        }

        $data = $response->json('data');

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array{profile: array<string, mixed>, updated_at: string|null}|null
     */
    private function profileData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return [
            'profile' => is_array($data['profile'] ?? null) ? $data['profile'] : [],
            'updated_at' => is_string($data['updated_at'] ?? null) ? $data['updated_at'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array{notes: list<string>, locked: bool, sample_count: int, updated_at: string|null}|null
     */
    private function styleNotesData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return [
            'notes' => is_array($data['notes'] ?? null) ? array_values(array_filter($data['notes'], is_string(...))) : [],
            'locked' => (bool) ($data['locked'] ?? false),
            'sample_count' => (int) ($data['sample_count'] ?? 0),
            'updated_at' => is_string($data['updated_at'] ?? null) ? $data['updated_at'] : null,
        ];
    }
}
