<?php

namespace App\Livewire\Admin\Meeting;

use App\Models\Meeting;
use App\Models\ZoomAccount;
use Carbon\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('Day schedule')]
class SchedulePage extends Component
{
    /** Y-m-d */
    public string $date = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function render()
    {
        try {
            $day = Carbon::parse($this->date)->startOfDay();
        } catch (Throwable) {
            $day = now()->startOfDay();
        }

        $fromHour = (int) config('portal.day_start_hour');
        $toHour = (int) config('portal.day_end_hour');
        $open = $day->copy()->setTime($fromHour, 0);
        $close = $day->copy()->setTime($toHour, 0);
        $span = max(1, $open->diffInSeconds($close));

        $accounts = ZoomAccount::orderBy('priority')->orderBy('created_at')->get();

        $meetings = Meeting::scheduled()
            ->where('end_time', '>', $open)->where('start_time', '<', $close)
            ->get()->groupBy('zoom_account_id');

        $rows = $accounts->map(fn (ZoomAccount $a) => [
            'label' => $a->label.($a->is_active ? '' : ' (off)'),
            'email' => $a->host_email,
            'bars' => $meetings->get($a->id, collect())->map(function (Meeting $m) use ($open, $close, $span) {
                $s = $m->start_time->max($open);
                $e = $m->end_time->min($close);

                return [
                    'title' => $m->title,
                    'tip' => $m->title.' ('.$m->start_time->format('g:i A').'–'.$m->end_time->format('g:i A').')',
                    'left' => round($open->diffInSeconds($s) / $span * 100, 3),
                    'width' => round($s->diffInSeconds($e) / $span * 100, 3),
                ];
            })->values(),
        ]);

        return view('livewire.admin.schedule-page', [
            'rows' => $rows,
            'hours' => range($fromHour, $toHour),
            'cols' => $toHour - $fromHour,
        ]);
    }
}
