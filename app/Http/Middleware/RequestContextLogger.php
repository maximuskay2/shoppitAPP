<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestContextLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-Id') ?: (string) str()->uuid();
        $request->headers->set('X-Request-Id', $requestId);

        $admin = $request->user('admin-api') ?? $request->user('admin');
        $user = $request->user();
        $driver = $request->user('driver');

        Log::withContext([
            'request_id' => $requestId,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'admin_id' => $admin?->id,
            'user_id' => $user?->id,
            'driver_id' => $driver?->id,
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
