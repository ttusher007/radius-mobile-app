<?php

namespace App\Http\Middleware;

use App\Exceptions\InsufficientPermissionException;
use App\Support\RoutePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $result = RoutePermissions::evaluate($routeName);

        if (! $result->allowed) {
            throw new InsufficientPermissionException($result);
        }

        return $next($request);
    }
}
