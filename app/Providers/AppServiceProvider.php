<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
     *
     * The permission→role map (~600 rows) is static between RBAC edits, so it
     * is cached to avoid hydrating every Permission model on each request.
     * Run `php artisan cache:forget permission_role_map` (or `cache:clear`)
     * after changing role/permission assignments.
     */
    private function registerPermissionGates(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            // [permissionName => [roleId, ...]] — cached; only the cache table
            // is touched on a hit, not the 600+ permission rows.
            $map = Cache::get('permission_role_map');

            if ($map === null) {
                if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
                    return;
                }

                $map = Permission::with('roles:id')->get()
                    ->mapWithKeys(fn (Permission $permission): array => [
                        $permission->name => $permission->roles
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->all(),
                    ])
                    ->all();

                Cache::put('permission_role_map', $map, now()->addHours(24));
            }
        } catch (Throwable) {
            // DB/cache unavailable (e.g. during setup) — skip gate registration.
            return;
        }

        foreach ($map as $name => $roleIds) {
            Gate::define($name, function (User $user) use ($roleIds): bool {
                return $roleIds !== [] && array_intersect($roleIds, $user->roleIds()) !== [];
            });
        }
    }
}
