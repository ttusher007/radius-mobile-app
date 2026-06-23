<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'company', 'message', 'photo', 'printer', 'is_token_admin', 'active_state', 'ledger_id', 'emp_id'])]
#[Hidden(['password', 'remember_token', 'api_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected function casts(): array
    {
        return [
            'password'     => 'hashed',
            'active_state' => 'integer',
            'ledger_id'    => 'integer',
            'emp_id'       => 'integer',
            'last_login'   => 'datetime',
        ];
    }
}
