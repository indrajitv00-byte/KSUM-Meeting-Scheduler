<div style="font-family:Segoe UI,Arial,sans-serif;max-width:560px;color:#13202b">
    <h2 style="margin:0 0 8px">Meeting recordings need attention</h2>

    @if ($general)
        <p style="background:#fdecea;padding:10px 14px;border-radius:6px"><b>Problem:</b> {{ $general }}</p>
    @endif

    @if (count($failures))
        <p><b>Failed in the last run</b></p>
        <ul style="padding-left:18px">
            @foreach ($failures as $f)
                <li style="margin-bottom:8px">
                    <b>{{ $f['meeting']->title }}</b> ({{ $f['meeting']->start_time->format('D, j M Y, g:i A') }})<br>
                    <span style="color:#a33">{{ \Illuminate\Support\Str::limit($f['error'], 300) }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($stale->isNotEmpty())
        <p><b>Still not in Google Drive {{ config('portal.recording_alert_after_days') }}+ days after the meeting</b></p>
        <ul style="padding-left:18px">
            @foreach ($stale as $m)
                <li style="margin-bottom:6px">
                    <b>{{ $m->title }}</b> ({{ $m->start_time->format('D, j M Y') }}) -
                    {{ $m->recordings_status ?: 'not checked yet' }}@if ($m->recordings_error): {{ \Illuminate\Support\Str::limit($m->recordings_error, 200) }}@endif
                </li>
            @endforeach
        </ul>
    @endif

    <p>
        <a href="{{ route('admin.recordings') }}" style="background:#0b78b8;color:#fff;padding:10px 18px;text-decoration:none;font-weight:bold;border-radius:6px">Open the Recordings page</a>
    </p>
    <p style="color:#777;font-size:12px">You can retry a meeting there with "Retry now". Recordings stay safe on Zoom until they are copied to Drive.</p>
</div>
