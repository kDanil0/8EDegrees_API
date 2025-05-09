<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\CorsMiddleware;

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
        // CORS is handled by Laravel's built-in CORS middleware
        // See config/cors.php for configuration
        
        // Register middleware aliases
        Route::aliasMiddleware('role', RoleMiddleware::class);
        Route::aliasMiddleware('cors', CorsMiddleware::class);
        
        // Apply CORS middleware globally
        app('Illuminate\Contracts\Http\Kernel')->prependMiddleware(CorsMiddleware::class);
    }
}
