<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $sql = file_get_contents(base_path('dev-resources/db/permissions.sql'));

        preg_match('/INSERT INTO `permissions`.*?;/s', $sql, $matches);

        if (empty($matches[0])) {
            $this->command->warn('PermissionsTableSeeder: no INSERT statement found in permissions.sql');

            return;
        }

        $insert = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $matches[0]);

        DB::unprepared($insert);

        $this->command->info('PermissionsTableSeeder: done (INSERT IGNORE INTO permissions).');
    }
}
