<?php

use App\Services\Content\HtmlSanitizer;

beforeEach(function () {
    config(['filesystems.disks.s3.url' => 'https://media.example.go.id/moso-public']);
});

it('removes dangerous markup', function (string $input, string $forbidden) {
    $output = (new HtmlSanitizer)->sanitize($input);

    expect($output)->not->toContain($forbidden);
})->with([
    'script tag' => ['<p>a</p><script>alert(1)</script>', '<script'],
    'event handler' => ['<img src="https://media.example.go.id/a.webp" onerror="alert(1)">', 'onerror'],
    'javascript url' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
    'inline style' => ['<p style="position:fixed">x</p>', 'style='],
    'iframe' => ['<iframe src="https://evil.example"></iframe>', '<iframe'],
    'foreign image host' => ['<img src="https://evil.example/track.png">', 'evil.example'],
]);

it('keeps safe formatting and images from the public bucket', function () {
    $output = (new HtmlSanitizer)->sanitize(
        '<h2>Judul</h2><p><strong>Tebal</strong> <a href="https://singkawangkota.go.id">tautan</a></p>'
        .'<img src="https://media.example.go.id/moso-public/a.webp" alt="Foto">',
    );

    expect($output)
        ->toContain('<h2>Judul</h2>')
        ->toContain('<strong>Tebal</strong>')
        ->toContain('href="https://singkawangkota.go.id"')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('src="https://media.example.go.id/moso-public/a.webp"');
});
