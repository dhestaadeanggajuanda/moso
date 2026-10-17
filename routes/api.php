<?php

use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\PublicDocumentController;
use App\Http\Controllers\Api\V1\SakipController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

/*
| API publik read-only untuk frontend tema dan integrasi lain.
| Hanya data terbit, tanpa autentikasi, dibatasi rate limit "api",
| dan boleh di-cache publik 5 menit (ETag untuk respons 304).
*/
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['throttle:api', 'cache.headers:public;max_age=300;etag'])
    ->group(function () {
        Route::get('services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');
        Route::get('ppid', [PublicDocumentController::class, 'index'])->name('ppid.index');
        Route::get('sakip', [SakipController::class, 'index'])->name('sakip.index');
        Route::get('menus/{location}', [MenuController::class, 'show'])->name('menus.show');
    });
