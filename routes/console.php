<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Emails "starting soon" reminders. Needs the Laravel scheduler running (see README).
Schedule::command('meetings:send-reminders')->everyFiveMinutes()->withoutOverlapping();

// Nightly: copy finished meetings' recordings from Zoom to Google Drive, then remove them from Zoom.
// Time is RECORDING_SYNC_TIME in .env (default 00:00, app timezone).
Schedule::command('recordings:archive')
    ->dailyAt(config('portal.recording_sync_time'))
    ->withoutOverlapping(720)
    ->runInBackground();

// Monthly clean-up: removes Drive copies older than RECORDING_RETENTION_MONTHS (0 = keep forever).
Schedule::command('recordings:purge')->monthlyOn(1, '02:00')->withoutOverlapping();

// Runs queued jobs such as "Retry now" on the Recordings page; no always-on worker needed.
Schedule::command('queue:work --stop-when-empty --tries=1 --timeout=0')
    ->everyMinute()
    ->withoutOverlapping(720)
    ->runInBackground();
