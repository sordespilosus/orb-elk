<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{

    protected $throttle = 'throttle:60,1';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.env') == 'local') {
            resolve(\Illuminate\Routing\UrlGenerator::class)->forceScheme('https');
        }
        $this->throttle = 'throttle:' . env('API_THROTTLE_COUNT_IN_MINUTE', 60) . ',' . env('API_THROTTLE_TIMEOUT_MINUTES', 1);
        parent::boot();

    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
        $this->mapAdminWebRoutes();

        Route::post('/oauth/token', [
            'uses' => '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken',
            'middleware' => $this->throttle,
        ]);
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * "web" роуты для админки
     *
     * То же самое, что mapWebRoutes, только для админки
     *
     * @return void
     */
    protected function mapAdminWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/admin/v1.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['api', $this->throttle])
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }
}
