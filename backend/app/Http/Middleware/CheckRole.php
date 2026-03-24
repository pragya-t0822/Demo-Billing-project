<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole middleware — enforces RBAC on API routes.
 * Usage in routes: ->middleware('role:STORE_MANAGER,ACCOUNTANT')
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Authentication required.']], 401);
        }

        if (! $user->hasAnyRole($roles)) {
            return response()->json(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have permission to perform this action.']], 403);
        }

        return $next($request);
    }
}
