<?php

namespace App\Models;

use App\Traits\HashId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A recording file that was copied from Zoom to Google Drive. */
class MeetingRecording extends Model
{
    use HashId;

    protected $fillable = [
        'meeting_id', 'zoom_file_id', 'recording_type', 'file_type',
        'file_size', 'recording_start', 'name', 'drive_file_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'recording_start' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function mimeType(): string
    {
        return match (strtolower(pathinfo($this->name, PATHINFO_EXTENSION))) {
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
