<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditMiddleware — logs all mutating API requests (POST/PUT/PATCH/DELETE)
 * to the audit trail automatically. Per-action granular logging is done
 * inside each Service; this middleware captures the HTTP-level context.
 */
class AuditMiddleware
{
    public function __construct(private readonly AuditService $auditService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log mutating requests from authenticated users
        if (
            Auth::check()
            && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])
            && $response->getStatusCode() < 400
        ) {
            $this->auditService->log(
                action: 'HTTP_' . $request->method(),
                entityType: 'HttpRequest',
                entityId: $request->path(),
                afterState: [
                    'method'   => $request->method(),
                    'path'     => $request->path(),
                    'status'   => $response->getStatusCode(),
                ],
            );
        }

        return $response;
    }
}
