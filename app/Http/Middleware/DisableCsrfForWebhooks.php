<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableCsrfForWebhooks
{
    public function handle(Request $request, Closure $next)
    {
        // Skip CSRF for webhook routes
        if ($request->is('webhook/*') || $request->is('webhook/whatsapp')) {
            return $next($request);
        }

        return $next($request);
    }
}
