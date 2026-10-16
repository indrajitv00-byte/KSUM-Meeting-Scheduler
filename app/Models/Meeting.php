<?php

namespace App\Models;

use App\Traits\HashId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HashId;

    public const SCHEDULED = 'scheduled';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    // where the recordings are in the Zoom -> Google Drive copy (column recordings_status)
    public const REC_PROCESSING = 'processing';
    public const REC_NONE = 'none';
    public const REC_FAILED = 'failed';
    public const REC_QUEUED = 'queued';
    public const REC_ARCHIVED = 'archived';
    public const REC_PURGED = 'purged';

    protected $fillable = [
        'user_id', 'title', 'agenda', 'department', 'participants_est',
        'start_time', 'end_time', 'block_start', 'block_end',
        'status', 'zoom_account_id', 'zoom_meeting_id', 'join_url', 'passcode', 'reminder_sent', 'recordings_synced_at',
        'recordings_status', 'recordings_error', 'recordings_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'block_start' => 'datetime',
            'block_end' => 'datetime',
            'reminder_sent' => 'boolean',
            'recordings_synced_at' => 'datetime',
            'recordings_checked_at' => 'datetime',
            'participants_est' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zoomAccount(): BelongsTo
    {
        return $this->belongsTo(ZoomAccount::class);
    }

    /** Recording files already copied to Google Drive. */
    public function recordings(): HasMany
    {
        return $this->hasMany(MeetingRecording::class);
    }

    /* ---------- scopes ---------- */

    public function scopeScheduled(Builder $q): Builder
    {
        return $q->where('status', self::SCHEDULED);
    }

    /** Scheduled and not finished yet. */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where('status', self::SCHEDULED)->where('end_time', '>', now());
    }

    /** Scheduled and already over. */
    public function scopeFinished(Builder $q): Builder
    {
        return $q->where('status', self::SCHEDULED)->where('end_time', '<=', now());
    }

    /* ---------- state ---------- */

    public function isLive(): bool
    {
        return $this->status === self::SCHEDULED && $this->end_time->isFuture();
    }

    public function isFinished(): bool
    {
        return $this->status === self::SCHEDULED && $this->end_time->isPast();
    }

    public function isManageableBy(User $user): bool
    {
        return $this->user_id === $user->id || $user->can('admin:meetings:update');
    }

    /** e.g. "Mon, 12 Oct 2026, 3:30 PM – 4:30 PM" */
    public function rangeLabel(): string
    {
        return $this->start_time->format('D, j M Y, g:i A').' – '.$this->end_time->format('g:i A');
    }

    /** Text people paste into chats/calendars. */
    public function inviteText(): string
    {
        return "Topic: {$this->title}\nTime: {$this->rangeLabel()}\n\nJoin Zoom Meeting\n{$this->join_url}"
            .($this->passcode ? "\nPasscode: {$this->passcode}" : '');
    }
}
