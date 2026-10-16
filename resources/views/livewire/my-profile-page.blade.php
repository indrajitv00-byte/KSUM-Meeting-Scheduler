<div>
    <x-hero subtitle="Keep your details up to date and change your password." />

    <section class="card">
        <h2 class="ttl">Your details</h2>

        <form wire:submit="saveProfile">
            {{ $this->profileForm }}

            <div class="actions">
                <button class="btn btn-primary" type="submit">Save changes</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="ttl">Change password</h2>

        <form wire:submit="changePassword">
            {{ $this->passwordForm }}

            <div class="actions">
                <button class="btn btn-primary" type="submit">Change password</button>
            </div>
        </form>
    </section>
</div>
