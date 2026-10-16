<?php

namespace App\Providers;

use App\Extensions\RsaagUserProvider;
use App\Http\Controllers\Auth\AuthorizationController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('rsaag', function ($app, array $config) {
            return new RsaagUserProvider($this->app['hash'], $config['model']);
        });

        Passport::routes();

        Route::get('oauth/authorize', [AuthorizationController::class, 'authorize'])
            ->middleware(['web', 'auth']);

        Passport::tokensExpireIn(Carbon::now()->addDays(env('TOKEN_LIFETIME_DAYS', 14))); //Время жизни токена доступа

        Passport::refreshTokensExpireIn(Carbon::now()->addDays(env('REFRESH_TOKEN_LIFETIME_DAYS', 14))); //Время жизни токена обновления

        Passport::tokensCan([
            'rsaag_id' => 'Доступ к открытой сессии пользователя (ID пользователя)',
            'personal_data' => 'Доступ к персональной информации (ФИО, дате рождения, городу проживания, логину)',
            'esia_data' => 'Доступ к персональной информации из Госуслуг',
            'email' => 'Доступ к E-mail',
            'phone' => 'Доступ к номеру телефона',
            //'roles_user' => 'Доступ ролям пользователя',
            'esia_user_id' => 'Доступ к ID пользователя в Госуслугах',
            'fb_user_id' => 'Доступ к ID пользователя в Facebook',
            'vk_user_id' => 'Доступ к ID пользователя в ВК',
            'passport' => 'Доступ к паспортным данным',
            'esia_address' => 'Доступ к адресу пользователя',
            'snils' => 'Доступ к номеру СНИЛС',
            'organizations_user' => 'Доступ организациям пользователя',
            'auth_method' => 'Доступ к методу авторизации'
        ]);
    }
}
