@php
    // Halaman situs publik memakai CSS tema; panel admin memakai app.css.
    $isSite = str_starts_with($page['component'], 'site/');
    $siteTheme = in_array($theme ?? null, config('themes.enabled'), true) ? $theme : config('themes.default');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ! $isSite && ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @unless ($isSite)
            {{-- Inline script to detect system dark mode preference and apply it immediately --}}
            <script nonce="{{ Vite::cspNonce() }}">
                (function() {
                    const appearance = '{{ $appearance ?? "system" }}';

                    if (appearance === 'system') {
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                        if (prefersDark) {
                            document.documentElement.classList.add('dark');
                        }
                    }
                })();
            </script>

            {{-- Inline style to set the HTML background color based on our theme in app.css --}}
            <style>
                html {
                    background-color: oklch(1 0 0);
                }

                html.dark {
                    background-color: oklch(0.145 0 0);
                }
            </style>
        @endunless

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @if (! $isSite)
            @fonts('instrument-sans')
        @elseif (config("themes.fonts.{$siteTheme}"))
            @fonts(config("themes.fonts.{$siteTheme}"))
        @endif

        @vite([
            $isSite ? "resources/js/Themes/{$siteTheme}/theme.css" : 'resources/css/app.css',
            'resources/js/app.ts',
            "resources/js/pages/{$page['component']}.vue",
        ])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body @class(['font-sans antialiased' => ! $isSite])>
        <x-inertia::app />
    </body>
</html>
