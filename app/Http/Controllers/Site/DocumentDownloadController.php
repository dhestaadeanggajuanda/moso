<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ESakip;
use App\Models\PublicDocument;
use App\Services\Storage\SecureDocumentService;
use Illuminate\Http\RedirectResponse;

/**
 * Unduhan publik dokumen PPID dan E-SAKIP. Dokumen yang belum terbit
 * dibalas 404 agar keberadaannya tidak bocor.
 */
class DocumentDownloadController extends Controller
{
    public function ppid(PublicDocument $publicDocument, SecureDocumentService $documents): RedirectResponse
    {
        abort_unless($publicDocument->is_published, 404);

        return redirect()->away(
            $documents->temporaryUrl($publicDocument->file_path, $publicDocument->slug.'.pdf'),
        );
    }

    public function sakip(ESakip $sakip, SecureDocumentService $documents): RedirectResponse
    {
        abort_unless($sakip->is_published, 404);

        return redirect()->away(
            $documents->temporaryUrl($sakip->file_path_s3, "sakip-{$sakip->year}-{$sakip->document_type->value}.pdf"),
        );
    }
}
