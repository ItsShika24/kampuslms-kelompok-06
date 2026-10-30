<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

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
        // Di Laravel 12, Event Discovery aktif secara otomatis.

        // MINGGU 11: Cegah N+1 Query secara global di lingkungan lokal/pengujian
        Model::preventLazyLoading(! app()->isProduction());
    }
}
