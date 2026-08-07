<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AppSettings
{
    public const MANDATORY_RECHARGE_CUSTOMER = 'mandatory_recharge_customer';

    public static function mandatoryRechargeCustomer(): bool
    {
        return self::boolean(self::MANDATORY_RECHARGE_CUSTOMER);
    }

    public static function setMandatoryRechargeCustomer(bool $enabled): void
    {
        self::setBoolean(self::MANDATORY_RECHARGE_CUSTOMER, $enabled);
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        try {
            if (! Schema::hasTable('app_settings')) {
                return $default;
            }

            $value = AppSetting::query()->where('key', $key)->value('value');
        } catch (Throwable) {
            return $default;
        }

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function setBoolean(string $key, bool $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value ? '1' : '0'],
        );
    }
}
