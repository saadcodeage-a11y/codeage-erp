<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    public function handle(Request $request, Closure $next, string $module, string $ability = 'read'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessModule($module, $ability)) {
            abort(403, 'You are not authorized to access this area.');
        }

        return $next($request);
    }
}
