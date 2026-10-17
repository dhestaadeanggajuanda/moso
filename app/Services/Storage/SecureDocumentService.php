<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Menyimpan dan menyajikan dokumen PDF (PPID, E-SAKIP) secara aman.
 *
 * Berkas selalu masuk disk privat dengan nama UUID, lalu disajikan lewat URL
 * bertanda tangan berumur pendek. Dengan begitu dokumen yang ditarik dari
 * publikasi langsung tidak bisa diakses lagi (OWASP A01), dan nama berkas dari
 * pengguna tidak pernah dipakai sebagai path atau header (OWASP A05).
 */
class SecureDocumentService
{
    public const DISK = 's3_private';

    public const MAX_KILOBYTES = 20 * 1024;

    /**
     * Aturan validasi standar untuk unggahan PDF.
     *
     * @return list<string>
     */
    public static function pdfRules(): array
    {
        return ['file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.self::MAX_KILOBYTES];
    }

    /**
     * @param  string  $directory  Folder tujuan, mis. "ppid" (huruf kecil, angka, tanda hubung)
     * @param  string  $attribute  Nama field untuk pesan error validasi
     * @return array{path: string, size: int}
     */
    public function storePdf(UploadedFile $file, string $directory, string $attribute = 'file'): array
    {
        if (! preg_match('/^[a-z0-9-]+$/', $directory)) {
            throw new InvalidArgumentException("Direktori unggahan tidak valid: {$directory}");
        }

        // Lapisan tambahan di atas deteksi MIME: berkas PDF selalu diawali "%PDF-".
        if (! $this->hasPdfSignature($file)) {
            throw ValidationException::withMessages([
                $attribute => 'Berkas harus berupa dokumen PDF yang valid.',
            ]);
        }

        $path = Storage::disk(self::DISK)->putFileAs(
            $directory.'/'.now()->format('Y/m'),
            $file,
            Str::uuid().'.pdf',
            ['ContentType' => 'application/pdf'],
        );

        if ($path === false) {
            throw new RuntimeException('Berkas gagal disimpan ke object storage.');
        }

        return ['path' => $path, 'size' => (int) $file->getSize()];
    }

    /**
     * URL bertanda tangan untuk membuka dokumen di browser.
     *
     * @param  string  $downloadName  Nama berkas yang aman (huruf, angka, tanda hubung, titik)
     */
    public function temporaryUrl(string $path, string $downloadName): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9.\-]/', '-', $downloadName) ?: 'dokumen.pdf';

        return Storage::disk(self::DISK)->temporaryUrl($path, now()->plus(minutes: 5), [
            'ResponseContentType' => 'application/pdf',
            'ResponseContentDisposition' => 'inline; filename="'.$safeName.'"',
        ]);
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function hasPdfSignature(UploadedFile $file): bool
    {
        $handle = fopen((string) $file->getRealPath(), 'rb');

        if ($handle === false) {
            return false;
        }

        $signature = fread($handle, 5);
        fclose($handle);

        return $signature === '%PDF-';
    }
}
