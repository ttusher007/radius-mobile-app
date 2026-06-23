<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminPermissionRoleSeeder extends Seeder
{
    public function run(): void
    {
        $sql = file_get_contents(base_path('dev-resources/db/permission_role.sql'));

        // Extract all (permission_id, role_id) pairs from the INSERT block
        preg_match_all('/\((\d+),\s*(\d+)\)/', $sql, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            $this->command->warn('AdminPermissionRoleSeeder: no pairs found in permission_role.sql');

            return;
        }

        // Keep only admin (role_id = 1) entries
        $rows = array_filter($matches, fn ($m) => (int) $m[2] === 1);
        $rows = array_map(fn ($m) => [
            'permission_id' => (int) $m[1],
            'role_id' => 1,
        ], $rows);

        $rows = array_values($rows);

        // Insert in chunks to avoid hitting query size limits
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('permission_role')->insertOrIgnore($chunk);
        }

        $this->command->info(sprintf(
            'AdminPermissionRoleSeeder: done (%d admin permission entries seeded/skipped).',
            count($rows)
        ));
    }
}
