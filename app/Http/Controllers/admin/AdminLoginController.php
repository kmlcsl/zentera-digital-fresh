<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\AdminUser;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $admin = AdminUser::where('email', $request->email)
                ->where('is_active', true)
                ->first();

            if ($admin && Hash::check($request->password, $admin->password)) {
                Session::put('admin_logged_in', true);
                Session::put('admin_id', $admin->id);
                Session::put('admin_name', $admin->name);
                Session::put('admin_email', $admin->email);
                Session::put('admin_role', $admin->role);

                $admin->updateLastLogin($request->ip());

                return redirect()->route('admin.dashboard');
            }

            return back()->withErrors(['login' => 'Email atau password salah!']);
        } catch (\Exception $e) {
            return back()->withErrors(['login' => 'Terjadi kesalahan sistem.']);
        }
    }

    public function logout()
    {
        Session::forget(['admin_logged_in', 'admin_id', 'admin_name', 'admin_email', 'admin_role']);
        return redirect()->route('admin.login');
    }
}
