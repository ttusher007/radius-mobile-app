<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,
            AdminPermissionRoleSeeder::class,
            // RoleUserSeeder intentionally omitted — entries are added manually.
        ]);
    }
}
