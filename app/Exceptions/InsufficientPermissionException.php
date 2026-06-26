<?php

namespace App\Exceptions;

use App\Support\RouteAccessResult;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InsufficientPermissionException extends HttpException
{
    public function __construct(public readonly RouteAccessResult $result)
    {
        parent::__construct(403, 'You do not have permission to access this page.');
    }

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
        return $this->result->toViewData();
    }
}
