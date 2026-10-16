<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use L5Swagger\L5SwaggerServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(L5SwaggerServiceProvider::class);
        if (in_array(config('app.env'), ['production', 'preproduction', 'developer'])) {
            $this->app['request']->server->set('HTTPS', true);
        }
    }
}
