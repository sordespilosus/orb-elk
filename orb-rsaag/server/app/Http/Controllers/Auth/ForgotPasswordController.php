<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Password};
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Send a reset link to the given user.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function sendResetLinkEmailWithAuthUser(Request $request): array
    {
        $email = strtolower($request->get('email'));
        $user = Auth::user();
        if ($email != $user->getAttribute('email')) {
            return ['success' => false, 'errors' => ['email' => ['E-mail не совпадает с вашим ']]];
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(['email' => $email]);

        if ($response == Password::RESET_LINK_SENT) {
            return ['status' => true];
        }

        return ['status' => false, 'errors' => ['email' => ['Отправить сообщение на данный e-mail не удалось']]];
    }

    public function sendResetLinkEmailOauth($email): array
    {
        $response = $this->broker()->sendResetLink(['email' => $email]);

        if ($response == Password::RESET_LINK_SENT) {
            return ['status' => true];
        }

        return ['status' => false, 'errors' => ['Error sending']];
    }

    /**
     * Send a reset link to the given user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.

        if ($this->hasUserNotActive($request)) {
            $response = 'auth.not_activate';
        } elseif ($this->hasUserDeleted($request)) {
            $response = 'auth.deleted';
        } elseif ($this->hasUserBlocked($request)) {
            $response = 'auth.blocked';
        } else {
            $response = $this->broker()
                ->sendResetLink(['email' => strtolower($request->get('email'))]);
        }

        if (Password::RESET_LINK_SENT) {
            return $this->sendResetLinkResponse($request, $response);
        }

        return $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Функция проверяет что пользователь не подтверждён
     * @param Request $request
     * @return boolean
     */
    protected function hasUserNotActive(Request $request): bool
    {
        $user = User::where('email', strtolower($request->get('email')))->first();
        if ($user != null && $user->email_verified_at == null) {
            return true;
        }
        return false;
    }

    /**
     * Функция проверяет что пользователь удалён
     * @param Request $request
     * @return boolean
     */
    protected function hasUserDeleted(Request $request): bool
    {
        $user = User::where('email', strtolower($request->get('email')))->first();
        if ($user != null && $user->is_deleted == true) {
            return true;
        }
        return false;
    }

    /**
     * Функция проверяет что пользователь заблокирован
     * @param Request $request
     * @return boolean
     */
    protected function hasUserBlocked(Request $request): bool
    {
        $user = User::where('email', strtolower($request->get('email')))->first();
        if ($user != null && $user->blocked == true) {
            return true;
        }
        return false;
    }
}
