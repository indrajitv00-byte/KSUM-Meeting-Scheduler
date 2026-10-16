<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the first administrator from ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD in .env.
 * If ADMIN_PASSWORD is empty a random one is generated and printed once.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        if (blank($email)) {
            $this->command?->warn('ADMIN_EMAIL is not set in .env - no administrator created.');

            return;
        }

        $user = User::firstOrNew(['email' => strtolower($email)]);

        if (! $user->exists) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);
            $user->name = env('ADMIN_NAME', 'Portal Admin');
            $user->password = $password; // hashed by the model cast
            $user->forceFill(['email_verified_at' => now()])->save();

            if (! env('ADMIN_PASSWORD')) {
                $this->command?->warn("Administrator created. Email: {$user->email}  Password: {$password}  (change it after logging in)");
            } else {
                $this->command?->info("Administrator created: {$user->email}");
            }
        }

        $user->syncRoles(['super']);
    }
}
