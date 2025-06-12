<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika admin sudah login via session, redirect ke dashboard
        if (Session::get('admin_logged_in') && Session::get('admin_id')) {
            return redirect()->route('admin.dashboard');
        }

        // Jika belum login, lanjutkan ke halaman login
        return $next($request);
    }
}
