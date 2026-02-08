<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AlertOnServerError
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 500 && config('logging.channels.slack.url')) {
            Log::channel('slack')->error('Server error response', [
                'status' => $response->getStatusCode(),
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
            ]);
        }

        return $response;
    }
}
