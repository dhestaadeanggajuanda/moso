<?php

use App\Services\Storage\SecureUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('s3');
});

it('stores a re-encoded webp under a uuid name', function () {
    $path = (new SecureUploadService)->storeImage(UploadedFile::fake()->image('KTP warga.png', 640, 480), 'agendas');

    expect($path)->toMatch('#^agendas/\d{4}/\d{2}/[0-9a-f-]{36}\.webp$#');
    expect(getimagesizefromstring(Storage::disk('s3')->get($path))['mime'])->toBe('image/webp');
});

it('rejects a file that is not a decodable image', function () {
    $fake = UploadedFile::fake()->createWithContent('foto.jpg', '<?php echo "polyglot"; ?>');

    (new SecureUploadService)->storeImage($fake, 'articles');
})->throws(ValidationException::class);

it('refuses directories that could escape the upload root', function () {
    (new SecureUploadService)->storeImage(UploadedFile::fake()->image('a.jpg'), '../config');
})->throws(InvalidArgumentException::class);
