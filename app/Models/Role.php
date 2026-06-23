<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Permissions granted to this role (pivot: permission_role).
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Users assigned this role (pivot: role_user).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
