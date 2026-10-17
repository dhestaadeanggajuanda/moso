<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jaga cache berita Media Center tetap segar agar pengunjung tidak menunggu API luar.
Schedule::command('mediacenter:warm')->everyTenMinutes()->withoutOverlapping();
