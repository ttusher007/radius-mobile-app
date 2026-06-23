<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Production table is named `user` (int PK, no email_verified_at, extra app columns).
        if (! Schema::hasTable('user')) {
            Schema::create('user', function (Blueprint $table) {
                $table->increments('id');                                    // int UNSIGNED AUTO_INCREMENT
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password', 60);
                $table->rememberToken();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->string('is_token_admin', 1)->nullable();
                $table->integer('active_state')->default(1);
                $table->string('company', 35)->default(config('app.name', ''));
                $table->string('message', 250)->nullable();
                $table->dateTime('last_login')->nullable();
                $table->text('last_login_ip')->nullable();
                $table->string('api_token', 32)->nullable();
                $table->integer('ledger_id')->nullable();
                $table->integer('emp_id')->nullable();
                $table->string('printer', 8)->nullable();
                $table->string('photo', 500)->default('http://erp.antbd.net/assets/images/user_photo/defult.jpg');
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->unsignedInteger('user_id')->nullable()->index();     // int to match user.id type
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        // Intentional no-op — never drop live production tables via rollback.
    }
};
