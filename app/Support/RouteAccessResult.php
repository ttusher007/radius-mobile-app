<?php

namespace App\Support;

readonly class RouteAccessResult
{
    /**
     * @param  array<string>  $missingPermissions
     * @param  array<string>  $scopePermissions
     */
    public function __construct(
        public bool $allowed,
        public string $pageLabel = 'This page',
        public array $missingPermissions = [],
        public bool $missingScope = false,
        public array $scopePermissions = [],
    ) {}

    /**
     * @return array{
     *     pageLabel: string,
     *     missingPermissions: array<int, array{name: string, description: string|null}>,
     *     missingScope: bool,
     *     scopePermissions: array<int, array{name: string, description: string|null}>
     * }
     */
    public function toViewData(): array
    {
        return [
            'pageLabel' => $this->pageLabel,
            'missingPermissions' => RoutePermissions::describePermissions($this->missingPermissions),
            'missingScope' => $this->missingScope,
            'scopePermissions' => RoutePermissions::describePermissions($this->scopePermissions),
        ];
    }
}
