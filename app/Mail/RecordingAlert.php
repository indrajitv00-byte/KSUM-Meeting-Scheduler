<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class RecordingAlert extends Mailable
{
    /**
     * @param  array<int,array{meeting:Meeting,error:string}>  $failures
     * @param  Collection<int,Meeting>  $stale
     */
    public function __construct(public array $failures, public Collection $stale, public ?string $general = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Meeting recordings need attention');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.recording-alert');
    }
}
