<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Webhook routes - semua variasi
        'webhook/*',
        '/webhook/*',
        'webhook/whatsapp',
        '/webhook/whatsapp',
        'webhook/whatsapp/*',
        '/webhook/whatsapp/*',

        // API routes
        'api/*',
        '/api/*',

        // External callbacks
        'callback/*',
        'hook/*',

        // Testing routes
        'test-*',
    ];

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        // Custom logic untuk webhook
        if ($request->is('webhook/*') || $request->is('*/webhook/*')) {
            return true;
        }

        // Check if User-Agent contains 'fonnte' (FONNTE webhook)
        $userAgent = $request->header('User-Agent', '');
        if (stripos($userAgent, 'fonnte') !== false) {
            return true;
        }

        return parent::inExceptArray($request);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Custom logic jika diperlukan
        return parent::handle($request, $next);
    }
}
