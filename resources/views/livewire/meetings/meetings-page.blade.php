<div>
    <x-hero subtitle="Pick a time and the portal finds a free Zoom account for you.">
        @can('meetings:create')
            <button class="btn btn-light" type="button" wire:click="mountAction('schedule')">+ Schedule a meeting</button>
        @endcan
    </x-hero>

    {{-- Outcome of the last booking request --}}
    @if ($result)
        <section style="margin-bottom:1.5rem">
            @if ($result['type'] === 'ok')
                <div class="result ok">
                    <h2 class="ttl">Meeting scheduled</h2>
                    <p><b>{{ $result['title'] }}</b><br>{{ $result['range'] }}</p>

                    <label class="lbl">Participants link (share this)</label>
                    <div class="copybox">
                        <input readonly value="{{ $result['join_url'] }}" onclick="this.select()">
                        <button class="btn" type="button" x-on:click="ptCopy(@js($result['join_url']), 'Join link copied')">Copy</button>
                    </div>
                    @if ($result['passcode'])
                        <p class="muted">Passcode: <b>{{ $result['passcode'] }}</b></p>
                    @endif

                    <div class="actions">
                        <a class="btn btn-primary" href="{{ $result['start_url'] }}" target="_blank" rel="noopener">Start as host</a>
                        <button class="btn" type="button" x-on:click="ptCopy(@js($result['invite']), 'Invitation copied')">Copy invitation</button>
                    </div>
                    <p class="muted" style="margin-top:.8rem">Keep the host button for yourself. Only use it to start the meeting as the host.</p>
                </div>
            @elseif ($result['type'] === 'unavailable')
                <div class="result warn">
                    <h2 class="ttl">Couldn't schedule that slot</h2>
                    <p>{{ $result['message'] }}</p>
                    @if (count($result['suggestions']))
                        <p class="muted"><b>Free slots nearby</b> (click one to book it):</p>
                        <div class="chips">
                            @foreach ($result['suggestions'] as $s)
                                <button class="chip" type="button" wire:click="pickSlot('{{ $s['iso'] }}')" wire:loading.attr="disabled">{{ $s['label'] }}</button>
                            @endforeach
                        </div>
                    @else
                        <p class="muted">No free slots were found nearby. Try a different day or a shorter duration.</p>
                    @endif
                    <div class="actions">
                        <button class="btn" type="button" wire:click="mountAction('schedule')">Change the request</button>
                    </div>
                </div>
            @else
                <div class="result bad">
                    <h2 class="ttl">Something went wrong</h2>
                    <p>{{ $result['msg'] }}</p>
                    <div class="actions">
                        <button class="btn" type="button" wire:click="mountAction('schedule')">Change the request</button>
                    </div>
                </div>
            @endif
        </section>
    @endif

    {{-- My meetings --}}
    <section class="card">
        <h2 class="ttl">My meetings</h2>

        <div class="tabs">
            @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'other' => 'Cancelled / not scheduled'] as $key => $label)
                <button type="button" class="tab {{ $filter === $key ? 'active' : '' }}" wire:click="$set('filter', '{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>

        @forelse ($meetings as $m)
            <div class="mtg" wire:key="mtg-{{ $m->id }}">
                <div class="mtg-date"><small>{{ $m->start_time->format('M') }}</small>{{ $m->start_time->format('j') }}</div>
                <div class="mtg-main">
                    <b>{{ $m->title }}</b>
                    <x-meeting-badge :meeting="$m" /><br>
                    <span class="muted">{{ $m->start_time->format('g:i A') }} – {{ $m->end_time->format('g:i A') }}{{ $m->department ? ' · '.$m->department : '' }}</span>
                </div>
                <div class="actions" style="margin:0">
                    @if ($m->isLive())
                        <button class="btn btn-sm" type="button" x-on:click="ptCopy(@js($m->join_url), 'Join link copied')">Copy join link</button>
                        <a class="btn btn-sm btn-primary" href="{{ route('meetings.start', $m) }}" target="_blank" rel="noopener">Start</a>
                        @if ($m->start_time->isFuture())
                            <button class="btn btn-sm" type="button" wire:click="mountAction('rescheduleMeeting', { meeting: {{ $m->id }} })">Reschedule</button>
                        @endif
                        <button class="btn btn-sm btn-danger" type="button" wire:click="mountAction('cancelMeeting', { meeting: {{ $m->id }} })">Cancel</button>
                    @elseif ($m->isFinished())
                        <button class="btn btn-sm btn-primary" type="button" wire:click="mountAction('recording', { meeting: {{ $m->id }} })">Download recording</button>
                    @endif
                </div>
            </div>
        @empty
            <p class="muted" style="padding:1rem 0">Nothing here yet.</p>
        @endforelse
    </section>

    <x-filament-actions::modals />
</div>
