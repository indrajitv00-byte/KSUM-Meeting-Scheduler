<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Services\RecordingArchiver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** "Retry now" on the Recordings page: copies one meeting's recordings in the background. */
class ArchiveMeetingRecordings implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 0; // big files take a while

    public function __construct(public int $meetingId)
    {
    }

    public function handle(RecordingArchiver $archiver): void
    {
        $meeting = Meeting::find($this->meetingId);
        if (! $meeting || $meeting->recordings_synced_at) {
            return;
        }

        try {
            $archiver->archive($meeting);
        } catch (Throwable $e) {
            report($e); // the meeting is already marked "failed" with the reason, shown on the Recordings page
        }
    }
}
