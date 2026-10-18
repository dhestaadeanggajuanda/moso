import { useHttp } from '@inertiajs/vue3';
import PageBuilderController from '@/actions/App/Http/Controllers/Admin/PageBuilderController';

/**
 * Unggah foto blok Page Builder (banner, slide) lewat SecureUploadService di
 * server. Hasilnya path yang disimpan di props blok saat susunan disimpan.
 */
export function useBlockImageUpload(pageId: () => number) {
    const http = useHttp<{ image: File | null }, { path: string; url: string }>(
        { image: null },
    );

    function upload(file: File, onUploaded: (path: string) => void): void {
        http.image = file;
        http.post(PageBuilderController.uploadImage.url(pageId()), {
            onSuccess: (response) => onUploaded(response.path),
            onFinish: () => http.reset(),
        }).catch(() => {
            // Galat validasi dan jaringan sudah ditampilkan lewat http.errors.
        });
    }

    return { http, upload };
}
