<?php

namespace App\Support;

use App\Models\AccountGroup;
use App\Models\Ledger;
use App\Models\Pop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Resolves which managers (resellers) and POPs the current user may see.
 *
 * Ported from the legacy DCM ResellerPermissionHelper. Each accessor returns
 * either the boolean TRUE (meaning "everything — no restriction") or an array
 * of permitted IDs. Callers MUST treat TRUE and [] differently: TRUE = all,
 * [] = nothing.
 */
class ResellerPermissionHelper
{
    /**
     * @return true|array<int>  TRUE means access to every reseller.
     */
    public static function getResellerIds(?int $userId = null): bool|array
    {
        $user = self::resolveUser($userId);
        if (! $user) {
            return [];
        }

        if (Gate::forUser($user)->allows('perm_all_manager')) {
            return true;
        }

        if (self::userAllowsAny($user, ['perm_manager', 'perm_asst_manager'])) {
            return self::pivotIds('reseller_user', 'reseller_id', $user->id);
        }

        if (Gate::forUser($user)->allows('perm_external_manager')) {
            return self::resellerIdsForAccountGroup('Manager');
        }

        if (Gate::forUser($user)->allows('perm_internal_manager')) {
            return self::resellerIdsForAccountGroup('Internal Manager');
        }

        return [];
    }

    /**
     * @return true|array<int>  TRUE means access to every POP.
     */
    public static function getPopIds(?int $userId = null): bool|array
    {
        $user = self::resolveUser($userId);
        if (! $user) {
            return [];
        }

        if (Gate::forUser($user)->allows('perm_all_manager')) {
            return true;
        }

        if (Gate::forUser($user)->allows('perm_asst_manager')) {
            return self::pivotIds('pop_user', 'pop_id', $user->id);
        }

        $resellerIds = self::getResellerIds($user->id);
        if ($resellerIds === true) {
            return true;
        }
        if (empty($resellerIds)) {
            return [];
        }

        return Pop::whereIn('allowresellerid', $resellerIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function hasResellerPermission(int $resellerId, ?int $userId = null): bool
    {
        $ids = self::getResellerIds($userId);

        return $ids === true || in_array($resellerId, $ids, true);
    }

    public static function hasPopPermission(int $popId, ?int $userId = null): bool
    {
        $ids = self::getPopIds($userId);

        return $ids === true || in_array($popId, $ids, true);
    }

    private static function resolveUser(?int $userId): ?User
    {
        if ($userId === null) {
            return auth()->user();
        }

        return User::find($userId);
    }

    /**
     * @param  array<string>  $permissions
     */
    private static function userAllowsAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (Gate::forUser($user)->allows($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int>
     */
    private static function pivotIds(string $table, string $column, int $userId): array
    {
        return DB::table($table)
            ->where('user_id', $userId)
            ->pluck($column)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Reseller IDs whose accounting ledger belongs to the named account group
     * (e.g. "Manager" for external, "Internal Manager" for internal).
     *
     * @return array<int>
     */
    private static function resellerIdsForAccountGroup(string $groupName): array
    {
        $groupId = AccountGroup::where('Account_Group_Name', $groupName)
            ->value('Account_Group_Id');

        if (! $groupId) {
            return [];
        }

        return Ledger::where('Account_Group_Id', $groupId)
            ->whereNotNull('Reseller_Id')
            ->pluck('Reseller_Id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
