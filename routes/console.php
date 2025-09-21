<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// DIME Scraper Scheduling
Schedule::command('dime:schedule --immediate')
    ->hourly()
    ->between('6:00', '23:00') // Run between 6 AM and 11 PM
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/dime-scheduler.log'))
    ->description('Check DIME availability and scrape if online');

// Monitor DIME status every 30 minutes
Schedule::command('dime:check-status')
    ->everyThirtyMinutes()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/dime-status.log'))
    ->description('Monitor DIME website status');
