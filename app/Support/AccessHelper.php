<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Thin wrapper over the DCM-style permission gates (registered from the
 * `permissions` table in AppServiceProvider). Lets views and components ask
 * "does the user hold ANY of these permissions?" — the same RBAC model used
 * throughout the legacy DCM app.
 */
class AccessHelper
{
    /**
     * @param  array<string>  $permissions
     */
    public static function any(array $permissions, ?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (Gate::forUser($user)->allows($permission)) {
                return true;
            }
        }

        return false;
    }
}
