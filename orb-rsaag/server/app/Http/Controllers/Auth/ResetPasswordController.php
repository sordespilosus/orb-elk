<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Support\Str;


class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->redirectTo = route('home');
    }

    /**
     * Reset the given user's password.
     *
     * @param Authenticatable $user
     * @param string $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);

        if ($user->getAttribute('is_old')) {
            $user->setAttribute('is_old', false);
        }
        $user->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));

        $this->guard()->login($user);
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param Request $request
     * @param string|null $token
     * @param string|null $email
     * @return View
     */
    public function showResetForm(Request $request, string $token = null, string $email = null): View
    {
        if (Auth::check()) {
            $email = Auth::user()->getAttribute('email');
        }
        $broker = $this->broker();
        $user = $broker->getUser(['email' => $email, 'token' => $token]);
        $errors = null;
        if ($user) {
            $isExist = $broker->tokenExists($user, $token);
            if (!$isExist) {
                $response = $broker::INVALID_TOKEN;
                $errors = ['email' => trans($response)];
            }
        } else {
            $response = $broker::INVALID_USER;
            $errors = ['email' => trans($response)];
        }

        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $email,
            'errorsEmail' => $errors
        ]);
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param Request $request
     * @return array
     */
    protected function credentials(Request $request): array
    {
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');
        if (isset($credentials['email'])) {
            $credentials['email'] = strtolower($credentials['email']);
        }
        return $credentials;
    }
}
