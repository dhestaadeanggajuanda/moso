<?php

use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\ArticleAiDraftController;
use App\Http\Controllers\Admin\ArticleCommentController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\ArticleWorkflowController;
use App\Http\Controllers\Admin\AssistantController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PublicDocumentController;
use App\Http\Controllers\Admin\SakipController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteLogoController;
use App\Http\Controllers\Admin\UserController;
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
        // Asisten AI (moso-agentic). Didaftarkan sebelum resource agar tidak tertangkap articles/{article}.
        // Prefix throttle terpisah: tanpa prefix, semua throttle:x,y berbagi satu hitungan per pengguna,
        // dan polling status (tiap 2 detik) akan menghabiskan jatah permintaan draf.
        Route::post('articles/ai-drafts', [ArticleAiDraftController::class, 'store'])
            ->middleware('throttle:10,1,ai-drafts')
            ->name('articles.ai-drafts.store');
        Route::get('articles/ai-drafts/{aiDraft}', [ArticleAiDraftController::class, 'show'])
            ->whereUuid('aiDraft')
            ->middleware('throttle:60,1,ai-draft-status')
            ->name('articles.ai-drafts.show');

        Route::resource('articles', ArticleController::class);
        // Alur persetujuan (Fase 5): status hanya berubah lewat endpoint ini.
        Route::post('articles/{article}/transitions', [ArticleWorkflowController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('articles.transitions.store');
        Route::post('articles/{article}/comments', [ArticleCommentController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('articles.comments.store');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('pages', PageController::class)->except('show');
        Route::get('pages/{page}/builder', [PageBuilderController::class, 'edit'])->name('pages.builder');
        Route::put('pages/{page}/blocks', [PageBuilderController::class, 'update'])->name('pages.blocks.update');
        Route::post('pages/{page}/builder/images', [PageBuilderController::class, 'uploadImage'])
            ->middleware('throttle:20,1')
            ->name('pages.builder.images');
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

        // Manajemen akun pegawai (Super Admin). Akun dinonaktifkan, tidak dihapus.
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::delete('users/{user}/sso-identities/{ssoIdentity}', [UserController::class, 'unlinkSso'])
            ->scopeBindings()
            ->name('users.sso-identities.destroy');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/logos/{logo}', [SiteLogoController::class, 'update'])
            ->middleware('throttle:20,1')
            ->name('settings.logos.update');
        Route::delete('settings/logos/{logo}', [SiteLogoController::class, 'destroy'])->name('settings.logos.destroy');

        // Asisten AI: profil dinas dan gaya tulisan disimpan di moso-agentic (Super Admin).
        Route::get('assistant', [AssistantController::class, 'index'])->name('assistant.index');
        Route::middleware('throttle:20,1,assistant')->group(function () {
            Route::put('assistant/profile', [AssistantController::class, 'updateProfile'])->name('assistant.profile.update');
            Route::put('assistant/style-notes', [AssistantController::class, 'updateStyleNotes'])->name('assistant.style-notes.update');
            Route::delete('assistant/style-notes', [AssistantController::class, 'resetStyleNotes'])->name('assistant.style-notes.destroy');
        });
    });
