<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
            'password' => 'hashed',
            'active_state' => 'integer',
            'ledger_id' => 'integer',
            'emp_id' => 'integer',
            'last_login' => 'datetime',
        ];
    }

    /**
     * Roles assigned to the user (pivot: role_user).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Managers (resellers) directly assigned to this user (pivot: reseller_user).
     */
    public function resellers(): BelongsToMany
    {
        return $this->belongsToMany(Reseller::class, 'reseller_user', 'user_id', 'reseller_id');
    }

    /**
     * POPs directly assigned to this user (pivot: pop_user).
     */
    public function pops(): BelongsToMany
    {
        return $this->belongsToMany(Pop::class, 'pop_user', 'user_id', 'pop_id');
    }

    /**
     * Cached list of the user's role IDs.
     *
     * @return array<int>
     */
    public function roleIds(): array
    {
        return $this->roles->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Whether the user holds the given permission through any of its roles.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->hasRole($permission->roles);
    }

    /**
     * @param  string|Collection<int, Role>  $role
     */
    public function hasRole(string|Collection $role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return $role->intersect($this->roles)->isNotEmpty();
    }
}
