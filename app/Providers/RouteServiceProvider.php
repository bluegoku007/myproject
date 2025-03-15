<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->routes(function () {
            Route::prefix('api') // <-- Ajoute le préfixe 'api'
                ->middleware('api')
                ->group(base_path('routes/api.php')); // <-- Charge bien routes/api.php

            Route::middleware('web')
                ->group(base_path('routes/web.php')); // <-- Charge routes/web.php
        });
    }
}
