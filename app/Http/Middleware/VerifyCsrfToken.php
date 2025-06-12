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
        // Exclude API routes dari CSRF
        'api/*',

        // Exclude webhook routes
        'webhook/*',

        // Exclude payment callback (jika ada)
        'payment/callback/*',

        // Jangan exclude form upload - biarkan CSRF aktif untuk keamanan
        // 'documents/*',
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
