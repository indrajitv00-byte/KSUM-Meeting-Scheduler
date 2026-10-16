<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every permission listed in config/perms.php and three roles:
 *   super - everything
 *   admin - admin:* permissions + the common ones
 *   user  - the common ones (book / cancel own meetings)
 * Safe to run again after adding permissions to config/perms.php.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];
        $common = [];
        $admin = [];

        foreach (config('perms') as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $this->command?->info($name);

            $all[] = $permission->id;
            if (count(explode(':', $name)) > 2) {
                $admin[] = $permission->id; // "admin:users:view"
            } else {
                $common[] = $permission->id; // "meetings:view"
            }
        }

        Role::firstOrCreate(['name' => 'super', 'guard_name' => 'web'])->syncPermissions($all);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['type' => 'admin'])
            ->syncPermissions(array_merge($common, $admin));
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web'], ['type' => 'user'])
            ->syncPermissions($common);
    }
}
