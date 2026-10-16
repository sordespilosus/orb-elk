<?php

namespace App\Http\Controllers\Auth;

use App\{AccessKey, AuthLog, Http\Traits\DataAuthTrait, User};
use App\Extensions\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\{LoginRequest, LoginWithTokenRequest};
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\{RedirectResponse, Request, Response};
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;
    use DataAuthTrait;

    protected $maxAttempts = 5;
    protected $decayMinutes = 5;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'beforeLogout']);
        $this->redirectTo = route('home');
    }

    public function username(): string
    {
        return 'login';
    }

    public function usernameToo(): string
    {
        return 'email';
    }

    /**
     * Show the application's login form.
     * @param Request $request
     * @return Factory|Application|RedirectResponse|View
     */
    public function showLoginForm(Request $request)
    {
        $routeParams = $this->getClientData($request);
        if (isset($routeParams['onlyEsia']) && $routeParams['onlyEsia']) {
            return redirect()->route('loginESIA');
        }

        return view('auth.login', array_merge(
            $this->getPageAuthData(),
            $routeParams,
            $this->getMethodsData($request)
        ));
    }

    /**
     * Handle a login request to the application.
     *
     * @param LoginRequest $request
     * @return Response
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): Response
    {
        $request->request->set($this->username(), strtolower($request->post($this->username())));
        $request->request->set($this->usernameToo(), $request->post($this->username()));

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request);
        }

        if ($this->hasUserNotActive($request)) {
            $this->sendNotActiveLoginResponse($request);
        }

        if ($this->hasUserBlocked($request)) {
            $this->sendBlockedLoginResponse($request);
        }

        if ($this->hasUserDeleted($request)) {
            $this->sendDeletedResponse($request);
        }

        if ($this->attemptLogin($request)) {
            $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);
        $this->sendFailedLoginResponse($request);
    }

    /**
     * Функция проверяет что пользователь не подтверждён
     * @param Request $request
     * @return boolean
     */
    protected function hasUserNotActive(Request $request): bool
    {
        $verified = DB::table('oauth.users')
            ->select('email_verified_at')
            ->where($this->username(), $request->post($this->username()))
            ->orWhere($this->usernameToo(), $request->post($this->usernameToo()))
            ->first();
        if ($verified != null && $verified->email_verified_at == null) {
            return true;
        }
        return false;
    }

    /**
     * Вызывает ошибку в случае, если пользователь не подтверждён
     * @param Request $request
     * @return void
     * @throws ValidationException
     */
    protected function sendNotActiveLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'not_activate' => [trans('auth.not_activate')],
        ]);
    }

    /**
     * Функция проверяет что пользователь удалён
     * @param Request $request
     * @return boolean
     */
    protected function hasUserDeleted(Request $request): bool
    {
        $deleted = DB::table('oauth.users')
            ->select('is_deleted')
            ->where($this->username(), $request->post($this->username()))
            ->orWhere($this->usernameToo(), $request->post($this->usernameToo()))
            ->first();
        if ($deleted != null && $deleted->is_deleted) {
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
        $blocked = DB::table('oauth.users')
            ->select('blocked')
            ->where($this->username(), $request->post($this->username()))
            ->orWhere($this->usernameToo(), $request->post($this->usernameToo()))
            ->first();
        if ($blocked != null && $blocked->blocked) {
            return true;
        }
        return false;
    }

    /**
     * Вызывает ошибку в случае, если пользователь удалён
     * @param Request $request
     * @return void
     * @throws ValidationException
     */
    protected function sendDeletedResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.deleted')],
        ]);
    }

    /**
     * Вызывает ошибку в случае, если пользователь заблокирован
     * @param Request $request
     * @return void
     * @throws ValidationException
     */
    protected function sendBlockedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.blocked')],
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param Request $request
     * @return bool
     */
    protected function attemptLogin(Request $request): bool
    {
        return $this->guard()->attempt($this->credentials($request), $request->filled('remember'))
            || $this->guard()->attempt($this->credentialsToo($request), $request->filled('remember'));
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param Request $request
     * @return array
     */
    protected function credentialsToo(Request $request): array
    {
        return $request->only($this->usernameToo(), 'password');
    }

    public function loginWithToken(LoginWithTokenRequest $request, int $id, string $token)
    {
        $accessKey = AccessKey::active()->byAccessKey($id, $token)->first();
        $user = User::find($id);

        if (!$accessKey || !$user || $user->blocked || $user->is_deleted || !$user->email_verified_at) {
            abort(404);
        }

        Auth::login($user);

        return redirect($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     *
     * @param Request $request
     * @param mixed $user
     * @return RedirectResponse
     */
    protected function authenticated(Request $request, $user): RedirectResponse
    {
        $user->setLatestAuthMethod('login');
        $authRequest = $request->session()->get('url.intended');
        $clientId = Helpers::getClientIdFromUrl($authRequest);
        AuthLog::create(['user_id' => $user->id, 'auth_method' => 'form', 'client_id' => $clientId]);
        Helpers::emmitEventToYM($user->id, 'login_password', time());
        return redirect()->intended($this->redirectPath());
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     * @return Redirector|RedirectResponse
     */
    public function beforeLogout(Request $request)
    {
        if ((Auth::check() && !Auth::user()->esia_user_id) || !env('ESIA_LOGOUT', true)) {
            return redirect('after_logout');
        } else {
            require_once __DIR__ . '/../Plugins/ESIAAuth/EsiaAuthService.php';

            if (config('app.env') === 'production') {
                $redirectUrl = route('after_logout');
            } else {
                $params = ['redirect_url' => route('after_logout')];
                $redirectUrl =
                    env('PROD_OLD_URL', 'http://permkrai.ru/cabinet')
                    . '/services/redirect/'
                    . env('APP_CONTOUR', 'cabinet-test')
                    . '?'
                    . http_build_query($params);
            }
            $esiaUrl = \EsiaAuthService::get()->getUrlLogout($redirectUrl);
            return redirect($esiaUrl);
        }
    }

    /**
     * The user has logged out of the application.
     *
     * @param Request $request
     * @return Application|Redirector|RedirectResponse
     */
    protected function logout(Request $request)
    {
        $logoutParams = $request->session()->pull('logoutParams');
        $request->session()->save();

        $this->guard()->logout();

        $request->session()->invalidate();

        if ($logoutParams && isset($logoutParams['redirect_uri'])) {
            if (isset($logoutParams['state'])) {
                $query = http_build_query(['state' => $logoutParams['state']]);
                $url = $logoutParams['redirect_uri']
                    . (strpos($logoutParams['redirect_uri'], '?') === false ? '?' : '&')
                    . $query;
            } else
                $url = $logoutParams['redirect_uri'];
            $res = redirect($url);
        } else
            $res = redirect('/');

        return $res;
    }
}
