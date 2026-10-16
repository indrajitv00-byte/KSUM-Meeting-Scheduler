<?php

use Illuminate\Support\Facades\Route;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Zoom Server-to-Server OAuth app (one app for the whole organisation).
    // Create it at https://marketplace.zoom.us > Develop > Build App > Server-to-Server OAuth.
    // Scopes needed: meeting:write:meeting:admin, meeting:read:meeting:admin,
    //                user:read:user:admin, cloud_recording:read:list_recording_files:admin,
    //                cloud_recording:delete:meeting_recording:admin (nightly recordings job)
    'zoom' => [
        'account_id' => env('ZOOM_ACCOUNT_ID'),
        'client_id' => env('ZOOM_CLIENT_ID'),
        'client_secret' => env('ZOOM_CLIENT_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost'), '/').'/google/callback'),
    ],

    // Google Drive for the nightly recordings job. Uses a refresh token of the Google account
    // that should own the files (see README / setup notes). Scope: drive.file
    'google_drive' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID', env('GOOGLE_CLIENT_ID')),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET')),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'folder_name' => env('GOOGLE_DRIVE_FOLDER_NAME', 'KSUM Meeting Recordings'),
        // optional: an existing folder. Needs the full "drive" scope instead of "drive.file".
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
    ],

    'uid' => [    
        'client_id' => env('UID_KEY'),  
        'client_secret' => env('UID_SECRET'),  
        'redirect' => env('UID_REDIRECT'),
        'host' => env('UID_SERVER'),
        'userinfo_key' => 'data',
        'user_email' => 'login_email',
        'user_avatar' => 'logo',
        'user_id' => 'unique_id'
    ],

];
