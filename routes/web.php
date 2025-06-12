<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Classes\ListRoutes;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public Routes (No Middleware)
$publicRoutes = (new ListRoutes)->getDataAuth();
foreach ($publicRoutes as $key_list => $value_list) {
    if (!empty($value_list['item'])) {
        $item = $value_list['item'];

        foreach ($item as $key => $value) {
            $value = (object)$value;
            if (!empty($value->method)) {
                $hasil = null;

                if (is_array($value->method)) {
                    $hasil = Route::match($value->method, $value->url, $value->controller);
                } else {
                    if (strtoupper($value->method) == 'GET') {
                        $hasil = Route::get($value->url, $value->controller);
                    }
                    if (strtoupper($value->method) == 'POST') {
                        $hasil = Route::post($value->url, $value->controller);
                    }
                    if (strtoupper($value->method) == 'PUT') {
                        $hasil = Route::put($value->url, $value->controller);
                    }
                    if (strtoupper($value->method) == 'DELETE') {
                        $hasil = Route::delete($value->url, $value->controller);
                    }
                    if (strtoupper($value->method) == 'PATCH') {
                        $hasil = Route::patch($value->url, $value->controller);
                    }
                }

                if (!empty($hasil)) {
                    if (!empty($value->name)) {
                        $hasil->name($value->name);
                    }

                    if (!empty($value->middleware)) {
                        $hasil->middleware($value->middleware);
                    }
                }
            }
        }
    }
}

// Admin Routes (With Middleware)
$adminRoutes = (new ListRoutes)->getDataAdmin();
foreach ($adminRoutes as $key_list => $value_list) {
    if (!empty($value_list['item'])) {
        $item = $value_list['item'];

        foreach ($item as $key => $value) {
            $value = (object)$value;
            if (!empty($value->method)) {
                $hasil = null;

                if (is_array($value->method)) {
                    $hasil = Route::match($value->method, $value->url, $value->controller);
                } else {
                    if (strtoupper($value->method) == 'GET') {
                        $hasil = Route::get($value->url, $value->controller);
                    }
                    if (strtoupper($value->method) == 'POST') {
                        $hasil = Route::post($value->url, $value->controller);
                    }
                    if (strtoupper($value->method) == 'PUT') {
                        $hasil = Route::put($value->url, $value->controller);
                    }
                    if (strtoupper($value->method) == 'DELETE') {
                        $hasil = Route::delete($value->url, $value->controller);
                    }
                    if (strtoupper($value->method) == 'PATCH') {
                        $hasil = Route::patch($value->url, $value->controller);
                    }
                }

                if (!empty($hasil)) {
                    if (!empty($value->name)) {
                        $hasil->name($value->name);
                    }

                    if (!empty($value->middleware)) {
                        $hasil->middleware($value->middleware);
                    }
                }
            }
        }
    }
}

// Admin routes tanpa middleware
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login');

Route::post('/admin/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    try {
        $admin = \App\Models\AdminUser::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if ($admin && Hash::check($request->password, $admin->password)) {
            Session::put('admin_logged_in', true);
            Session::put('admin_id', $admin->id);
            Session::put('admin_name', $admin->name);
            Session::put('admin_email', $admin->email);
            Session::put('admin_role', $admin->role);

            // Update last login jika method ada
            if (method_exists($admin, 'updateLastLogin')) {
                $admin->updateLastLogin($request->ip());
            }

            return redirect('/admin/dashboard')->with('success', 'Login berhasil!');
        }

        return back()->withErrors(['login' => 'Email atau password salah!'])
            ->withInput($request->only('email'));
    } catch (\Exception $e) {
        Log::error('Login Error: ' . $e->getMessage());
        return back()->withErrors(['login' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
})->name('admin.login.submit');

Route::get('/admin/dashboard', function () {
    if (!Session::get('admin_logged_in')) {
        return redirect('/admin/login')->with('error', 'Please login first');
    }

    // Simple dashboard view
    return view('admin.dashboard', [
        'adminName' => Session::get('admin_name', 'Admin'),
        'stats' => [
            'total_orders' => 0,
            'pending_orders' => 0,
            'completed_orders' => 0,
            'monthly_revenue' => 0,
            'weekly_orders' => 0,
            'active_services' => 0
        ],
        'recent_orders' => collect(),
        'monthly_chart_data' => [
            'labels' => [],
            'revenue' => [],
            'orders' => []
        ]
    ]);
})->name('admin.dashboard');

Route::post('/admin/logout', function () {
    Session::forget(['admin_logged_in', 'admin_id', 'admin_name', 'admin_email', 'admin_role']);
    return redirect('/admin/login')->with('success', 'Logout berhasil!');
})->name('admin.logout');

Route::get('/admin', function () {
    if (Session::get('admin_logged_in')) {
        return redirect('/admin/dashboard');
    }
    return redirect('/admin/login');
});


// Fallback route for 404
Route::fallback(function () {
    return redirect()->route('home');
});


Route::get('/debug-controllers', function () {
    $controllers = [
        'ProductController' => file_exists(app_path('Http/Controllers/Admin/ProductController.php')),
        'DashboardController' => file_exists(app_path('Http/Controllers/Admin/DashboardController.php')),
        'Directory exists' => is_dir(app_path('Http/Controllers/Admin')),
        'Files in Admin' => is_dir(app_path('Http/Controllers/Admin')) ?
            scandir(app_path('Http/Controllers/Admin')) : 'No directory'
    ];

    return response()->json($controllers);
});
