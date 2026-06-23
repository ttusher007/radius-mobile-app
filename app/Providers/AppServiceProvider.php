<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
        }

        Blaze::optimize()
            ->in(resource_path('views/components'))
            ->in(resource_path('views/components/layouts'), compile: false);

        $this->registerPermissionGates();
    }

    /**
     * Define one Gate ability per row in the `permissions` table, authorised
     * through the user's roles. Mirrors the legacy DCM permission system so
     * abilities such as `perm_all_manager` / `money-receipt-entry` work as
     * named gates across the app.
     */
    private function registerPermissionGates(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
                return;
            }

            // [permissionName => [roleId, ...]] — loaded once per request.
            $map = [];
            foreach (Permission::with('roles:id')->get() as $permission) {
                $map[$permission->name] = $permission->roles
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        } catch (Throwable) {
            // DB unavailable (e.g. during setup) — skip gate registration.
            return;
        }

        foreach ($map as $name => $roleIds) {
            Gate::define($name, function (User $user) use ($roleIds): bool {
                return $roleIds !== [] && array_intersect($roleIds, $user->roleIds()) !== [];
            });
        }
    }
}
