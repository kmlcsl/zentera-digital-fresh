<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $role
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        // Cek apakah admin sudah login via session
        if (!Session::get('admin_logged_in') || !Session::get('admin_id')) {
            return redirect('/admin/login')->with('error', 'Please login to access admin panel.');
        }

        // Jika ada parameter role, cek role admin
        if ($role) {
            $adminRole = Session::get('admin_role');

            // Cek apakah admin memiliki role yang diperlukan
            if ($adminRole !== $role) {
                // Jika bukan super admin, cek apakah punya akses
                if ($adminRole !== 'super_admin' && $adminRole !== $role) {
                    abort(403, 'You do not have permission to access this page.');
                }
            }
        }

        return $next($request);
    }
}
