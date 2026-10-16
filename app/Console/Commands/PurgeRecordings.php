<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Services\Google\GoogleDrive;
use App\Services\RecordingAlerts;
use Illuminate\Console\Command;
use Throwable;

class PurgeRecordings extends Command
{
    protected $signature = 'recordings:purge
        {--months= : Remove recordings older than this many months (default RECORDING_RETENTION_MONTHS)}
        {--dry-run : Only list what would be removed}';

    protected $description = 'Remove recordings from Google Drive once they are older than the retention period (run monthly by the scheduler)';

    public function handle(GoogleDrive $drive, RecordingAlerts $alerts): int
    {
        $months = (int) ($this->option('months') ?? config('portal.recording_retention_months'));
        if ($months < 1) {
            $this->info('Retention is off (RECORDING_RETENTION_MONTHS=0): recordings are kept forever.');

            return self::SUCCESS;
        }

        $cutoff = now()->subMonths($months);
        $dry = (bool) $this->option('dry-run');

        $meetings = Meeting::where('end_time', '<', $cutoff)->whereHas('recordings')->with('recordings')->orderBy('end_time')->get();
        $this->info("Recordings older than {$months} month(s) (before {$cutoff->format('Y-m-d')}): {$meetings->count()} meeting(s)");

        $files = $failed = 0;
        $errors = [];

        foreach ($meetings as $meeting) {
            foreach ($meeting->recordings as $rec) {
                $this->line("#{$meeting->id} {$rec->name}");
                if ($dry) {
                    $files++;

                    continue;
                }

                try {
                    $drive->trash($rec->drive_file_id); // Drive keeps it in its trash for 30 days
                    $rec->delete();
                    $files++;
                } catch (Throwable $e) {
                    $failed++;
                    report($e);
                    $errors[] = $e->getMessage();
                    $this->error('  failed: '.$e->getMessage());
                }
            }

            if (! $dry && $meeting->recordings()->count() === 0) {
                $meeting->update(['recordings_status' => Meeting::REC_PURGED]);
            }
        }

        $this->info(($dry ? 'Would remove' : 'Removed')." {$files} file(s), failed: {$failed}");

        if ($failed > 0) {
            $alerts->send([], collect(), 'The monthly clean-up of old recordings had '.$failed.' failure(s). First error: '.$errors[0]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
