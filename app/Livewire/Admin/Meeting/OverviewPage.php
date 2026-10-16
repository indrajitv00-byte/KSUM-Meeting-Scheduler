<?php

namespace App\Livewire\Admin\Meeting;

use App\Models\Meeting;
use App\Models\ZoomAccount;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Admin overview')]
class OverviewPage extends Component
{
    public function render()
    {
        $dayStart = now()->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        $accounts = ZoomAccount::orderBy('priority')->orderBy('created_at')->get();
        $today = Meeting::scheduled()->where('start_time', '>=', $dayStart)->where('start_time', '<', $dayEnd)->get();

        $workMinutes = max(1, (config('portal.day_end_hour') - config('portal.day_start_hour')) * 60);

        $usage = $accounts->map(function (ZoomAccount $a) use ($today, $workMinutes) {
            $mins = $today->where('zoom_account_id', $a->id)
                ->sum(fn (Meeting $m) => abs($m->start_time->diffInMinutes($m->end_time)));

            return ['label' => $a->label, 'pct' => (int) min(100, round($mins / $workMinutes * 100))];
        });

        $stats = [
            [$accounts->where('is_active', true)->count().' / '.$accounts->count(), 'Active Zoom accounts'],
            [$today->count(), 'Meetings today'],
            [Meeting::live()->count(), 'Upcoming meetings'],
            [Meeting::where('status', Meeting::FAILED)->where('created_at', '>=', now()->subDays(7))->count(), 'Requests with no free slot (7 days)'],
        ];

        return view('livewire.admin.overview-page', [
            'stats' => $stats,
            'usage' => $usage,
            'hours' => config('portal.day_start_hour').':00–'.config('portal.day_end_hour').':00',
        ]);
    }
}
