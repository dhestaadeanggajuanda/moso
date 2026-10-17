<?php

use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PublicDocumentController;
use App\Http\Controllers\Admin\SakipController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Middleware\EnsureTwoFactorForPrivilegedRoles;
use Illuminate\Support\Facades\Route;

/*
| Panel admin. Otorisasi per aksi ada di Policy (Gate::authorize / FormRequest),
| bukan di route, supaya setiap aksi tetap terlindungi meski route berubah.
*/
Route::middleware(['auth', 'verified', EnsureTwoFactorForPrivilegedRoles::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('articles', ArticleController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('pages', PageController::class)->except('show');
        Route::resource('agendas', AgendaController::class)->except('show');

        // Modul kepatuhan: PPID, Standar Pelayanan, E-SAKIP, dan Menu Builder.
        Route::resource('ppid', PublicDocumentController::class)
            ->parameters(['ppid' => 'publicDocument'])
            ->except('show');
        Route::get('ppid/{publicDocument}/file', [PublicDocumentController::class, 'file'])->name('ppid.file');
        Route::resource('services', ServiceController::class)->except('show');
        Route::resource('sakip', SakipController::class)->except('show');
        Route::get('sakip/{sakip}/file', [SakipController::class, 'file'])->name('sakip.file');
        Route::resource('menus', MenuController::class)->except('show');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
