<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\User;

/**
 * Resolves and evaluates route-level RBAC requirements from config/route_permissions.php.
 */
class RoutePermissions
{
    /** Permissions that can grant manager / POP scope (shown when scope is missing). */
    public const SCOPE_PERMISSIONS = [
        'perm_all_manager',
        'perm_manager',
        'perm_asst_manager',
        'perm_external_manager',
        'perm_internal_manager',
    ];

    /**
     * @return array<string, array{label: string, any?: array<string>, require_scope?: bool}>
     */
    public static function definitions(): array
    {
        return config('route_permissions', []);
    }

    /**
     * @return array{label: string, any?: array<string>, require_scope?: bool}|null
     */
    public static function forRoute(?string $routeName): ?array
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        $definition = self::definitions()[$routeName] ?? null;

        return is_array($definition) ? $definition : null;
    }

    public static function isAllowed(?string $routeName, ?User $user = null): bool
    {
        return self::evaluate($routeName, $user)->allowed;
    }

    public static function evaluate(?string $routeName, ?User $user = null): RouteAccessResult
    {
        $definition = self::forRoute($routeName);

        if ($definition === null) {
            return new RouteAccessResult(allowed: true);
        }

        $user ??= auth()->user();
        $any = $definition['any'] ?? [];
        $requireScope = (bool) ($definition['require_scope'] ?? false);
        $label = $definition['label'] ?? 'This page';

        $missingPermissions = [];
        if ($any !== [] && ! AccessHelper::any($any, $user)) {
            $missingPermissions = $any;
        }

        $missingScope = $requireScope && ! BillingScope::hasAnyScope();

        $allowed = $missingPermissions === [] && ! $missingScope;

        return new RouteAccessResult(
            allowed: $allowed,
            pageLabel: $label,
            missingPermissions: $missingPermissions,
            missingScope: $missingScope,
            scopePermissions: $requireScope ? self::SCOPE_PERMISSIONS : [],
        );
    }

    /**
     * @param  array<string>  $names
     * @return array<int, array{name: string, description: string|null}>
     */
    public static function describePermissions(array $names): array
    {
        if ($names === []) {
            return [];
        }

        $descriptions = Permission::query()
            ->whereIn('name', $names)
            ->get(['name', 'description'])
            ->keyBy('name');

        return collect($names)
            ->map(fn (string $name): array => [
                'name' => $name,
                'description' => self::normaliseDescription(
                    $descriptions->get($name)?->description
                ),
            ])
            ->values()
            ->all();
    }

    private static function normaliseDescription(?string $description): ?string
    {
        $description = trim((string) $description);

        return $description !== '' ? $description : null;
    }
}
