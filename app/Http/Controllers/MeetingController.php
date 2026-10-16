<?php

namespace App\Http\Controllers;

use App\Exceptions\MeetingException;
use App\Models\Meeting;
use App\Services\MeetingService;
use Filament\Notifications\Notification;
use Throwable;

class MeetingController extends Controller
{
    /**
     * Opens the Zoom host link. A fresh link is fetched on every click because Zoom's expires.
     * Used as a normal link (opens in a new tab), so popup blockers never get in the way.
     */
    public function start(Meeting $meeting, MeetingService $service)
    {
        try {
            return redirect()->away($service->startUrl($meeting, auth()->user()));
        } catch (MeetingException $e) {
            $message = $e->getMessage();
        } catch (Throwable $e) {
            report($e);
            $message = 'Could not open the meeting. Please try again.';
        }

        Notification::make()->danger()->title($message)->send();

        return redirect()->route('meetings');
    }
}
