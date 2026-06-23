<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        $sql = file_get_contents(base_path('dev-resources/db/roles.sql'));

        preg_match('/INSERT INTO `roles`.*?;/s', $sql, $matches);

        if (empty($matches[0])) {
            $this->command->warn('RolesTableSeeder: no INSERT statement found in roles.sql');

            return;
        }

        $insert = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $matches[0]);

        DB::unprepared($insert);

        $this->command->info('RolesTableSeeder: done (INSERT IGNORE INTO roles).');
    }
}
