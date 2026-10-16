<div class="flex flex-col items-center justify-center p-5 py-24">
    <div class="flex items-center justify-center my-5">
        <img src="/img/logo.svg" alt="" class="h-24">
    </div>

    <div class="text-center font-xl font-bold mb-5">
        Forgot Your Password
    </div>

    <x-filament::section class="md:w-md w-full">
        <form wire:submit="login">
            {{ $this->form }}

            <div class="mt-5 flex justify-end">
                <x-filament::button type="submit" class="">
                    Reset Password
                </x-filament::button>
            </div>

        </form>
    </x-filament::section>
</div>
