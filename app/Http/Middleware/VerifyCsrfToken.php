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
        // Webhook routes
        'webhook/*',
        '/webhook/whatsapp',
        'webhook/whatsapp',
        'api/webhook/*',
        'https://www.zenteradigital.my.id/api/webhook/whatsapp',
        'https://zenteradigital.my.id/api/webhook/whatsapp',

        // API routes
        'api/*',

        // Any other external callbacks
        'callback/*',
        'hook/*',
    ];

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
