<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IMPORTANT:
        // - Production contains live data. This migration is strictly "create-if-missing".
        // - No drops, no truncates, no destructive changes.
        // - Seeding runs only when the table did NOT exist and only for MySQL/MariaDB.

        $created = [
            'master_groups' => false,
            'account_groups' => false,
            'ledgers' => false,
            'ledger_users' => false,
        ];

        if (! Schema::hasTable('master_groups')) {
            Schema::create('master_groups', function (Blueprint $table) {
                $table->increments('Master_Group_Id'); // int UNSIGNED AUTO_INCREMENT
                $table->string('Master_Group_Name', 150);
                $table->integer('Trial_Balance')->nullable();
            });

            $created['master_groups'] = true;
        }

        if (! Schema::hasTable('account_groups')) {
            Schema::create('account_groups', function (Blueprint $table) {
                $table->increments('Account_Group_Id'); // int UNSIGNED AUTO_INCREMENT
                $table->string('Account_Group_Name', 150);
                $table->unsignedInteger('Master_Group_Id')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('Master_Group_Id', 'fk');
            });

            // Add the FK only when both tables exist (fresh install or already present).
            if (Schema::hasTable('master_groups')) {
                Schema::table('account_groups', function (Blueprint $table) {
                    $table
                        ->foreign('Master_Group_Id', 'fk')
                        ->references('Master_Group_Id')
                        ->on('master_groups');
                });
            }

            $created['account_groups'] = true;
        }

        if (! Schema::hasTable('ledgers')) {
            Schema::create('ledgers', function (Blueprint $table) {
                // Legacy dump defines `Ledger_Id` as `int NOT NULL` (signed), then later AUTO_INCREMENT.
                // We intentionally keep it signed to match the `ledger_users.ledger_id` type.
                $table->integer('Ledger_Id')->autoIncrement();
                $table->primary('Ledger_Id');
                $table->string('Ledger_Name', 250)->nullable();
                $table->integer('Account_Group_Id'); // legacy dump uses int (not unsigned)
                $table->string('Address', 500)->nullable();
                $table->string('Contact_Person', 100)->nullable();
                $table->string('Contact_Number', 50)->nullable();
                $table->string('Email', 50)->nullable();
                $table->string('Ledger_Code', 20)->nullable();
                $table->decimal('Opening_Balance', 15, 2)->nullable();
                $table->text('Balance_Type')->nullable(); // tinytext in MySQL
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->integer('Reseller_Id')->nullable();

                $table->index('Account_Group_Id', 'Account_Group_Id');
            });

            // Intentionally NOT adding an FK to account_groups here.
            // Some environments may have historical/inconsistent data; an FK could block migrations.
            $created['ledgers'] = true;
        }

        if (! Schema::hasTable('ledger_users')) {
            Schema::create('ledger_users', function (Blueprint $table) {
                $table->integer('ledger_id');
                $table->unsignedInteger('user_id');
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable();

                $table->unique(['ledger_id', 'user_id'], 'ledger_id');
                $table->index('user_id', 'user_id');
            });

            // Add constraints only if referenced tables exist.
            if (Schema::hasTable('ledgers')) {
                Schema::table('ledger_users', function (Blueprint $table) {
                    $table
                        ->foreign('ledger_id', 'ledger_user_ibfk_1')
                        ->references('Ledger_Id')
                        ->on('ledgers');
                });
            }

            // Production table name is `user` (not `users`).
            if (Schema::hasTable('user')) {
                Schema::table('ledger_users', function (Blueprint $table) {
                    $table
                        ->foreign('user_id', 'ledger_user_ibfk_2')
                        ->references('id')
                        ->on('user');
                });
            }

            $created['ledger_users'] = true;
        }

        $driver = DB::getDriverName();
        $isMysqlFamily = in_array($driver, ['mysql', 'mariadb'], true);

        if (! $isMysqlFamily) {
            return;
        }

        DB::transaction(function () use ($created) {
            if ($created['master_groups'] && $this->tableIsEmpty('master_groups')) {
                $this->seedFromSqlInserts(base_path('dev-resources/db/master_groups.sql'), 'master_groups');
            }

            if ($created['account_groups'] && $this->tableIsEmpty('account_groups')) {
                $this->seedFromSqlInserts(base_path('dev-resources/db/account_groups.sql'), 'account_groups');
            }

            if ($created['ledgers'] && $this->tableIsEmpty('ledgers')) {
                $this->seedFromSqlInserts(base_path('dev-resources/db/ledgers.sql'), 'ledgers');
            }

            if ($created['ledger_users'] && $this->tableIsEmpty('ledger_users')) {
                $path = base_path('dev-resources/db/ledger_users.sql');
                if ($this->ledgerUsersSeedWouldBeValid($path)) {
                    $this->seedFromSqlInserts($path, 'ledger_users');
                }
            }
        });
    }

    public function down(): void
    {
        // Intentional no-op — never drop live production tables via rollback.
    }

    private function tableIsEmpty(string $table): bool
    {
        return DB::table($table)->limit(1)->count() === 0;
    }

    private function seedFromSqlInserts(string $path, string $table): void
    {
        if (! is_file($path)) {
            return;
        }

        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            return;
        }

        // Execute only INSERT statements for the requested table.
        // This avoids CREATE TABLE / ALTER TABLE / COMMIT from phpMyAdmin dumps.
        $pattern = '/INSERT\s+INTO\s+`?' . preg_quote($table, '/') . '`?\s*\\([\\s\\S]*?;\\s*/i';
        if (! preg_match_all($pattern, $sql, $matches)) {
            return;
        }

        foreach ($matches[0] as $statement) {
            // Legacy dumps may contain MySQL "zero" dates which are invalid under strict SQL modes.
            // We convert them to NULL so seeding succeeds on modern MySQL.
            $statement = preg_replace("/'0000-00-00 00:00:00'/", 'NULL', $statement) ?? $statement;
            $statement = preg_replace("/'0000-00-00'/", 'NULL', $statement) ?? $statement;

            DB::unprepared($statement);
        }
    }

    private function ledgerUsersSeedWouldBeValid(string $path): bool
    {
        if (! Schema::hasTable('user')) {
            return false;
        }

        if (! is_file($path)) {
            return false;
        }

        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            return false;
        }

        // Extract referenced user ids from VALUES tuples: (ledger_id, user_id, ...)
        if (! preg_match_all('/\\(\\s*\\d+\\s*,\\s*(\\d+)\\s*,/m', $sql, $m)) {
            return true; // nothing to validate
        }

        $userIds = array_values(array_unique(array_map('intval', $m[1])));
        if ($userIds === []) {
            return true;
        }

        $existing = DB::table('user')->whereIn('id', $userIds)->count();

        return $existing === count($userIds);
    }
};

