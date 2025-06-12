<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS for Vercel/production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');

            // Ensure upload directories exist in production
            $uploadDirs = [
                '/tmp/uploads',
                '/tmp/uploads/documents',
                '/tmp/uploads/payment_proofs',
                '/tmp/cache'
            ];

            foreach ($uploadDirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
        }

        // Set default string length for older MySQL versions
        Schema::defaultStringLength(191);

        // Extend CSRF token lifetime for forms
        config(['session.lifetime' => 120]);

        // Trust proxy headers for Vercel
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
        }

        // Custom asset path untuk Vercel
        app('url')->macro('asset', function ($path, $secure = null) {
            return app('url')->to($path, [], $secure);
        });
    }
}
