<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
     * The permission→role map (~600 rows) is cached to avoid hydrating every
     * Permission model on each request. RBAC is edited in DCM (a separate
     * app that cannot clear this cache), so the cache key carries a cheap
     * fingerprint of `permissions` + `permission_role`: any added, removed,
     * renamed or re-assigned permission changes the key and the map is
     * rebuilt on the next request.
     */
    private function registerPermissionGates(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            // [permissionName => [roleId, ...]]
            $map = Cache::remember(
                'permission_role_map:'.$this->permissionFingerprint(),
                now()->addHours(24),
                fn (): array => Permission::with('roles:id')->get()
                    ->mapWithKeys(fn (Permission $permission): array => [
                        $permission->name => $permission->roles
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->all(),
                    ])
                    ->all(),
            );
        } catch (Throwable) {
            // DB/cache/tables unavailable (e.g. during setup) — skip gate registration.
            return;
        }

        foreach ($map as $name => $roleIds) {
            Gate::define($name, function (User $user) use ($roleIds): bool {
                return $roleIds !== [] && array_intersect($roleIds, $user->roleIds()) !== [];
            });
        }
    }

    /**
     * One aggregate query that changes whenever a permission is added,
     * removed or renamed, or a permission↔role assignment changes.
     */
    private function permissionFingerprint(): string
    {
        $row = DB::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM permissions) AS p_count,
                (SELECT COALESCE(SUM(id), 0) FROM permissions) AS p_ids,
                (SELECT MAX(updated_at) FROM permissions) AS p_updated,
                (SELECT COUNT(*) FROM permission_role) AS pr_count,
                (SELECT COALESCE(SUM(permission_id * 1000 + role_id), 0) FROM permission_role) AS pr_sum,
                (SELECT COALESCE(SUM(permission_id * role_id), 0) FROM permission_role) AS pr_product'
        );

        return md5(implode('|', (array) $row));
    }
}
