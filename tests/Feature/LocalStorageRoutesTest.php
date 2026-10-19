<?php

use Illuminate\Support\Facades\Storage;

it('does not serve or accept files on the local disk over http', function () {
    Storage::disk('local')->put('rahasia.txt', 'isi privat');

    $this->get('/storage/rahasia.txt')->assertNotFound();
    $this->put('/storage/unggahan.txt', ['isi' => 'x'])->assertNotFound();

    Storage::disk('local')->delete('rahasia.txt');
});
