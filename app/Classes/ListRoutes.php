<?php

namespace App\Classes;

class ListRoutes
{
    function getDataAuth($index = null)
    {
        $data = [
            [
                'title' => 'Public Routes',
                'item' => [
                    [
                        'type' => 'public',
                        'method' => 'get',
                        'url' => '/',
                        'controller' => 'App\Http\Controllers\HomeController@index',
                        'name' => 'home',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'public',
                        'method' => 'get',
                        'url' => '/products',
                        'controller' => 'App\Http\Controllers\ProductController@index',
                        'name' => 'products',
                        'middleware' => '',
                    ]
                ]
            ],
            [
                'title' => 'Document Upload Routes',
                'item' => [
                    [
                        'type' => 'documents',
                        'method' => 'get',
                        'url' => '/documents/upload/repair',
                        'controller' => 'App\Http\Controllers\DocumentUploadController@repairForm',
                        'name' => 'documents.upload.repair',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'documents',
                        'method' => 'get',
                        'url' => '/documents/upload/format',
                        'controller' => 'App\Http\Controllers\DocumentUploadController@formatForm',
                        'name' => 'documents.upload.format',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'documents',
                        'method' => 'get',
                        'url' => '/documents/upload/plagiarism',
                        'controller' => 'App\Http\Controllers\DocumentUploadController@plagiarismForm',
                        'name' => 'documents.upload.plagiarism',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'documents',
                        'method' => 'post',
                        'url' => '/documents/upload/repair',
                        'controller' => 'App\Http\Controllers\DocumentUploadController@repairSubmit',
                        'name' => 'documents.upload.repair.submit',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'documents',
                        'method' => 'post',
                        'url' => '/documents/upload/format',
                        'controller' => 'App\Http\Controllers\DocumentUploadController@formatSubmit',
                        'name' => 'documents.upload.format.submit',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'documents',
                        'method' => 'post',
                        'url' => '/documents/upload/plagiarism',
                        'controller' => 'App\Http\Controllers\DocumentUploadController@plagiarismSubmit',
                        'name' => 'documents.upload.plagiarism.submit',
                        'middleware' => '',
                    ]
                ]
            ],
            [
                'title' => 'Payment Routes',
                'item' => [
                    [
                        'type' => 'payment',
                        'method' => 'get',
                        'url' => '/payment/{orderNumber}',
                        'controller' => 'App\Http\Controllers\PaymentController@show',
                        'name' => 'payment.show',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'payment',
                        'method' => 'post',
                        'url' => '/payment/{orderNumber}/confirm',
                        'controller' => 'App\Http\Controllers\PaymentController@confirm',
                        'name' => 'payment.confirm',
                        'middleware' => '',
                    ]
                ]
            ]
        ];

        if (!empty($index)) {
            return !empty($data[$index]) ? $data[$index] : null;
        }
        return $data;
    }

    function getDataAdmin($index = null)
    {
        $data = [
            [
                'title' => 'Admin Authentication',
                'item' => [
                    [
                        'type' => 'auth',
                        'method' => 'get',
                        'url' => '/admin/login',
                        'controller' => 'App\Http\Controllers\Admin\AdminLoginController@showLoginForm',
                        'name' => 'admin.login',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'auth',
                        'method' => 'post',
                        'url' => '/admin/login',
                        'controller' => 'App\Http\Controllers\Admin\AdminLoginController@login',
                        'name' => 'admin.login.submit',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'auth',
                        'method' => 'post',
                        'url' => '/admin/logout',
                        'controller' => 'App\Http\Controllers\Admin\AdminLoginController@logout',
                        'name' => 'admin.logout',
                        'middleware' => '',
                    ]
                ]
            ],
            [
                'title' => 'Admin Dashboard',
                'item' => [
                    [
                        'type' => 'dashboard',
                        'method' => 'get',
                        'url' => '/admin/dashboard',
                        'controller' => 'App\Http\Controllers\Admin\DashboardController@index',
                        'name' => 'admin.dashboard',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'dashboard',
                        'method' => 'get',
                        'url' => '/admin/dashboard/stats',
                        'controller' => 'App\Http\Controllers\Admin\DashboardController@getStats',
                        'name' => 'admin.dashboard.stats',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'dashboard',
                        'method' => 'get',
                        'url' => '/admin/dashboard/chart',
                        'controller' => 'App\Http\Controllers\Admin\DashboardController@getChartData',
                        'name' => 'admin.dashboard.chart',
                        'middleware' => '',
                    ]
                ]
            ],
            [
                'title' => 'Product Management',
                'item' => [
                    [
                        'type' => 'products',
                        'method' => 'get',
                        'url' => '/admin/products',
                        'controller' => 'App\Http\Controllers\AdminProductController@index',
                        'name' => 'admin.products.index',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'products',
                        'method' => 'get',
                        'url' => '/admin/products/create',
                        'controller' => 'App\Http\Controllers\AdminProductController@create',
                        'name' => 'admin.products.create',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'products',
                        'method' => 'post',
                        'url' => '/admin/products',
                        'controller' => 'App\Http\Controllers\AdminProductController@store',
                        'name' => 'admin.products.store',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'products',
                        'method' => 'get',
                        'url' => '/admin/products/{id}',
                        'controller' => 'App\Http\Controllers\AdminProductController@show',
                        'name' => 'admin.products.show',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'products',
                        'method' => 'get',
                        'url' => '/admin/products/{id}/edit',
                        'controller' => 'App\Http\Controllers\AdminProductController@edit',
                        'name' => 'admin.products.edit',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'products',
                        'method' => 'put',
                        'url' => '/admin/products/{id}',
                        'controller' => 'App\Http\Controllers\AdminProductController@update',
                        'name' => 'admin.products.update',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'products',
                        'method' => 'delete',
                        'url' => '/admin/products/{id}',
                        'controller' => 'App\Http\Controllers\AdminProductController@destroy',
                        'name' => 'admin.products.destroy',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'products',
                        'method' => 'post',
                        'url' => '/admin/products/toggle-visibility',
                        'controller' => 'App\Http\Controllers\AdminProductController@toggleVisibility',
                        'name' => 'admin.products.toggle.visibility',
                        'middleware' => '',
                    ]
                ]
            ],
            [
                'title' => 'Orders Management',
                'item' => [
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders',
                        'controller' => 'App\Http\Controllers\Admin\OrderController@index',
                        'name' => 'admin.orders.index',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders/filter',
                        'controller' => 'App\Http\Controllers\Admin\OrderController@filter',
                        'name' => 'admin.orders.filter',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders/create',
                        'controller' => 'App\Http\Controllers\Admin\OrderController@create',
                        'name' => 'admin.orders.create',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'post',
                        'url' => '/admin/orders',
                        'controller' => 'App\Http\Controllers\Admin\OrderController@store',
                        'name' => 'admin.orders.store',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders/{id}',
                        'controller' => 'App\Http\Controllers\Admin\OrderController@show',
                        'name' => 'admin.orders.show',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders/{id}/edit',
                        'controller' => 'App\Http\Controllers\Admin\OrderController@edit',
                        'name' => 'admin.orders.edit',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'put',
                        'url' => '/admin/orders/{id}',
                        'controller' => 'App\Http\Controllers\Admin\OrderController@update',
                        'name' => 'admin.orders.update',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'delete',
                        'url' => '/admin/orders/{id}',
                        'controller' => 'App\Http\Controllers\Admin\OrderController@destroy',
                        'name' => 'admin.orders.destroy',
                        'middleware' => '',
                    ]
                ]
            ],
            [
                'title' => 'Settings Management',
                'item' => [
                    [
                        'type' => 'settings',
                        'method' => 'get',
                        'url' => '/admin/settings',
                        'controller' => 'App\Http\Controllers\Admin\SettingController@index',
                        'name' => 'admin.settings.index',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'settings',
                        'method' => 'post',
                        'url' => '/admin/settings/business',
                        'controller' => 'App\Http\Controllers\Admin\SettingController@updateBusiness',
                        'name' => 'admin.settings.business',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'settings',
                        'method' => 'put',
                        'url' => '/admin/settings/profile',
                        'controller' => 'App\Http\Controllers\Admin\SettingController@updateProfile',
                        'name' => 'admin.settings.profile.update',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'settings',
                        'method' => 'post',
                        'url' => '/admin/settings/password',
                        'controller' => 'App\Http\Controllers\Admin\SettingController@updatePassword',
                        'name' => 'admin.settings.password',
                        'middleware' => '',
                    ]
                ]
            ]
        ];

        if (!empty($index)) {
            return !empty($data[$index]) ? $data[$index] : null;
        }
        return $data;
    }

    function getIgnoreType($type = null)
    {
        $data = ['/', 'form', 'ajax', 'system'];
        if (!empty($type)) {
            if (!in_array($type, $data)) {
                return 1;
            }
            return 0;
        }
        return $data;
    }
}
