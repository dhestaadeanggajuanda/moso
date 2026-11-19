<?php

use App\Models\Page;
use App\Services\PageBuilder\BlockRegistry;
use App\Support\Settings;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Akar folder tema. Berbasis lokasi berkas ini (bukan resource_path()) karena dataset Pest
 * dievaluasi sebelum aplikasi Laravel ada.
 */
function themesRoot(): string
{
    return dirname(__DIR__, 3).'/resources/js/Themes';
}

/**
 * Nama folder tema di resources/js/Themes.
 *
 * @return list<string>
 */
function themeFolders(): array
{
    return array_map('basename', glob(themesRoot().'/*', GLOB_ONLYDIR) ?: []);
}

/**
 * Nama komponen (tanpa .vue) pada satu bagian sebuah tema.
 *
 * @return list<string>
 */
function themeComponents(string $theme, string $section): array
{
    $names = array_map(
        fn (string $file): string => basename($file, '.vue'),
        glob(themesRoot()."/{$theme}/{$section}/*.vue") ?: [],
    );
    sort($names);

    return $names;
}

beforeEach(function () {
    $this->withoutVite();
});

it('ships TemaModern and TemaModern2 as separate themes', function () {
    expect(themeFolders())->toContain('TemaModern', 'TemaModern2');
});

it('gives every theme a component for each page builder block', function (string $theme) {
    $missing = array_diff(app(BlockRegistry::class)->types(), themeComponents($theme, 'Components'));

    expect($missing)->toBe([], "{$theme} tidak punya komponen untuk blok: ".implode(', ', $missing));
})->with(fn () => themeFolders());

it('gives every theme the layout and views that the public pages load', function (string $theme) {
    foreach (['Layouts', 'Views'] as $section) {
        $missing = array_diff(themeComponents('TemaModern', $section), themeComponents($theme, $section));

        expect($missing)->toBe([], "{$theme} tidak punya {$section}: ".implode(', ', $missing));
    }
})->with(fn () => themeFolders());

it('gives every theme its own stylesheet and a font alias', function (string $theme) {
    expect(themesRoot()."/{$theme}/theme.css")->toBeFile()
        ->and(config('themes.fonts'))->toHaveKey($theme);
})->with(fn () => themeFolders());

it('does not let one theme import files from another', function (string $theme) {
    $others = array_values(array_diff(themeFolders(), [$theme]));

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(themesRoot()."/{$theme}", FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        foreach ($others as $other) {
            // Nama tema baru memakai awalan nama tema lama (TemaModern2 memuat "TemaModern"), jadi cocokkan path utuh.
            expect(preg_match("#Themes/{$other}/#", (string) file_get_contents($file->getPathname())))->toBe(0, "{$theme}/{$file->getFilename()} mengimpor dari {$other}");
        }
    }
})->with(fn () => themeFolders());

it('uses the site-wide theme setting only when that theme is compiled', function (string $setting, string $expected) {
    config(['themes.enabled' => ['TemaModern', 'TemaModern2'], 'themes.default' => 'TemaModern']);
    app(Settings::class)->put('theme.active', $setting);
    $page = Page::factory()->published()->create(['theme' => null]);

    $this->get(route('site.pages.show', $page))
        ->assertInertia(fn (Assert $inertia) => $inertia->where('theme', $expected));
})->with([
    'second theme chosen' => ['TemaModern2', 'TemaModern2'],
    'first theme chosen' => ['TemaModern', 'TemaModern'],
    'theme that was removed' => ['TemaDihapus', 'TemaModern'],
]);

it('lets a page pick TemaModern2 while the site stays on TemaModern', function () {
    config(['themes.enabled' => ['TemaModern', 'TemaModern2'], 'themes.default' => 'TemaModern']);
    app(Settings::class)->put('theme.active', 'TemaModern');
    $plain = Page::factory()->published()->create(['theme' => null]);
    $picked = Page::factory()->published()->create(['theme' => 'TemaModern2']);

    $this->get(route('site.pages.show', $plain))->assertInertia(fn (Assert $inertia) => $inertia->where('theme', 'TemaModern'));
    $this->get(route('site.pages.show', $picked))->assertInertia(fn (Assert $inertia) => $inertia->where('theme', 'TemaModern2'));
});
