<?php

namespace App\Http\Controllers\Auth;

use App\Extensions\Helpers;
use App\User;
use App\Http\Controllers\{Controller, UserController};
use App\Http\Traits\DataAuthTrait;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\{Auth, DB, Validator};

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;
    use DataAuthTrait;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/end_registation';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->redirectTo = route('end_registation');
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     * @throws GuzzleException
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $verified = DB::table('oauth.users')
            ->select('email_verified_at', 'blocked')
            ->where('email', $data['email'])
            ->first();

        if ($verified) {
            $data['email_verified_at'] = $verified->email_verified_at;
            $data['blocked'] = $verified->blocked;
        } else {
            $data['email_verified_at'] = 'testtest';
            $data['blocked'] = false;
        }

        if (env('SMS_SECURITY', true)) {
            if (isset($data['phone'])
                && !Helpers::verifySmsHash($data['phone'], $data['sms_hash'], 'registration')
            ) {
                $data['sms_hash'] = null;
            } else {
                $data['sms_hash'] = '90be854aa5e1f11d02763c9a5da0a047';
            }
        } else {
            $data['sms_hash'] = '90be854aa5e1f11d02763c9a5da0a047';
        }

        if (env('RECAPTCHA_V', 3) == 2
            && !Helpers::checkReCaptchaV2($data['g-recaptcha-response'])
        ) {
            $data['g-recaptcha-response'] = null;
        }
        return UserController::validatorByCreateUser($data);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return User
     */
    protected function create(array $data): User
    {
        return UserController::insertNewUser($data);
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return StatefulGuard
     */
    protected function guard(): StatefulGuard
    {
        return Auth::guard();
    }

    /**
     * The user has been registered.
     *
     * @param Request $request
     * @param mixed $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        //
    }

    /**
     * Handle a registration request for the application.
     *
     * @param Request $request
     * @param SessionStore $session
     * @return array
     * @throws GuzzleException
     */
    public function register(Request $request, SessionStore $session): array
    {
        $data = $request->all();
        $old_data = $request->session()->pull('registerRedirect');
        if (isset($old_data)) {
            $data = array_merge($old_data, $data);
            $request->session()->save();
        }
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }
        $validator = $this->validator($data);
        if ($validator->fails()) {
            $messages = $validator->errors()->messages();
            $res = ['status' => false, 'errors' => $messages, 'not_verified_email' => null, 'has_user' => null];
            if (isset($messages['email'], $data['email'])) {
                $emailValidator = Validator::make(['email' => $data['email']], ['email' => ['required', 'email', 'max:255']]);
                if (!$emailValidator->fails()) {
                    $verified = DB::table('oauth.users')->select('email_verified_at', 'blocked', 'is_deleted')->where('email', $data['email'])->get()->first();
                    if ($verified != null) {
                        if ($verified->email_verified_at == null) {
                            $res['not_verified_email'] = $data['email'];
                        } else if ($verified->blocked === true) {
                            $res['errors']['email'] = ['Пользователь с таким email заблокирован'];
                        } else if ($verified->is_deleted === true) {
                            $res['errors']['email'] = ['Пользователь с таким email удалён'];
                        } else {
                            $res['has_user'] = $data['email'];
                        }
                    }
                }
            }
            return $res;
        }

        if (isset($data['phone']) && !env('SMS_SECURITY', true)) {
            $userPhones = DB::table('oauth.users')
                ->select('email_verified_at', 'blocked', 'is_deleted', 'id')
                ->where('phone', $data['phone'])
                ->get()->all();
            if (count($userPhones)) {
                $isAllowed = true;
                foreach ($userPhones as $userPhone) {
                    if ($userPhone->email_verified_at && !$userPhone->blocked && !$userPhone->is_deleted) {
                        $isAllowed = false;
                    }
                }
                if (!$isAllowed) {
                    return [
                        'status' => false,
                        'errors' => ['phone' => ['Пользователь с данным телефоном уже зарегистрирован']]
                    ];
                }
            }
        }

        if (!$data['phone']) {
            if (env('RECAPTCHA_V', 3) == 3 && !Helpers::checkReCaptchaV3($data['g-recaptcha-response'])) {
                $request->session()->put('registerRedirect', $data);
                $request->session()->save();
                return ['status' => false, 'errors' => ['phone' => ['Необходимо привязать телефон']], 'need_phone' => true];
            }
        }
        if (isset($data['phone']) && env('SMS_SECURITY', true)) {
            Helpers::resetSmsCount($data['phone'], 'registration');
        }
        event(new Registered($user = $this->create($data)));
        return ['status' => true];
    }

    /**
     * Show the application registration form.
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function showRegistrationForm(Request $request)
    {
        $routeParams = $this->getClientData($request);
        if (isset($routeParams['onlyEsia']) && $routeParams['onlyEsia']) {
            return redirect()->route('loginESIA');
        }
        $registerData = $request->session()->pull('registerData');
        return view('auth.login', array_merge(
            ['registerData' => $registerData],
            $this->getPageAuthData(),
            $routeParams,
            $this->getMethodsData($request)
        ));
    }

    /**
     * Show the application verify phone form.
     * @param Request $request
     * @param SessionStore $session
     * @return View
     */
    public function showVerifyPhoneForm(Request $request, SessionStore $session): View
    {
        return view('auth.verify_phone');
    }
}
