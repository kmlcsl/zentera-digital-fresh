<?php

use Illuminate\Support\Facades\Route;
use App\Classes\ListRoutes;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// PUBLIC ROUTES - Generated from ListRoutes
$publicRoutes = (new ListRoutes)->getDataAuth();
foreach ($publicRoutes as $routeGroup) {
    if (!empty($routeGroup['item'])) {
        foreach ($routeGroup['item'] as $route) {
            $route = (object)$route;

            // Skip webhook and testing routes - these go to api.php
            $ignoreTypes = ['webhook', 'testing'];
            if (in_array($route->type, $ignoreTypes)) {
                continue;
            }

            $result = null;

            switch (strtoupper($route->method)) {
                case 'GET':
                    $result = Route::get($route->url, $route->controller);
                    break;
                case 'POST':
                    $result = Route::post($route->url, $route->controller);
                    break;
                case 'PUT':
                    $result = Route::put($route->url, $route->controller);
                    break;
                case 'DELETE':
                    $result = Route::delete($route->url, $route->controller);
                    break;
            }

            if ($result && !empty($route->name)) {
                $result->name($route->name);
            }
        }
    }
}

// ADMIN ROUTES - Generated from ListRoutes
Route::prefix('admin')->group(function () {
    $adminRoutes = (new ListRoutes)->getDataAdmin();
    foreach ($adminRoutes as $routeGroup) {
        if (!empty($routeGroup['item'])) {
            foreach ($routeGroup['item'] as $route) {
                $route = (object)$route;

                $result = null;

                // Remove /admin prefix from URL since we're in admin group
                $url = str_replace('/admin', '', $route->url);
                if ($url === '') $url = '/';

                switch (strtoupper($route->method)) {
                    case 'GET':
                        $result = Route::get($url, $route->controller);
                        break;
                    case 'POST':
                        $result = Route::post($url, $route->controller);
                        break;
                    case 'PUT':
                        $result = Route::put($url, $route->controller);
                        break;
                    case 'DELETE':
                        $result = Route::delete($url, $route->controller);
                        break;
                }

                if ($result && !empty($route->name)) {
                    $result->name($route->name);
                }
            }
        }
    }
});

// AUTHENTICATION REDIRECTS
Route::get('/login', fn() => redirect('/admin/login'))->name('login');
Route::get('/admin', fn() => redirect('/admin/dashboard'));

// FALLBACK ROUTE
Route::fallback(function () {
    return redirect('/');
});
