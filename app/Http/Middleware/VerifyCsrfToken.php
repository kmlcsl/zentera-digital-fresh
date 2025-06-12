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
        'webhook/*',
        'webhook/whatsapp',
        'webhook/whatsapp/*',
        'api/*',
        // Add specific paths
        '/webhook/whatsapp',
        'https://www.zenteradigital.my.id/webhook/whatsapp',
        'https://zenteradigital.my.id/webhook/whatsapp',
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
