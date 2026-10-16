<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MeetingNotification extends Mailable
{
    /** @param 'confirm'|'reminder'|'rescheduled' $kind */
    public function __construct(public Meeting $meeting, public string $kind = 'confirm')
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->kind) {
                'confirm' => 'Scheduled: ',
                'rescheduled' => 'Rescheduled: ',
                default => 'Starting soon: ',
            }.$this->meeting->title,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.meeting');
    }
}
