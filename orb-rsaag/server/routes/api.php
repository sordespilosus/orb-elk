<?php

use App\Http\Controllers\{Auth\ServiceLoginController,
    CityController,
    ClientsController,
    OauthController,
    ProfileController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('getProfileByPhone', [ProfileController::class, 'getProfileByPhone'])->middleware('bot');
Route::post('getProfileByToken', [ProfileController::class, 'getProfileByToken']);

Route::get('get_user', [OauthController::class, 'getUser'])->middleware('auth:api');
Route::get('cities', [CityController::class, 'list']);

Route::post('get_user_for_mobile', [OauthController::class, 'getUserForMobile']);
Route::post('get_registration_data_for_mobile', [OauthController::class, 'getRegistrationDataForMobile']);
Route::post('send_sms', [OauthController::class, 'sendSmsRegister']);
Route::post('confirm_sms', [OauthController::class, 'confirmSmsRegister']);
Route::post('registration_for_mobile', [OauthController::class, 'registrationForMobile']);
Route::post('get_social_id/{social}', [OauthController::class, 'getIdSocial']);

Route::post('reset_password_for_mobile', [OauthController::class, 'resetPasswordForMobile']);

Route::post('set_user_card', [OauthController::class, 'setUserCard']);
Route::post('unset_user_card', [OauthController::class, 'unsetUserCard']);
Route::post('get_user_card', [OauthController::class, 'getUserCard']);

Route::post('add_user_notices', [OauthController::class, 'addUserNotices']);

Route::group(['middleware' => ['web'], 'prefix' => 'v2'], function () {
    Route::post('delete_profile', [ProfileController::class, 'deleteProfile']);
    Route::get('profile', [ProfileController::class, 'getProfile']);
    Route::get('cities', [ProfileController::class, 'getCities']);
    Route::post('service_login', [ServiceLoginController::class, 'login']);
    Route::get('clients', [ClientsController::class, 'getClients']);
    Route::get('agreement', [ClientsController::class, 'getAgreement']);
    Route::get('system-description', [ClientsController::class, 'getSystemDescription']);
    Route::get('client/{id}', [ClientsController::class, 'getClient']);
    Route::get('client/{id}/agreement', [ClientsController::class, 'getClientAgreement']);
    Route::get('client/{id}/revoke', [ClientsController::class, 'revokeAccess']);
});

Route::get('v2/loginESIA', [ProfileController::class, 'loginESIA'])->name('loginESIAV2');
