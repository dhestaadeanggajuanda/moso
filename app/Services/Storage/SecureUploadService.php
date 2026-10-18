<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Throwable;

/**
 * Menyimpan gambar unggahan secara aman (OWASP A05).
 *
 * Gambar selalu di-decode lalu di-encode ulang menjadi WebP baru: metadata
 * EXIF (termasuk lokasi GPS) terbuang, dan payload polyglot yang
 * disisipkan ke berkas asli ikut hilang. Nama berkas berupa UUID, jadi nama
 * asli dari pengguna tidak pernah dipakai sebagai path.
 *
 * Validasi tipe dan ukuran tetap dilakukan di FormRequest (lihat imageRules()).
 */
class SecureUploadService
{
    public const MAX_WIDTH = 1920;

    public const QUALITY = 82;

    private ImageManager $images;

    public function __construct()
    {
        $this->images = new ImageManager(GdDriver::class);
    }

    /**
     * Aturan validasi standar untuk unggahan gambar.
     *
     * @return list<mixed>
     */
    public static function imageRules(): array
    {
        return [
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
            'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000',
        ];
    }

    /**
     * @param  string  $directory  Folder tujuan, mis. "articles" (huruf kecil, angka, tanda hubung)
     * @param  string  $attribute  Nama field untuk pesan error validasi
     * @param  int  $maxWidth  Lebar maksimum hasil (gambar tidak pernah diperbesar)
     * @return string Path objek di disk
     */
    public function storeImage(
        UploadedFile $file,
        string $directory,
        string $disk = 's3',
        string $attribute = 'image',
        int $maxWidth = self::MAX_WIDTH,
    ): string {
        if (! preg_match('/^[a-z0-9-]+$/', $directory)) {
            throw new InvalidArgumentException("Direktori unggahan tidak valid: {$directory}");
        }

        try {
            $encoded = $this->images->decodePath((string) $file->getRealPath())
                ->orient()
                ->scaleDown(width: $maxWidth)
                ->encode(new WebpEncoder(quality: self::QUALITY, strip: true));
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                $attribute => 'Berkas gambar tidak dapat diproses. Unggah gambar JPG, PNG, atau WebP yang valid.',
            ]);
        }

        $path = $directory.'/'.now()->format('Y/m').'/'.Str::uuid().'.webp';

        Storage::disk($disk)->put($path, $encoded->toString(), [
            'ContentType' => 'image/webp',
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);

        return $path;
    }

    public function delete(?string $path, string $disk = 's3'): void
    {
        if ($path) {
            Storage::disk($disk)->delete($path);
        }
    }
}
