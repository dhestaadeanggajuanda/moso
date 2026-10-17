<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Akses pengaturan situs ber-cache.
 *
 * Nilai terenkripsi disimpan sebagai ciphertext di database dan di cache, lalu
 * baru didekripsi saat dibaca, supaya rahasia tidak pernah tersimpan dalam
 * bentuk plaintext (OWASP A04).
 */
class Settings
{
    private const CACHE_KEY = 'settings.all';

    public function get(string $key, ?string $default = null): ?string
    {
        $item = $this->all()[$key] ?? null;

        if ($item === null || $item['value'] === null) {
            return $default;
        }

        if (! $item['encrypted']) {
            return $item['value'];
        }

        try {
            return Crypt::decryptString($item['value']);
        } catch (DecryptException $e) {
            // Biasanya terjadi setelah APP_KEY dirotasi: laporkan, jangan sampai halaman error.
            report($e);

            return $default;
        }
    }

    public function put(string $key, ?string $value, bool $encrypted = false): Setting
    {
        $setting = Setting::query()->updateOrCreate(['key' => $key], [
            'value' => $value !== null && $encrypted ? Crypt::encryptString($value) : $value,
            'is_encrypted' => $encrypted,
        ]);

        $this->flush();

        return $setting;
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array{value: string|null, encrypted: bool}>
     */
    private function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::query()
            ->get(['key', 'value', 'is_encrypted'])
            ->mapWithKeys(fn (Setting $setting) => [
                $setting->key => ['value' => $setting->value, 'encrypted' => $setting->is_encrypted],
            ])
            ->all());
    }
}
