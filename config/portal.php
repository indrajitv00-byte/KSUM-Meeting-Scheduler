<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meeting rules
    |--------------------------------------------------------------------------
    | All times are handled in the application timezone (APP_TIMEZONE,
    | default Asia/Kolkata), so "8:00-20:00" below means 8am-8pm IST.
    */

    // Gap kept free on a Zoom account after each meeting.
    'buffer_minutes' => 10,

    'min_duration' => 15,
    'max_duration' => 360,

    // Durations offered in the booking form (minutes => label).
    'durations' => [
        60 => '1 hour',
        120 => '2 hours',
        180 => '3 hours',
        240 => '4 hours',
        300 => '5 hours',
        360 => '6 hours',
    ],

    // Working day used for "free slot" suggestions and the usage/timeline views.
    'day_start_hour' => 6,
    'day_end_hour' => 23,

    // Email a reminder this many minutes before the meeting starts.
    'reminder_minutes' => 30,

    /*
    |--------------------------------------------------------------------------
    | Sign-in
    |--------------------------------------------------------------------------
    | Comma separated list of email domains allowed to create an account the
    | first time they sign in with Google / Startup Mission ID.
    | Leave empty to allow any domain. Example: PORTAL_ALLOWED_DOMAINS=startupmission.in
    */
    'allowed_domains' => array_values(array_filter(array_map(
        fn ($d) => strtolower(trim($d)),
        explode(',', (string) env('PORTAL_ALLOWED_DOMAINS', ''))
    ))),

    // Role given to people who sign in for the first time.
    'default_role' => 'user',
    /*
    | Recordings: a nightly job (php artisan recordings:archive) copies finished
    | meetings' recordings from Zoom to Google Drive, then removes them from Zoom.
    */
    'recording_sync_time' => env('RECORDING_SYNC_TIME', '00:00'),   // 24h clock, app timezone
    'recording_delete_action' => env('RECORDING_DELETE_ACTION', 'trash'), // trash = recoverable for 30 days, delete = permanent
    'recording_lookback_days' => 7,    // keep retrying meetings that ended up to this many days ago
    'recording_wait_minutes' => 30,    // give Zoom time to process after the meeting ends
    'recording_retention_months' => (int) env('RECORDING_RETENTION_MONTHS', 12), // Drive copies older than this are removed monthly; 0 = keep forever
    'recording_alert_after_days' => 2, // email admins when a recording is still not in Drive this many days after the meeting
    'alert_emails' => env('ADMIN_ALERT_EMAILS'), // comma separated; empty = every admin
    'recording_skip_types' => ['TIMELINE'], // Zoom index files nobody needs; every other file is archived

];
