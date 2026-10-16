<div>
    <x-hero subtitle="See every Zoom account on one timeline." />

    <section class="card">
        <div style="display:flex;gap:1rem;align-items:end;flex-wrap:wrap">
            <div>
                <h2 class="ttl">Day schedule</h2>
                <p class="muted" style="margin:0">All accounts on one timeline ({{ $hours[0] }}:00–{{ end($hours) }}:00).</p>
            </div>
            <span class="spacer"></span>
            <div style="width:190px">
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="date" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div style="margin-top:1rem">
            @if ($rows->isEmpty())
                <p class="muted">No accounts yet.</p>
            @else
                <div class="tl-head">
                    <div></div>
                    <div class="tl-hours">
                        @foreach ($hours as $i => $h)
                            <span style="left:{{ $i / $cols * 100 }}%">{{ $h }}</span>
                        @endforeach
                    </div>
                </div>

                @foreach ($rows as $i => $row)
                    <div class="tl-row" style="--bar: var(--tl-{{ $i % 4 }})" wire:key="tl-{{ $i }}">
                        <div class="tl-label" title="{{ $row['email'] }}">{{ $row['label'] }}</div>
                        <div class="tl-track" style="--cols:{{ $cols }}">
                            @foreach ($row['bars'] as $bar)
                                <div class="tl-bar" title="{{ $bar['tip'] }}" style="left:{{ $bar['left'] }}%;width:{{ $bar['width'] }}%">{{ $bar['title'] }}</div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </section>
</div>
