<?php

namespace App\Http\Controllers\Auth;

use App\{AccessKey, AuthLog, User};
use App\Extensions\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Traits\DataAuthTrait;
use EsiaAuthService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Routing\Redirector;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, DB, Validator};
use Illuminate\Validation\ValidationException;

class ServiceLoginController extends Controller
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
     * @OA\Post(path="/api/v2/service_login", tags={"API(V2)"}, summary="Сервисный вход для администраторов",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(required={"login", "password", "_token"},
     *                 @OA\Property(property="login", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="_token", type="string"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Отсутствует обязательный параметр или неправильный пароль",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="login", type="array",
     *                     @OA\Items(type="string", example="Логин должен быть обязательно заполнен")
     *                 ),
     *                 @OA\Property(property="password", type="array",
     *                     @OA\Items(type="string", example="Введите пароль")
     *                 ),
     *                 @OA\Property(property="invalidCredentials", type="array",
     *                     @OA\Items(type="string", example="Неправильный пароль")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Пользователь заблокирован",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="userBlocked", type="array",
     *                     @OA\Items(type="string", example="Пользователь заблокирован")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Пользователь не найден, не активирован или удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="userNotActive", type="array",
     *                     @OA\Items(type="string", example="Пользователь не активирован")
     *                 ),
     *                 @OA\Property(property="userNotExists", type="array",
     *                     @OA\Items(type="string", example="Пользователь не существует")
     *                 ),
     *                 @OA\Property(property="userDeleted", type="array",
     *                     @OA\Items(type="string", example="Пользователь удален")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный вход",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean")
     *         )
     *     )
     * )
     * Handle a login request to the application.
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function login(Request $request): JsonResponse
    {
        $request->request->set($this->username(), strtolower($request->post($this->username())));
        $request->request->set($this->usernameToo(), $request->post($this->username()));
        $login = $request->post('login');
        $password = $request->post('password');

        $validator = Validator::make(
            [
                'login' => $login,
                'password' => $password
            ],
            [
                'login' => 'required|string',
                'password' => 'required|string'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        if ($this->hasUserNotExists($request)) {
            return response()->json(['errors' => ['userNotExists' => ['Пользователя не существует']]], 404);
        }

        if ($this->hasUserNotActive($request)) {
            return response()->json(['errors' => ['userNotActive' => ['Пользователь не активирован']]], 404);
        }

        if ($this->hasUserBlocked($request)) {
            return response()->json(['errors' => ['userBlocked' => ['Пользователь заблокирован']]], 401);
        }

        if ($this->hasUserDeleted($request)) {
            return response()->json(['errors' => ['userDeleted' => ['Пользователь удален']]], 404);
        }

        if ($this->attemptLogin($request)) {
            return response()->json(['status' => true]);
        }
        return response()->json(['errors' => ['invalidCredentials' => ['Неправильный пароль']]], 400);
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

    protected function hasUserNotExists(Request $request): bool
    {
        $verified = DB::table('oauth.users')
            ->where($this->username(), $request->post($this->username()))
            ->orWhere($this->usernameToo(), $request->post($this->usernameToo()))
            ->first();
        if (!$verified) {
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
        if ($deleted != null && $deleted->is_deleted == true) {
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
        if ($blocked != null && $blocked->blocked == true) {
            return true;
        }
        return false;
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

    public function loginWithToken(Request $request, int $id, string $token)
    {
        $validator = Validator::make([
            'id' => $id,
            'token' => $token
        ], [
            'id' => 'required|integer',
            'token' => 'required|string|max:32'
        ]);

        if ($validator->fails()) {
            abort(404);
        }

        $accessKey = AccessKey::active()->byAccessKey($id, $token)->first();
        if (!$accessKey) {
            abort(404);
        }

        $user = User::find($id);
        if ($user->blocked || $user->is_deleted || !$user->email_verified_at) {
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
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
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
                $params = [
                    'redirect_url' => route('after_logout')
                ];
                $redirectUrl =
                    env('PROD_OLD_URL', 'http://permkrai.ru/cabinet')
                    . '/services/redirect/'
                    . env('APP_CONTOUR', 'cabinet-test')
                    . '?'
                    . http_build_query($params);
            }
            $esiaUrl = EsiaAuthService::get()->getUrlLogout($redirectUrl);
            return redirect($esiaUrl);
        }
    }

    /**
     * The user has logged out of the application.
     *
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
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
