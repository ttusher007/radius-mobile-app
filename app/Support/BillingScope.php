<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;

/**
 * Applies the current user's manager (reseller) / POP permission scope to a
 * query joined against radcheck. Mirrors the scoping used in BillView so the
 * Dashboard and Collection Report stay consistent.
 */
class BillingScope
{
    /**
     * Restrict a query to the customers (radcheck rows under $alias) the user
     * may see. A user with no scope at all gets an empty result (never "all").
     */
    public static function applyCustomerScope(Builder $query, string $alias = 'c'): Builder
    {
        $reseller = ResellerPermissionHelper::getResellerIds();
        $pop = ResellerPermissionHelper::getPopIds();

        $resellerAll = $reseller === true;
        $popAll = $pop === true;
        $resellerIds = is_array($reseller) ? $reseller : [];
        $popIds = is_array($pop) ? $pop : [];

        // No access to anything → force an empty set.
        if (! $resellerAll && ! $popAll && $resellerIds === [] && $popIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if (! $resellerAll && $resellerIds !== []) {
            $query->whereIn("{$alias}.resellerid", $resellerIds);
        }
        if (! $popAll && $popIds !== []) {
            $query->whereIn("{$alias}.allowpopid", $popIds);
        }

        return $query;
    }

    /** Whether the user has access to any manager or POP at all. */
    public static function hasAnyScope(): bool
    {
        $reseller = ResellerPermissionHelper::getResellerIds();
        $pop = ResellerPermissionHelper::getPopIds();

        return $reseller === true
            || $pop === true
            || (is_array($reseller) && $reseller !== [])
            || (is_array($pop) && $pop !== []);
    }
}
