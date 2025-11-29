<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\VerifySupabaseJwt;
use App\Http\Middleware\RoleMiddleware;

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
        if ($this->app->bound('router')) {
            $this->app['router']->aliasMiddleware('supabase.jwt', VerifySupabaseJwt::class);
            $this->app['router']->aliasMiddleware('role', RoleMiddleware::class);
        }
    }
}
