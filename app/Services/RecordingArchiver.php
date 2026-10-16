<?php

namespace App\Services;

use App\Exceptions\MeetingException;
use App\Models\Meeting;
use App\Models\MeetingRecording;
use App\Services\Google\GoogleDrive;
use App\Services\Zoom\ZoomClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * Copies a finished meeting's recordings from Zoom to Google Drive and only then
 * removes them from Zoom. Nothing is removed from Zoom unless every file is safely in Drive.
 */
class RecordingArchiver
{
    public const NONE = 'none';             // Zoom has no recording for this meeting (yet)
    public const PROCESSING = 'processing'; // Zoom is still processing; try again next run
    public const DONE = 'done';             // in Drive and gone from Zoom

    public function __construct(private ZoomClient $zoom, private GoogleDrive $drive)
    {
    }

    /**
     * Archives one meeting and records the outcome on it (status, error, time checked),
     * so the admin page and the alert emails can show where each meeting stands.
     * Two runs for the same meeting (nightly job + "Retry now") never overlap.
     *
     * @param  callable(string):void|null  $log
     * @return array{state:string, files:int}
     */
    public function archive(Meeting $meeting, ?callable $log = null): array
    {
        $lock = Cache::lock('recordings:archive:'.$meeting->id, 6 * 3600);
        if (! $lock->get()) {
            return ['state' => self::PROCESSING, 'files' => 0]; // already being copied
        }

        try {
            $result = $this->run($meeting, $log);

            $meeting->forceFill([
                'recordings_status' => match ($result['state']) {
                    self::DONE => Meeting::REC_ARCHIVED,
                    self::NONE => Meeting::REC_NONE,
                    default => Meeting::REC_PROCESSING,
                },
                'recordings_error' => null,
                'recordings_checked_at' => now(),
            ])->save();

            return $result;
        } catch (Throwable $e) {
            $meeting->forceFill([
                'recordings_status' => Meeting::REC_FAILED,
                'recordings_error' => Str::limit($e->getMessage(), 1000),
                'recordings_checked_at' => now(),
            ])->save();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    /** @return array{state:string, files:int} */
    private function run(Meeting $meeting, ?callable $log): array
    {
        $log ??= fn (string $m) => null;
        $zoomId = (string) $meeting->zoom_meeting_id;
        $copied = 0;
        $removed = [];

        // A meeting that was restarted has one recording set per run; Zoom lists the latest first,
        // so repeat until nothing is left.
        for ($pass = 1; $pass <= 5; $pass++) {
            $rec = $this->zoom->recordings($zoomId, 21600);
            $files = collect($rec['recording_files'] ?? []);

            if ($files->isEmpty()) {
                return $pass === 1
                    ? ['state' => self::NONE, 'files' => 0]
                    : $this->finish($meeting, $copied);
            }

            if ($files->contains(fn ($f) => ($f['status'] ?? '') !== 'completed')) {
                return ['state' => self::PROCESSING, 'files' => $copied];
            }

            // Zoom still lists what we asked it to remove: do not loop or re-delete forever
            if ($files->pluck('id')->diff($removed)->isEmpty()) {
                throw new MeetingException('Zoom still lists the recordings after they were removed. Will retry next run.');
            }

            $skip = config('portal.recording_skip_types');
            foreach ($files as $f) {
                if (in_array($f['file_type'] ?? '', $skip, true) || empty($f['download_url'])) {
                    continue;
                }
                $copied += $this->copyFile($meeting, $f, $rec['download_access_token'] ?? null, $log) ? 1 : 0;
            }

            // every file above is in Drive (copyFile throws otherwise), so Zoom's copy can go
            $this->zoom->deleteRecordings($zoomId, config('portal.recording_delete_action'));
            $removed = array_merge($removed, $files->pluck('id')->all());
            $log("  removed from Zoom ({$files->count()} file(s))");
        }

        return $this->finish($meeting, $copied);
    }

    private function finish(Meeting $meeting, int $copied): array
    {
        $meeting->forceFill(['recordings_synced_at' => now()])->save();

        return ['state' => self::DONE, 'files' => $copied];
    }

    /** Downloads one file from Zoom, uploads it to Drive and records it. False if it was already archived. */
    private function copyFile(Meeting $meeting, array $f, ?string $downloadToken, callable $log): bool
    {
        $zoomFileId = (string) $f['id'];

        if (MeetingRecording::where('zoom_file_id', $zoomFileId)->exists()) {
            return false; // uploaded by an earlier run that stopped before removing it from Zoom
        }

        $ext = strtolower($f['file_extension'] ?? $f['file_type'] ?? 'bin');
        $name = $this->fileName($meeting, $f, $ext);

        $dir = storage_path('app/recordings-tmp');
        File::ensureDirectoryExists($dir);
        $tmp = $dir.'/'.preg_replace('/[^A-Za-z0-9_-]/', '', $zoomFileId).'.part';

        try {
            $log("  downloading {$name}");
            $this->zoom->downloadTo($f['download_url'], $tmp, $downloadToken);

            $size = (int) filesize($tmp);
            $expected = (int) ($f['file_size'] ?? 0);
            if ($expected > 0 && $size < $expected) {
                throw new MeetingException("Download of {$name} was cut short ({$size} of {$expected} bytes).");
            }
            if ($size === 0) {
                $log('  skipped an empty file');

                return false;
            }

            $log('  uploading to Google Drive');
            $up = $this->drive->upload($tmp, $name, $this->mime($ext));
        } finally {
            if (file_exists($tmp)) {
                @unlink($tmp);
            }
        }

        MeetingRecording::create([
            'meeting_id' => $meeting->id,
            'zoom_file_id' => $zoomFileId,
            'recording_type' => $f['recording_type'] ?? null,
            'file_type' => $f['file_type'] ?? strtoupper($ext),
            'file_size' => $up['size'],
            'recording_start' => isset($f['recording_start']) ? \Carbon\Carbon::parse($f['recording_start']) : null,
            'name' => $name,
            'drive_file_id' => $up['id'],
        ]);

        return true;
    }

    /** "2026-10-12 1530 - Review meeting - Video - shared screen.mp4" */
    private function fileName(Meeting $meeting, array $f, string $ext): string
    {
        $label = MeetingService::RECORDING_LABELS[$f['recording_type'] ?? ''] ?? ($f['recording_type'] ?? $f['file_type'] ?? 'Recording');

        $base = $meeting->start_time->format('Y-m-d Hi').' - '.$meeting->title.' - '.$label;
        $base = preg_replace('/[\\\\\/:*?"<>|\x00-\x1f]+/u', ' ', $base);
        $base = trim(preg_replace('/\s+/', ' ', $base));

        return Str::limit($base, 150, '').'.'.$ext;
    }

    private function mime(string $ext): string
    {
        return match ($ext) {
            'mp4' => 'video/mp4',
            'm4a' => 'audio/mp4',
            'txt' => 'text/plain',
            'vtt' => 'text/vtt',
            'csv' => 'text/csv',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }
}
