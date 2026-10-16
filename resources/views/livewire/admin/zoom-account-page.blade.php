<div>
    <x-hero subtitle="Add the Zoom hosts the portal can book meetings on." />

    @unless (filled(config('services.zoom.account_id')) && filled(config('services.zoom.client_id')) && filled(config('services.zoom.client_secret')))
        <div class="note" style="border-color:#ecd391;background:var(--warn-l);color:var(--warn)">
            Zoom credentials are not set. Add <code>ZOOM_ACCOUNT_ID</code>, <code>ZOOM_CLIENT_ID</code> and
            <code>ZOOM_CLIENT_SECRET</code> to the <code>.env</code> file, then reload.
        </div>
    @endunless

    {{ $this->table }}
</div>
