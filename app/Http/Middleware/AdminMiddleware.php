<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah admin sudah login via session
        if (!Session::get('admin_logged_in')) {
            // Jika tidak login, redirect ke halaman login admin
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('admin.login')->with('error', 'Please login to access admin panel.');
        }

        // Cek apakah session admin masih valid
        if (!Session::get('admin_id')) {
            Session::forget(['admin_logged_in', 'admin_id', 'admin_name', 'admin_email', 'admin_role']);
            return redirect()->route('admin.login')->with('error', 'Session expired. Please login again.');
        }

        // Jika semua validasi lolos, lanjutkan ke request berikutnya
        return $next($request);
    }
}
