<?php

namespace App\Http\Controllers;

use App\Models\MeetingRecording;
use App\Services\Google\GoogleDrive;
use Throwable;

class RecordingController extends Controller
{
    /**
     * Sends an archived recording from Google Drive to the browser.
     * Files stay private in Drive; the portal decides who may download them.
     */
    public function download(MeetingRecording $recording, GoogleDrive $drive)
    {
        $meeting = $recording->meeting;
        abort_unless($meeting && $meeting->isManageableBy(auth()->user()), 403);

        set_time_limit(0);

        try {
            $body = $drive->stream($recording->drive_file_id);
        } catch (Throwable $e) {
            report($e);
            abort(502, 'Could not fetch the recording from Google Drive. Please try again.');
        }

        return response()->streamDownload(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(256 * 1024);
                flush();
            }
        }, $recording->name, [
            'Content-Type' => $recording->mimeType(),
            'Content-Length' => (string) $recording->file_size,
        ]);
    }
}
