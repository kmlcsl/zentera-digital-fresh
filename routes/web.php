<?php

use Illuminate\Support\Facades\Route;
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

// Additional Routes
// Route::get('/login', function () {
//     return redirect()->route('admin.login');
// })->name('login');

// Route::get('/admin', function () {
//     if (Session::get('admin_logged_in') && Session::get('admin_id')) {
//         return redirect()->route('admin.dashboard');
//     }
//     return redirect()->route('admin.login');
// });


// Admin routes tanpa middleware
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login');

Route::post('/admin/login', function (Request $request) {
    // Login logic
})->name('admin.login.submit');

Route::get('/admin/dashboard', function () {
    // Dashboard logic
})->name('admin.dashboard');

// Fallback route for 404
Route::fallback(function () {
    return redirect()->route('home');
});
