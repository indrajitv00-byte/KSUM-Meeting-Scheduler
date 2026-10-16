@props(['meeting'])

@if ($meeting->status === 'scheduled')
    @if ($meeting->isLive())
        <span class="badge b-ok">Scheduled</span>
    @else
        <span class="badge b-grey">Completed</span>
    @endif
@elseif ($meeting->status === 'cancelled')
    <span class="badge b-grey">Cancelled</span>
@else
    <span class="badge b-bad">No free account</span>
@endif
