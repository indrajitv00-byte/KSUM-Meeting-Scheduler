<div>
    <p class="muted" style="margin-bottom:.75rem">{{ $meeting->title }}</p>

    @if ($error)
        <p style="color:var(--bad);padding:1rem 0">{{ $error }}</p>
    @elseif (empty($files))
        <p class="muted" style="padding:1rem 0">{{ $message ?: 'No recording is available.' }}</p>
    @else
        @foreach ($files as $f)
            <div class="rec-row">
                <div>
                    <b>{{ $f['label'] }}</b><br>
                    <span class="muted">{{ $f['type'] }} · {{ \Illuminate\Support\Number::fileSize($f['size'], 1) }}</span>
                </div>
                <a class="btn btn-sm btn-primary" href="{{ $f['url'] }}" @if (empty($archived)) target="_blank" rel="noopener noreferrer" @endif download>Download</a>
            </div>
        @endforeach
        @if (! empty($archived))
            <p class="muted" style="margin-top:.8rem">Recordings are stored in the organisation's Google Drive and download through this portal.</p>
        @else
            <p class="muted" style="margin-top:.8rem">Download links expire after about an hour. Reopen this window for a fresh link. Recordings are moved to Google Drive and removed from Zoom overnight.</p>
        @endif
    @endif
</div>
