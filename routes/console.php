<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Peringatan budget harian. Di hPanel, buat cron job: php /home/USER/path/artisan schedule:run (tiap menit),
// atau langsung: php /home/USER/path/artisan budget:peringatan (sekali sehari).
\Illuminate\Support\Facades\Schedule::command('budget:peringatan')->dailyAt('07:00');
