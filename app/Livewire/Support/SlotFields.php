<?php

namespace App\Livewire\Support;

use App\Models\Meeting;
use App\Services\MeetingService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Throwable;

/**
 * The "when" part of the booking and reschedule forms: date, start time and duration.
 * Start times that no Zoom host can take (for the chosen length) are shown as "booked"
 * and cannot be picked, so people see what is free before they choose.
 */
class SlotFields
{
    /** @var array<string,array<string,bool>> */
    private static array $cache = [];

    /** @return array<int,\Filament\Schemas\Components\Component> */
    public static function schema(?Meeting $reschedule = null): array
    {
        $ignoreId = $reschedule?->id; // a meeting being moved must not block its own new slot

        // after the date or length changes, drop a time that is no longer on offer
        $reconcile = function (Get $get, Set $set) use ($ignoreId) {
            $time = $get('time');
            if ($time && ! (self::slots($get('date'), $get('duration_min'), $ignoreId)[$time]['free'] ?? false)) {
                $set('time', null);
            }
        };

        return [
            Grid::make(['default' => 1, 'sm' => 2])->schema([
                DatePicker::make('date')
                    ->label('Date')
                    ->native(false)
                    ->displayFormat('D, j M Y')
                    ->closeOnDateSelection()
                    ->minDate(fn () => now()->startOfDay())
                    ->required()
                    ->live()
                    ->afterStateUpdated($reconcile),
                Select::make('time')
                    ->label('Start time')
                    ->options(fn (Get $get) => collect(self::slots($get('date'), $get('duration_min'), $ignoreId))
                        ->map(fn (array $s) => $s['label'].($s['free'] ? '' : ' · booked'))
                        ->all())
                    ->disableOptionWhen(fn (string $value, Get $get) => ! (self::slots($get('date'), $get('duration_min'), $ignoreId)[$value]['free'] ?? true))
                    ->native(false)->searchable()
                    ->placeholder('Pick a time')
                    ->required()
                    ->live(),
            ]),
            Select::make('duration_min')
                ->label('How long?')
                ->options(config('portal.durations'))
                ->native(false)->selectablePlaceholder(false)
                ->required()
                ->live()
                ->afterStateUpdated($reconcile)
                ->helperText(fn (Get $get) => self::endsAtHint($get('date'), $get('time'), (int) $get('duration_min'))),
        ];
    }

    /**
     * Start times still ahead on a day, with whether a Zoom host is free for each.
     *
     * @return array<string,array{label:string,free:bool}> ['17:00' => ['label' => '5:00 PM', 'free' => true]]
     */
    private static function slots(?string $date, mixed $duration, ?int $ignoreId): array
    {
        try {
            $day = Carbon::parse($date ?: now())->startOfDay();
        } catch (Throwable) {
            return [];
        }

        $minutes = (int) $duration > 0 ? (int) $duration : 60;
        $key = $day->toDateString().'|'.$minutes.'|'.$ignoreId;

        $free = self::$cache[$key] ??= app(MeetingService::class)->slotAvailability($day, $minutes, $ignoreId);

        $out = [];
        foreach ($free as $hm => $isFree) {
            $t = Carbon::parse($day->toDateString().' '.$hm);
            if ($t->isFuture()) {
                $out[$hm] = ['label' => $t->format('g:i A'), 'free' => $isFree];
            }
        }

        return $out;
    }

    public static function endsAtHint(?string $date, ?string $time, int $minutes): ?string
    {
        if (! $date || ! $time || $minutes < 1) {
            return null;
        }

        try {
            $start = Carbon::parse($date.' '.$time);
        } catch (Throwable) {
            return null;
        }

        $end = $start->copy()->addMinutes($minutes);

        return 'Ends at '.$end->format('g:i A').($end->isSameDay($start) ? '' : ' the next day').'.';
    }
}