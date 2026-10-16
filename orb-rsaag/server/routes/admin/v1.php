<?php

/*
|--------------------------------------------------------------------------
| Admin Web Routes
|--------------------------------------------------------------------------
|
| Роуты для админки!
| Они регистрируются в RouteServiceProvider, и используют middleware web
|
 */

use App\Http\Controllers\{AdminController, AdminExportController};
use Illuminate\Support\Facades\Route;

Route::get('get_agreement', [AdminController::class, 'getAgreement']);

Route::group(['middleware' => ['auth', 'needRole:admin'], 'prefix' => 'admin'], function () {
    //TODO переопределить или закрыть роуты клиентов
    Route::get('', [AdminController::class, 'constructAdminPanel'])->name('admin');

    //@todo: добавил страницы vue-router. Если неправильно -- поправить
    Route::prefix('page')->group(function () {
        Route::get('users', [AdminController::class, 'constructAdminPanel'])->name('page_users');
        Route::get('roles', [AdminController::class, 'constructAdminPanel'])->name('page_roles');
        Route::get('clients', [AdminController::class, 'constructAdminPanel'])->name('page_clients');
        Route::get('clients_moderation', [AdminController::class, 'constructAdminPanel'])->name('page_clients_moderation');
        Route::get('logs', [AdminController::class, 'constructAdminPanel'])->name('page_logs');
        Route::get('logs_users', [AdminController::class, 'constructAdminPanel'])->name('page_logs_users');
        Route::get('user_agreement', [AdminController::class, 'constructAdminPanel'])->name('page_user_agreement');
        Route::get('system_description', [AdminController::class, 'constructAdminPanel']);
        Route::get('mailing_module', [AdminController::class, 'constructAdminPanel'])->name('page_mailing');
    });

    Route::prefix('api')->group(function () {
        //admin api

        Route::prefix('users')->group(function () {
            Route::get('', [AdminController::class, 'getUsers']);
            Route::get('export', [AdminExportController::class, 'exportUsers']);
            Route::post('edit', [AdminController::class, 'updateUser']);
            Route::post('blocking', [AdminController::class, 'setUserBlocking']);
            Route::get('{id}', [AdminController::class, 'getUser']);
            Route::get('{id}/roles', [AdminController::class, 'getUserRoles']);
            Route::post('{id}/roles', [AdminController::class, 'changeUserRole']);
            Route::post('{id}/roles/set', [AdminController::class, 'setUserRole']);
            Route::post('{id}/roles/unset', [AdminController::class, 'unsetUserRole']);
            Route::post('{id}/restore', [AdminController::class, 'restoreUser']);
        });

        Route::prefix('mailing')->group(function () {
            Route::get('get_queue', [AdminController::class, 'getQueue']);
            Route::get('get_unsubscribed', [AdminController::class, 'getUnsubscribed']);
            Route::post('mailing_module', [AdminController::class, 'sendMailing']);
            Route::post('unsubscribed_user', [AdminController::class, 'unsubscribedUser']);
            Route::post('update_unsubscribed', [AdminController::class, 'updateUnsubscribed']);
        });

        Route::prefix('roles')->group(function () {
            Route::get('', [AdminController::class, 'getRoles']);
            Route::post('new', [AdminController::class, 'setNewRole']);
            Route::post('edit', [AdminController::class, 'setEditRole']);
            Route::post('delete', [AdminController::class, 'setDeleteRole']);
        });

        Route::prefix('clients')->group(function () {
            Route::get('', [AdminController::class, 'getClients']);
            Route::post('new', [AdminController::class, 'addClient']);
            Route::post('edit', [AdminController::class, 'updateClient']);
            Route::post('delete', [AdminController::class, 'delClient']);
        });

        Route::prefix('clients_moderation')->group(function () {
            Route::get('', [AdminController::class, 'getPageClientsModeration']);
            Route::post('set_status', [AdminController::class, 'setStatusClients']);
        });

        Route::prefix('clients_changes')->group(function () {
            //Route::get('', 'AdminController@getClientsChangesModeration');
            Route::post('set_status', [AdminController::class, 'setChangesStatus']);
        });

        Route::get('logs', [AdminController::class, 'getLogs']);
        Route::get('export_logs', [AdminExportController::class, 'exportLogs']);

        Route::get('get_agreement', [AdminController::class, 'getAgreement']);
        Route::post('set_agreement', [AdminController::class, 'setAgreement']);

        Route::get('get_description', [AdminController::class, 'getSystemDescription']);
        Route::post('set_description', [AdminController::class, 'setSystemDescription']);
    });

    Route::get('login_like', [AdminController::class, 'loginLikeUser'])->name('login_like_user');
});
