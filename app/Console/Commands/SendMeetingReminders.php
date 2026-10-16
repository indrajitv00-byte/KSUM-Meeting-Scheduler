<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Services\MeetingService;
use Illuminate\Console\Command;

class SendMeetingReminders extends Command
{
    protected $signature = 'meetings:send-reminders';

    protected $description = 'Email a reminder for meetings that start soon (run every 5 minutes by the scheduler)';

    public function handle(MeetingService $service): int
    {
        $until = now()->addMinutes((int) config('portal.reminder_minutes'));

        $meetings = Meeting::scheduled()
            ->with('user')
            ->where('reminder_sent', false)
            ->where('start_time', '>', now())
            ->where('start_time', '<=', $until)
            ->get();

        foreach ($meetings as $meeting) {
            $service->mail($meeting->user?->email, $meeting, 'reminder');
            $meeting->update(['reminder_sent' => true]);
        }

        $this->info("Reminders sent: {$meetings->count()}");

        return self::SUCCESS;
    }
}
