<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ExpiryDateHelper
{
    public static function format(?string $expiry): string
    {
        if (! self::hasExpiry($expiry)) {
            return '—';
        }

        try {
            return Carbon::parse($expiry)->format('d M Y');
        } catch (\Throwable) {
            return $expiry;
        }
    }

    public static function hasExpiry(?string $expiry): bool
    {
        return $expiry !== null && $expiry !== '' && $expiry !== '0000-00-00';
    }

    public static function isExpired(?string $expiry): bool
    {
        if (! self::hasExpiry($expiry)) {
            return false;
        }

        try {
            return Carbon::parse($expiry)->startOfDay()->lt(now()->startOfDay());
        } catch (\Throwable) {
            return false;
        }
    }

    public static function textClass(?string $expiry): string
    {
        if (! self::hasExpiry($expiry)) {
            return 'text-zinc-700 dark:text-zinc-300';
        }

        return self::isExpired($expiry)
            ? 'text-red-600 dark:text-red-400 font-medium'
            : 'text-emerald-600 dark:text-emerald-400 font-medium';
    }
}
