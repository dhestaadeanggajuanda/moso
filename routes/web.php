<?php

use App\Http\Controllers\Site\DocumentDownloadController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Unduhan dokumen publik: dialihkan ke URL bertanda tangan berumur pendek.
Route::middleware('throttle:downloads')->prefix('unduh')->name('documents.')->group(function () {
    Route::get('ppid/{publicDocument:slug}', [DocumentDownloadController::class, 'ppid'])->name('ppid.download');
    Route::get('sakip/{sakip}', [DocumentDownloadController::class, 'sakip'])->name('sakip.download');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
