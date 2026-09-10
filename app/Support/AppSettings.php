<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AppSettings
{
    public const MANDATORY_RECHARGE_CUSTOMER = 'mandatory_recharge_customer';

    /** MUSHAK-6.3 tax invoice header fields (NBR, Bangladesh). */
    public const REGISTERED_PERSON_NAME = 'registered_person_name';

    public const BIN = 'bin';

    public const INVOICE_ISSUING_ADDRESS = 'invoice_issuing_address';

    /**
     * Public path of the server-specific company logo shown on the receipt.
     * The file itself is git-ignored (each server uploads its own).
     */
    public const LOGO_PATH = 'branding/receipt-logo.png';

    public static function mandatoryRechargeCustomer(): bool
    {
        return self::boolean(self::MANDATORY_RECHARGE_CUSTOMER);
    }

    public static function setMandatoryRechargeCustomer(bool $enabled): void
    {
        self::setBoolean(self::MANDATORY_RECHARGE_CUSTOMER, $enabled);
    }

    public static function registeredPersonName(): string
    {
        return self::string(self::REGISTERED_PERSON_NAME);
    }

    public static function bin(): string
    {
        return self::string(self::BIN);
    }

    public static function invoiceIssuingAddress(): string
    {
        return self::string(self::INVOICE_ISSUING_ADDRESS);
    }

    /**
     * Absolute URL of the receipt logo, or NULL when no logo has been
     * uploaded on this server.
     */
    public static function logoUrl(): ?string
    {
        $file = public_path(self::LOGO_PATH);

        if (! is_file($file)) {
            return null;
        }

        // Cache-bust so a replaced logo shows up without a hard refresh.
        return asset(self::LOGO_PATH).'?v='.filemtime($file);
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        $value = self::raw($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::raw($key);

        return $value === null ? $default : (string) $value;
    }

    public static function setBoolean(string $key, bool $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value ? '1' : '0'],
        );
    }

    public static function setString(string $key, ?string $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => trim((string) $value)],
        );
    }

    private static function raw(string $key): ?string
    {
        try {
            if (! Schema::hasTable('app_settings')) {
                return null;
            }

            $value = AppSetting::query()->where('key', $key)->value('value');
        } catch (Throwable) {
            return null;
        }

        return $value === null ? null : (string) $value;
    }
}
