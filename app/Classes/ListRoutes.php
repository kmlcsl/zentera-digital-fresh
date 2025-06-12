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
            ],
            [
                'title' => 'WhatsApp Testing Routes',
                'item' => [
                    [
                        'type' => 'testing',
                        'method' => 'get',
                        'url' => '/test-wa',
                        'controller' => 'App\Http\Controllers\TestController@testWhatsApp',
                        'name' => 'test.whatsapp',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'testing',
                        'method' => 'get',
                        'url' => '/test-wa-order',
                        'controller' => 'App\Http\Controllers\TestController@testWhatsAppOrder',
                        'name' => 'test.whatsapp.order',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'testing',
                        'method' => 'get',
                        'url' => '/test-wa-payment',
                        'controller' => 'App\Http\Controllers\TestController@testWhatsAppPayment',
                        'name' => 'test.whatsapp.payment',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'testing',
                        'method' => 'get',
                        'url' => '/test-wa-completion',
                        'controller' => 'App\Http\Controllers\TestController@testWhatsAppCompletion',
                        'name' => 'test.whatsapp.completion',
                        'middleware' => '',
                    ]
                ]
            ],
            [
                'title' => 'WhatsApp Webhook Routes',
                'item' => [
                    [
                        'type' => 'webhook',
                        'method' => 'post',
                        'url' => '/webhook/whatsapp',
                        'controller' => 'App\Http\Controllers\WhatsAppWebhookController@handleIncoming',
                        'name' => 'webhook.whatsapp.post',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'webhook',
                        'method' => 'get',
                        'url' => '/webhook/whatsapp',
                        'controller' => 'App\Http\Controllers\WhatsAppWebhookController@handleIncoming',
                        'name' => 'webhook.whatsapp.get',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'webhook',
                        'method' => 'get',
                        'url' => '/webhook/whatsapp/test',
                        'controller' => 'App\Http\Controllers\WhatsAppWebhookController@testWebhook',
                        'name' => 'webhook.whatsapp.test',
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
                        'controller' => 'App\Http\Controllers\AdminLoginController@showLoginForm',
                        'name' => 'admin.login',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'auth',
                        'method' => 'post',
                        'url' => '/admin/login',
                        'controller' => 'App\Http\Controllers\AdminLoginController@login',
                        'name' => 'admin.login.submit',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'auth',
                        'method' => 'post',
                        'url' => '/admin/logout',
                        'controller' => 'App\Http\Controllers\AdminLoginController@logout',
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
                        'controller' => 'App\Http\Controllers\AdminDashboardController@index',
                        'name' => 'admin.dashboard',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'dashboard',
                        'method' => 'get',
                        'url' => '/admin/dashboard/stats',
                        'controller' => 'App\Http\Controllers\AdminDashboardController@getStats',
                        'name' => 'admin.dashboard.stats',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'dashboard',
                        'method' => 'get',
                        'url' => '/admin/dashboard/chart',
                        'controller' => 'App\Http\Controllers\AdminDashboardController@getChartData',
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
                        'controller' => 'App\Http\Controllers\AdminOrderController@index',
                        'name' => 'admin.orders.index',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders/filter',
                        'controller' => 'App\Http\Controllers\AdminOrderController@filter',
                        'name' => 'admin.orders.filter',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders/create',
                        'controller' => 'App\Http\Controllers\AdminOrderController@create',
                        'name' => 'admin.orders.create',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'post',
                        'url' => '/admin/orders',
                        'controller' => 'App\Http\Controllers\AdminOrderController@store',
                        'name' => 'admin.orders.store',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders/{id}',
                        'controller' => 'App\Http\Controllers\AdminOrderController@show',
                        'name' => 'admin.orders.show',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'get',
                        'url' => '/admin/orders/{id}/edit',
                        'controller' => 'App\Http\Controllers\AdminOrderController@edit',
                        'name' => 'admin.orders.edit',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'put',
                        'url' => '/admin/orders/{id}',
                        'controller' => 'App\Http\Controllers\AdminOrderController@update',
                        'name' => 'admin.orders.update',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'delete',
                        'url' => '/admin/orders/{id}',
                        'controller' => 'App\Http\Controllers\AdminOrderController@destroy',
                        'name' => 'admin.orders.destroy',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'post',
                        'url' => '/admin/orders/{id}/notify-payment',
                        'controller' => 'App\Http\Controllers\AdminOrderController@notifyPayment',
                        'name' => 'admin.orders.notify.payment',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'post',
                        'url' => '/admin/orders/{id}/notify-completion',
                        'controller' => 'App\Http\Controllers\AdminOrderController@notifyCompletion',
                        'name' => 'admin.orders.notify.completion',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'orders',
                        'method' => 'post',
                        'url' => '/admin/orders/{id}/send-whatsapp',
                        'controller' => 'App\Http\Controllers\AdminOrderController@sendWhatsApp',
                        'name' => 'admin.orders.send.whatsapp',
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
                        'controller' => 'App\Http\Controllers\AdminSettingController@index',
                        'name' => 'admin.settings.index',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'settings',
                        'method' => 'post',
                        'url' => '/admin/settings/business',
                        'controller' => 'App\Http\Controllers\AdminSettingController@updateBusiness',
                        'name' => 'admin.settings.business',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'settings',
                        'method' => 'put',
                        'url' => '/admin/settings/profile',
                        'controller' => 'App\Http\Controllers\AdminSettingController@updateProfile',
                        'name' => 'admin.settings.profile.update',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'settings',
                        'method' => 'post',
                        'url' => '/admin/settings/password',
                        'controller' => 'App\Http\Controllers\AdminSettingController@updatePassword',
                        'name' => 'admin.settings.password',
                        'middleware' => '',
                    ]
                ]
            ],
            [
                'title' => 'WhatsApp Management',
                'item' => [
                    [
                        'type' => 'whatsapp',
                        'method' => 'get',
                        'url' => '/admin/whatsapp',
                        'controller' => 'App\Http\Controllers\AdminWhatsAppController@index',
                        'name' => 'admin.whatsapp.index',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'whatsapp',
                        'method' => 'post',
                        'url' => '/admin/whatsapp/test',
                        'controller' => 'App\Http\Controllers\AdminWhatsAppController@test',
                        'name' => 'admin.whatsapp.test',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'whatsapp',
                        'method' => 'post',
                        'url' => '/admin/whatsapp/broadcast',
                        'controller' => 'App\Http\Controllers\AdminWhatsAppController@broadcast',
                        'name' => 'admin.whatsapp.broadcast',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'whatsapp',
                        'method' => 'get',
                        'url' => '/admin/whatsapp/templates',
                        'controller' => 'App\Http\Controllers\AdminWhatsAppController@templates',
                        'name' => 'admin.whatsapp.templates',
                        'middleware' => '',
                    ],
                    [
                        'type' => 'whatsapp',
                        'method' => 'post',
                        'url' => '/admin/whatsapp/templates',
                        'controller' => 'App\Http\Controllers\AdminWhatsAppController@saveTemplate',
                        'name' => 'admin.whatsapp.templates.save',
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
        $data = ['/', 'form', 'ajax', 'system', 'testing', 'webhook'];
        if (!empty($type)) {
            if (!in_array($type, $data)) {
                return 1;
            }
            return 0;
        }
        return $data;
    }
}
