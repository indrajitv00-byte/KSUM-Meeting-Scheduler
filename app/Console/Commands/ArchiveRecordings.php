<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Services\Google\GoogleDrive;
use App\Services\RecordingAlerts;
use App\Services\RecordingArchiver;
use App\Services\Zoom\ZoomClient;
use Illuminate\Console\Command;
use Throwable;

class ArchiveRecordings extends Command
{
    protected $signature = 'recordings:archive
        {--meeting= : Only this meeting (its id in the portal database)}
        {--days= : How many days back to look (default from config/portal.php)}';

    protected $description = 'Copy finished meetings\' Zoom recordings to Google Drive, then remove them from Zoom (run nightly by the scheduler)';

    public function handle(RecordingArchiver $archiver, RecordingAlerts $alerts, ZoomClient $zoom, GoogleDrive $drive): int
    {
        $days = (int) ($this->option('days') ?: config('portal.recording_lookback_days'));

        $query = Meeting::scheduled()
            ->whereNotNull('zoom_meeting_id')
            ->whereNull('recordings_synced_at')
            ->where('end_time', '<=', now()->subMinutes((int) config('portal.recording_wait_minutes')))
            ->where('end_time', '>=', now()->subDays($days))
            ->orderBy('end_time');

        if ($id = $this->option('meeting')) {
            $query->whereKey($id);
        }

        $meetings = $query->get();
        $this->info("Meetings to check: {$meetings->count()}");

        // Sign in to both services first: an expired Google token would otherwise fail every meeting the same way.
        if ($meetings->isNotEmpty()) {
            try {
                $zoom->token(fresh: true);
                $drive->token(fresh: true);
            } catch (Throwable $e) {
                report($e);
                $this->error('Could not sign in: '.$e->getMessage());
                $alerts->send([], $alerts->stale(), $e->getMessage());

                return self::FAILURE;
            }
        }

        $done = $waiting = 0;
        $failures = [];

        foreach ($meetings as $meeting) {
            $this->line("#{$meeting->id} {$meeting->title} ({$meeting->start_time->format('Y-m-d H:i')})");

            try {
                $r = $archiver->archive($meeting, fn (string $m) => $this->line($m));
            } catch (Throwable $e) {
                report($e);
                $failures[] = ['meeting' => $meeting, 'error' => $e->getMessage()];
                $this->error('  failed: '.$e->getMessage());

                continue;
            }

            match ($r['state']) {
                RecordingArchiver::DONE => $done++,
                default => $waiting++,
            };
            $this->line('  '.match ($r['state']) {
                RecordingArchiver::DONE => "done, {$r['files']} file(s) copied",
                RecordingArchiver::PROCESSING => 'Zoom is still processing; will try again next run',
                default => 'no recording on Zoom (yet)',
            });
        }

        // tell admins about this run's failures and about recordings that have been stuck for days
        $alerts->send($failures, $alerts->stale());

        $failed = count($failures);
        $this->info("Archived: {$done}, waiting: {$waiting}, failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
