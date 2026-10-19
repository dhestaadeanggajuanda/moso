<?php

use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\DocumentDownloadController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\PublicDocumentController;
use App\Http\Controllers\Site\SakipController;
use App\Http\Controllers\Site\ServiceController;
use App\Support\ReservedSlugs;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('berita', [ArticleController::class, 'index'])->name('site.articles.index');
Route::get('berita/{article:slug}', [ArticleController::class, 'show'])->name('site.articles.show');
Route::get('layanan', [ServiceController::class, 'index'])->name('site.services.index');
Route::get('layanan/{service:slug}', [ServiceController::class, 'show'])->name('site.services.show');
Route::get('ppid', [PublicDocumentController::class, 'index'])->name('site.ppid.index');
Route::get('sakip', [SakipController::class, 'index'])->name('site.sakip.index');

// Unduhan dokumen publik: dialihkan ke URL bertanda tangan berumur pendek.
Route::middleware('throttle:downloads')->prefix('unduh')->name('documents.')->group(function () {
    Route::get('ppid/{publicDocument:slug}', [DocumentDownloadController::class, 'ppid'])->name('ppid.download');
    Route::get('sakip/{sakip}', [DocumentDownloadController::class, 'sakip'])->name('sakip.download');
});

// Login SSO OpenID Connect (404 bila SSO_ENABLED=false). Lihat docs/SSO-ROADMAP.md.
Route::middleware(['guest', 'throttle:sso'])->prefix('auth/sso')->name('sso.')->group(function () {
    Route::get('redirect', [SsoController::class, 'redirect'])->name('redirect');
    Route::get('callback', [SsoController::class, 'callback'])->name('callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

// Halaman Page Builder: harus paling akhir. Slug sistem tidak pernah tertangkap di sini.
Route::get('{page:slug}', [PageController::class, 'show'])
    ->where('page', ReservedSlugs::routePattern())
    ->name('site.pages.show');
