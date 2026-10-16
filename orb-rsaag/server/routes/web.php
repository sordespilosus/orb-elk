<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

use App\Http\Controllers\{ClientsController,
    HomeController,
    NotificationsController,
    RsaagAuthorizationController,
    UserController
};
use App\Http\Controllers\Auth\{ForgotPasswordController,
    LoginController,
    RegisterController,
    ResetPasswordController,
    UnsubscribeController
};
use Illuminate\Support\Facades\Route;

Route::get('services/redirect/{contour}', [HomeController::class, 'redirectTo'])->name('redirect_to');

Route::get('csrf_token', function () {
    return ['csrf_token' => csrf_token()];
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'beforeLogout'])->name('logout');
Route::get('logout', [LoginController::class, 'beforeLogout'])->name('logout_get');
Route::get('after_logout', [LoginController::class, 'logout'])->name('after_logout');

Route::group(['prefix' => 'register'], function () {
    Route::get('', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('', [RegisterController::class, 'register']);
    Route::post('send_sms', [UserController::class, 'sendSmsRegister'])->middleware('guest');
    Route::post('confirm_sms', [UserController::class, 'confirmSmsRegister'])->middleware('guest');
    Route::get('verify_phone', [RegisterController::class, 'showVerifyPhoneForm'])->name('verify_phone');

    Route::get('new_social/{method}', [UserController::class, 'questionForSocialUser'])->name('question_for_social_user');

    Route::post('new_social', [UserController::class, 'insertNewSocialUser'])->name('new_social_user');
    Route::get('add_to_user', [UserController::class, 'addSocialToUser'])->middleware('auth')->name('add_to_user');
});

Route::group(['prefix' => 'password'], function () {
    Route::get('reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request')->middleware('guest');
    Route::post('email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('guest');

    Route::get('reset/{token}/{email}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::group(['prefix' => 'mailing'], function () {
    Route::get('unsubscribe/{hash}/{email}', [UnsubscribeController::class, 'showUnsubscribedForm'])->name('mailing.unsubscribe_form');
    Route::post('unsubscribed', [UnsubscribeController::class, 'unsubscribed'])->name('mailing.unsubscribed');
});

Route::get('', [HomeController::class, 'index'])->name('home')->middleware('auth');
Route::get('notices', [HomeController::class, 'index'])->name('home_notices')->middleware('auth');
Route::post('notices', [HomeController::class, 'getUserNotices'])->middleware('auth');
Route::post('clients', [HomeController::class, 'getClientsAjax'])->middleware('auth');

Route::get('agreement', [HomeController::class, 'agreement'])->name('agreement');
Route::get('get_client_agreement', [HomeController::class, 'getClientAgreement']);

Route::group(['prefix' => 'user', 'middleware' => ['guest']], function () {
    Route::get('activate', [UserController::class, 'confirmRegistration'])->name('activate');
    Route::post('activate_by_code', [UserController::class, 'confirmRegistrationByCode']);
    Route::get('activate_again', [UserController::class, 'sendEmailActivationAgain'])->name('activate_again');
});

Route::get('end_registation', [UserController::class, 'endRegistation'])->name('end_registation')->middleware('guest');

Route::get('login_from_vk', [UserController::class, 'registrationFromVK'])->name('login_from_vk');
Route::get('loginVK', [UserController::class, 'loginVK'])->name('loginVK');
Route::get('login_from_fb', [UserController::class, 'registrationFromFB'])->name('login_from_fb');
Route::get('loginFB', [UserController::class, 'loginFB'])->name('loginFB');

Route::get('loginESIA', [UserController::class, 'loginESIA'])->name('loginESIA');

Route::post('change_email', [UserController::class, 'changeEmail'])->name('change_email');
Route::get('activate_new_email', [UserController::class, 'activateNewEmail'])->name('activate_new_email');

Route::group(['middleware' => ['auth'], 'prefix' => 'profile'], function () {
    Route::get('', [UserController::class, 'getProfile'])->name('profile');
    Route::post('', [UserController::class, 'updateProfile'])->name('profile_update');
    Route::post("send_image", [UserController::class, 'sendImage'])->name('image');
    Route::post('password_change', [UserController::class, 'passwordChange'])->name('password_change');
    Route::post('password_reset', [ForgotPasswordController::class, 'sendResetLinkEmailWithAuthUser'])->name('password.emailWithAuthUser');

    Route::get('remove_vk', [UserController::class, 'vkRemove']);
    Route::get('remove_fb', [UserController::class, 'fbRemove']);
    Route::get('remove_esia', [UserController::class, 'esiaRemove']);

    Route::post('delete', [UserController::class, 'deleteProfile'])->name('profile_delete');

    Route::post('send_sms', [UserController::class, 'sendSmsProfile']);
    Route::post('confirm_sms', [UserController::class, 'confirmSmsProfile']);

    Route::get('bind_inn', [UserController::class, 'bindINN']);
    Route::get('remove_org_user', [UserController::class, 'removeOrgUser']);

    Route::post('get_access_token', [UserController::class, 'getAccessToken'])->name('get_access_token');
});

Route::get('oauth/authorize_mobile_with_social/{social}', [RsaagAuthorizationController::class, 'authorizeMobileWithSocial'])
    ->middleware(['checkScopes', 'web']);

Route::group(['middleware' => ['needEsia', 'auth'], 'prefix' => 'my_notifications'], function ($router) {
    $router->get('', [NotificationsController::class, 'getNotificationsPage'])->middleware('auth')->name('my_notifications_page');
    $router->post('', [NotificationsController::class, 'getNotifications'])->middleware('auth')->name('my_notifications');
    $router->post('{id}', [NotificationsController::class, 'getNotification'])->middleware('auth')->name('my_notification');
    $router->get('print/{id}', [NotificationsController::class, 'getNotificationPrint'])->middleware('auth')->name('notification_print');
    $router->get('file/{file_id}', [NotificationsController::class, 'getNotificationFile'])->middleware('auth')->name('notification_file');
});

Route::get('login/{id}/{token}', [LoginController::class, 'loginWithToken'])->name('login_with_token');

Route::group(['middleware' => ['auth', 'needRole:trusted_user'], 'prefix' => 'my_clients'], function ($router) {
    $router->get('', [ClientsController::class, 'getPage'])->name('my_clients_page');
    $router->post('', [ClientsController::class, 'getUsersClient']);
    $router->post('resend', [ClientsController::class, 'resendClient']);
    $router->post('edit', [ClientsController::class, 'setClientChanges']);
    $router->post('delete', [ClientsController::class, 'delClient']);
    $router->post('cancel', [ClientsController::class, 'cancelChanges']);
});
