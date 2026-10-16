<?php

namespace App\Http\Controllers;


use App\{AuthLog, OauthClient, RefUserOrganization, RegistrationCode, User};
use App\Extensions\Helpers;
use App\Http\Traits\OrganizationsTrait;
use Carbon\Carbon;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Session\Store;
use Illuminate\Support\Facades\{Auth, DB, Hash, Lang, Log};

class ProfileController extends Controller
{
    use OrganizationsTrait;

    /**
     * @OA\Post(path="/api/v2/delete_profile", tags={"API(V2)"}, summary="Удалить профиль",
     *     description="Запрос на удаление авторизированного пользователя (через сессию)",
     *     @OA\Response(response=200, description="Успешное удаление",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean")
     *         )
     *     )
     * )
     *
     * Удалить профиль
     * @return JsonResponse
     */
    public function deleteProfile(): JsonResponse
    {
        $user = Auth::user();

        $user->setAttribute('is_deleted', true);
        if ($user->getAttribute('vk_user_id')) {
            $user->setAttribute('vk_user_id', null);
            $user->setAttribute('vk_updated_at', date('Y-m-d H:i:s'));
        }
        if ($user->getAttribute('fb_user_id')) {
            $user->setAttribute('fb_user_id', null);
            $user->setAttribute('fb_updated_at', date('Y-m-d H:i:s'));
        }
        if ($user->getAttribute('esia_user_id')) {
            $user->setAttribute('esia_user_id', null);
            $user->setAttribute('esia_updated_at', date('Y-m-d H:i:s'));
            /*$user->setAttribute('snils', null);
            $user->setAttribute('passport', null);*/
            $user->setAttribute('esia_info', null);
        }
        $user->setAttribute('phone', null);

        if ($user->save()) {
            Auth::logout();
            return response()->json(['status' => true]);
        }

        return response()->json(['status' => false]);
    }

    /**
     * @OA\Get(path="/api/v2/profile", tags={"API(V2)"}, summary="Получить данные профиля",
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="photo", type="string", example="zxcwqedfsdvfb43.png"),
     *                 @OA\Property(property="name", type="string", example="Admin"),
     *                 @OA\Property(property="surname", type="string", example="Super"),
     *                 @OA\Property(property="patroname", type="string", example="Superovich"),
     *                 @OA\Property(property="points", type="integer", example=5),
     *                 @OA\Property(property="city", type="integer", example=0),
     *                 @OA\Property(property="area_name", type="string", example="Оренбургская область"),
     *                 @OA\Property(property="birthday", type="string", example="2000-01-01"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="email", type="string", example="test@mail.ru"),
     *                 @OA\Property(property="phone", type="string", example="79123123123"),
     *                 @OA\Property(property="international_passport", type="string", example="14 8822800"),
     *                 @OA\Property(property="inn", type="string", example="123123123123"),
     *                 @OA\Property(property="pension_certificate", type="string", example="123-123-123 12"),
     *                 @OA\Property(property="driver_license", type="string", example="53 78 378354"),
     *             )
     *         )
     *     )
     * )
     *
     * Получить профиль
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['data' => 'Пользователь не найден'], 404);
        }

        $cityId = $user->getAttribute('city') ?? 0;

        $city = DB::table('dicts.d_areas')
            ->where('area_id', $cityId)
            ->get(['area_name'])
            ->first();
        $area_name = $city->area_name ?? '';

        $response = [
            'id' => $user->getAuthIdentifier(),
            'photo' => $user->getAttribute('photo'),
            'name' => $user->getAttribute('name'),
            'surname' => $user->getAttribute('surname'),
            'patroname' => $user->getAttribute('patroname'),
            'points' => $user->getAttribute('points'),
            'city' => $user->getAttribute('city'),
            'area_name' => $area_name,
            'birthday' => $user->getAttribute('birthday'),
            'gender' => $user->getAttribute('gender'),
            'email' => $user->getAttribute('email'),
            'phone' => $user->getAttribute('phone'),
            'international_passport' => $user->getAttribute('international_passport'),
            'inn' => $user->getAttribute('inn'),
            'pension_certificate' => $user->getAttribute('pension_certificate'),
            'driver_license' => $user->getAttribute('driver_license'),
            'isAdmin' => Helpers::checkRoleUser($user->getAuthIdentifier(), 'admin')
        ];

        return response()->json(['status' => true, 'data' => $response]);
    }

    /**
     * Возвращает email пользователя по JWT-токену (для авторизации в АГ-голосования через суперапп)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProfileByToken(Request $request): JsonResponse
    {
        if ($request->has('token')) {
            $token = $request->input('token');
            $user = User::where('jwt', $token)->get()->first();

            if (!$user) {
                return response()->json(['error' => 'Пользователь не найден'], 404);
            }

            return response()->json(['data' => $user->email]);
        }
        return response()->json(['error' => 'Отсутствует токен'], 400);
    }


    /**
     * @OA\Get(path="/api/v2/cities", tags={"API(V2)"}, summary="Получить список городов",
     *     description="Запрос на получение списка городов (по умолчанию кол-во записей = 7)",
     *     @OA\Parameter(name="city", in="query", description="Название города (н.п.) для поиска", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="count", in="query", description="Кол-во записей", required=false),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="area_id", type="integer", example=1),
     *                     @OA\Property(property="area_prnt_id", type="integer", example=0),
     *                     @OA\Property(property="area_name", type="string", example="Оренбургская область"),
     *                     @OA\Property(property="area_s_name", type="string", example="Оренбургская область"),
     *                     @OA\Property(property="area_polygon", type="string", example="[[[[53.612332, 46.435]]]]"),
     *                     @OA\Property(property="area_lng", type="string", example="53.534534535345534534"),
     *                     @OA\Property(property="area_lat", type="string", example="53.534534535345534534"),
     *                     @OA\Property(property="area_enabled", type="integer", example=1),
     *                     @OA\Property(property="area_order", type="integer", example=1),
     *                     @OA\Property(property="area_ofcl_name", type="string", example="Оренбургская область"),
     *                     @OA\Property(property="area_def_zoom", type="integer", example=10),
     *                     @OA\Property(property="area_lvl", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCities(Request $request): JsonResponse
    {
        $city = $request->get('city', '');
        $count = $request->get('count', 7);

        $cities = DB::table('dicts.d_areas')
            ->where('area_name', 'ilike', "%$city%")
            ->where('area_id', '>', 0)
            ->take($count)
            ->get();

        return response()->json(['status' => true, 'data' => $cities]);
    }

    /**
     * @OA\Get(path="/api/v2/loginESIA", tags={"API(V2)"}, summary="Регистрация и вход через ЕСИА",
     *     @OA\Parameter(name="redirect", in="query", description="URL для редиректа после авторизации",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="clientId", in="query", description="ID Клиента", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Успешная авторизация",
     *         @OA\JsonContent(
     *             @OA\Property(property="redirect", type="string", description="URL для редиректа после авторизации")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Ошибка авторизации",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Ошибка авторизации"),
     *             @OA\Property(property="esiaError", type="string", description="Ошибка ЕСИА")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @param Store $store
     * @return JsonResponse
     */
    public function loginESIA(Request $request, Store $store): JsonResponse
    {
        require_once __DIR__ . '/Plugins/ESIAAuth/EsiaAuthService.php';
        $esiaAuthService = \EsiaAuthService::get();

        $redirect = $request->get('redirect');
        $clientId = $request->get('clientId');
        $redirectUri = $request->get('redirect_uri');
        $isOAuth = $request->get('isOAuth');

        if ($redirect) {
            $request->session()->put('esiaRedirect', $redirect);
            $request->session()->save();
        }

        if ($clientId) {
            $request->session()->put('esiaClientId', $clientId);
            $request->session()->save();
        }

        if ($redirectUri) {
            $request->session()->put('redirectUri', $redirectUri);
            $request->session()->save();
        }

        if ($isOAuth) {
            $request->session()->put('goToOAuth', $isOAuth);
            $request->session()->save();
        }
        Log::info('Обработка запроса от фронтенда на /loginESIA',
            ['request' => $request->all()]
        );

        try {
            $result = $esiaAuthService->checkAuth();

            if (!$result) {
                throw new Exception('Ошибка проверки доступа в ЕСИА', 1);
            }

            if (strpos($result, 'http') === 0) {
                Log::info('Первый ответ (редирект)', [
                    'redirect' => $result,
                    'request' => $request->all()
                ]);
                return response()->json(['redirect' => $result]);
            }

            // получаем id пользователя из ЕСИА
            $esiaUserId = $esiaAuthService->getOID();

            if (!$esiaUserId) {
                throw new Exception('Ошибка получения ID пользователя из ЕСИА', 2);
            }

            if (!$esiaAuthService->isAccessToken()) {
                // если маркер является маркером идентификации, то перенаправляем пользователя на страницу запроса личных данных
                $scopes = [];
                $clientId = $request->session()->pull('esiaClientId');

                if ($clientId) {
                    $request->session()->put('esiaClientId', $clientId);
                    $request->session()->save();
                    $client = OauthClient::find($clientId);
                    if ($client) {
                        $scopesClient = $client->esiaScopes()->get();
                        foreach ($scopesClient as $scope) {
                            if ($scope->name !== 'ext_org_info')
                                $scopes = array_merge($scopes, $scope->scopes);
                        }
                    }
                }
                if (count($scopes)) {
                    $esiaAuthService->scopes = implode(' ', $scopes);
                }
                $result = $esiaAuthService->getUserInfoURL();

                Log::info('Второй ответ (редирект)', [
                    'redirect' => $result,
                    'request' => $request->all()
                ]);
                return response()->json(['redirect' => $result]);
            }

            $userInfo = $esiaAuthService->getUserInfo();
            if ($userInfo === false || $userInfo === null) {
                throw new Exception('Ошибка получения информации о пользователе из ЕСИА', 4);
            }

            $clientId = $request->session()->pull('esiaClientId');
            if ($clientId) {
                $request->session()->save();
                $client = OauthClient::find($clientId);
                $esiaLevelFilter = Helpers::stringToTrustedLevel($client->esia_level);
            } else {
                $esiaLevelFilter = Helpers::stringToTrustedLevel(config('auth.esia_trusted'));
            }

            if (Helpers::stringToTrustedLevel($userInfo['trusted']) < $esiaLevelFilter) {
                $code = $esiaLevelFilter === 3 ? 104 : 105;
                $message = Lang::get("errors.{$code}");
                Log::info("Exception ESIA: code {$code}");

                return response()->json(['errors' => [$message], 'type' => 'info'], 400);
            }

            $notReRegistration = $request->session()->pull('notReRegistration');
            $isOAuth = $request->session()->pull('goToOAuth');
            $isAuthorizeMobileWithSocial = $request->session()->pull('isAuthorizeMobileWithSocial');
            $redirect = $request->session()->pull('esiaRedirect');
            $request->session()->save();

            //Если пользователь авторизован, то привязываем к его профилю ЕСИА и направляем на дом
            if (Auth::check() && !$notReRegistration) {
                $authUser = Auth::user();

                $issetUser = User::where([
                    ['esia_user_id', $esiaUserId],
                    ['id', '<>', $authUser->getAttribute('id')]
                ])->first();

                if ($issetUser) {
                    if ($issetUser->email_verified_at && !$issetUser->blocked && !$issetUser->is_deleted) {
                        throw new Exception('Профиль ЕСИА привязан к другому пользователю', 3);
                    } else {
                        $this->removeExternalProfile('esia', $issetUser);
                    }
                }

                $data = $this->parseData($userInfo);
                $data['rowEsiaData'] = $userInfo;
                $data['esia_user_id'] = $esiaUserId;

                $authUserEsiaUserId = $authUser->getAttribute('esia_user_id');
                if ($isOAuth && $authUserEsiaUserId && $authUserEsiaUserId != $esiaUserId) {
                    throw new Exception('', 9);
                }

                $res = $this->updateUserEsia($data, $authUser);

                return $this->authorizeByUserId(
                    $authUser->getAttribute('id'),
                    $redirect,
                    $isOAuth || $isAuthorizeMobileWithSocial);
            }

            // проверяем наличие пользователя в локальной бд по id из ЕСИА
            $resultsUser = User::where('esia_user_id', $esiaUserId)->first();

            if ($resultsUser) {
                if ($resultsUser->blocked) {
                    throw new Exception('Пользователь заблокирован', 16);
                }

                if ($resultsUser->is_deleted) {
                    throw new Exception('Пользователь удалён', 17);
                }

                // если пользователь есть, то обновляем данные о пользователе из ЕСИА
                $userId = $resultsUser->id;
                $data = $this->parseData($userInfo);
                $data['rowEsiaData'] = $userInfo;
                $data['esia_user_id'] = $esiaUserId;

                if ($isOAuth && $resultsUser->esia_user_id && $resultsUser->esia_user_id != $esiaUserId) {
                    throw new Exception('', 9);
                }

                $this->updateUserEsia($data, $resultsUser);
                // и авторизуем
                return $this->authorizeByUserId(
                    $resultsUser->id,
                    $redirect,
                    $isOAuth || $isAuthorizeMobileWithSocial
                );
            }

            if (!empty($userInfo['email'])) {
                // если пользователя нет и из ЕСИА вернулся не пустой email,
                // то проверяем наличие пользователя по email из ЕСИА
                $resultsUser = User::where('email', mb_strtolower($userInfo['email']))->first();

                if (isset($resultsUser)) {
                    if ($resultsUser->blocked) {
                        throw new Exception('Пользователь заблокирован', 16);
                    }

                    if ($resultsUser->is_deleted) {
                        throw new Exception('Пользователь удалён', 17);
                    }

                    $data = $this->parseData($userInfo);
                    $data['rowEsiaData'] = $userInfo;
                    $data['esia_user_id'] = $esiaUserId;
                    $this->updateUserEsia($data, $resultsUser);

                    return $this->authorizeByUserId(
                        $resultsUser->id,
                        $redirect,
                        $isOAuth || $isAuthorizeMobileWithSocial
                    );
                }
            }

            $data = $this->parseData($userInfo);
            $data['rowEsiaData'] = $userInfo;
            $data['esia_user_id'] = $esiaUserId;
            $data['client_id'] = session()->get('mobileClientId')
                ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));

            if (!$redirect) {
                $redirect = redirect()->intended()->getTargetUrl();
            }

            $socialUserData = array_merge([
                'method' => 'esia',
                'registration_method' => 'esia',
                'esia_updated_at' => date('Y-m-d H:i:s.u P'),
                'password' => Helpers::generateString(8),
                'redirect' => $redirect,
                'isOAuth' => $isOAuth
            ], $data);

            $socialUserData['esia_info'] = $socialUserData;

            $user = $this->insertNewUser($socialUserData, true, !isset($data['email']));

            if (isset($user)) {
                return $this->authorizeByUserId($user->id, $socialUserData['method'], $socialUserData['redirect']);
            }

            Log::error('12: Registration failed');

            $errors = Lang::get('errors.12');

            return response()->json(['errors' => $errors], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'esiaError' => $this->getEsiaError($e)], 400);
        }
    }


    private function insertNewUser($data, $isActive = false, $generateLogin = false)
    {
        if (isset($data['age_period'])) {
            $data['birthday'] = Helpers::getDateFromAgePeriod($data['age_period']);
        }

        $activationHash = md5(microtime() . rand(100000, 999999));

        Hash::make($data['password']);

        if ($generateLogin) {
            $data['email'] = '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN' . md5(microtime() . rand(100000, 999999));
        }

        if (isset($data['phone'])) {
            $usersWithSamePhone = User::where('phone', $data['phone'])->get()->all();
            if (count($usersWithSamePhone)) {
                if (env('SMS_SECURITY', true)) {
                    foreach ($usersWithSamePhone as $userWithSamePhone) {
                        $userWithSamePhone->phone = null;
                        $userWithSamePhone->save();
                    }
                } else {
                    foreach ($usersWithSamePhone as $userWithSamePhone) {
                        if (!$userWithSamePhone->email_verified_at || $userWithSamePhone->blocked || $userWithSamePhone->is_deleted) {
                            $userWithSamePhone->phone = null;
                            $userWithSamePhone->save();
                        } else {
                            $data['phone'] = null;
                        }
                    }
                }
            }
        }

        $user = User::create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'patroname' => $data['patroname'] ?? null,
            'email' => !$generateLogin ? mb_strtolower($data['email']) : $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'esia_user_id' => $data['esia_user_id'] ?? null,
            'esia_updated_at' => isset($data['esia_user_id']) ? date('Y-m-d H:i:s') : null,
            'email_verified_at' => $isActive ? date('Y-m-d H:i:s') : null,
            'city' => $data['city'] ?? 0,
            'activation_hash' => $activationHash,
            'birthday' => isset($data['birthday']) ? date('Y-m-d', strtotime($data['birthday'])) : null,
            //'age_period' => isset($data['age_period']) ? $data['age_period'] : null,
            'gender' => $data['gender'] ?? null,
            'is_accepted' => isset($data['is_accepted']) || $isActive,

            'vk_user_id' => $data['vk_user_id'] ?? null,
            'vk_updated_at' => isset($data['vk_user_id']) ? date('Y-m-d H:i:s') : null,

            'login' => !$generateLogin ? mb_strtolower($data['email']) : $data['email'],
            'fio' => $data['name'] . ' ' . mb_strtoupper(mb_substr($data['surname'], 0, 1)) . '.',

            'fb_user_id' => $data['fb_user_id'] ?? null,
            'fb_updated_at' => isset($data['fb_user_id']) ? date('Y-m-d H:i:s') : null,

            //'snils' => isset($data['snils']) ? $data['snils'] : null,
            /*'passport' => (isset($data['passport_number']) && isset($data['passport_issue_date']) && isset($data['passport_issued_by']) && isset($data['passport_issue_id'])) ? json_encode(array(
                'number' => $data['passport_number'],
                'issue_date' => $data['passport_issue_date'],
                'issued_by' => $data['passport_issued_by'],
                'issue_id' => $data['passport_issue_id'],
            )) : null,*/
            'esia_info' => $data['esia_info'] ?? null,
            'latest_esia_scope' => $data['latest_esia_scope'] ?? null,

            //'role' => isset($data['role']) ? $data['role'] : null,

            'international_passport' => $data['international_passport'] ?? null,
            'inn' => $data['inn'] ?? null,
            'pension_certificate' => $data['pension_certificate'] ?? null,
            'driver_license' => $data['driver_license'] ?? null,

            'registration_method' => $data['registration_method'] ?? 'form',

        ]);

        if (isset($data['org_info'])) {
            foreach ($data['org_info'] as $org) {
                $orgId = self::updateOrCreateOrganization($org);
                $refid = self::createRefUserOrganization($user->getAttribute('id'), $orgId, $org);
            }
        }

//        if (!$isActive && $user) {
//            do {
//                $registrationCode = implode('', Helpers::randNumbers(6, 0, 9));
//                $regCode = RegistrationCode::where([['code', $registrationCode], ['is_used', false], ['expired_at', '>=', date('Y-m-d H:i:s')], ['user_email', $user->email]])->first();
//            } while (isset($regCode));
//            $expiredAt = Carbon::now()->addMinutes(env('MIX_REGISTRATION_CODE_MINUTES_LIFETIME', 60));
//            $regCode = RegistrationCode::create(['code' => $registrationCode, 'expired_at' => $expiredAt->format('Y-m-d H:i:s'), 'user_email' => $user->email]);
//
//            self::sendActivationEmail($user->getAttribute('id'), $user->getAttribute('email'), $activationHash, $registrationCode, $data['client_id'] ?? null);
//        } elseif ($isActive && $user && strripos($user->email, '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN') === false) {
//            $client = isset($data['client_id']) ? OauthClient::find($data['client_id']) : null;
//            self::sendSuccessRegistrationEmail($user->email, $client->name ?? null, $client->description ?? null, null, null, true);
//        }

        return $user;
    }

    /**
     * Парсинг данных от ЕСИА
     *
     * @param $userInfo
     * @return array
     * @throws Exception
     */
    private function parseData($userInfo): array
    {
        $data = [
            'name' => $userInfo['firstName'],
            'surname' => $userInfo['lastName'],
            'scope' => $userInfo['scope']
        ];

        if (isset($userInfo['birthDate'])) {
            $d = date_create_from_format('d.m.Y', $userInfo['birthDate']);
            $data['birthday'] = $d ? $d->format('Y-m-d') : null;
            $data['age_period'] = $data['birthday'] ? Helpers::getAgePeriodFromDate($data['birthday']) : null;
        }

        if (isset($userInfo['gender']) && preg_match('/^([MF])/i', $userInfo['gender'], $matches)) {
            switch ($matches[1]) {
                case 'F':
                {
                    $data['gender'] = 'female';
                    break;
                }
                case 'M':
                {
                    $data['gender'] = 'male';
                    break;
                }
                default:
                {
                    $data['gender'] = null;
                    break;
                }
            }
        }

        if (isset($userInfo['middleName'])) {
            $data['patroname'] = $userInfo['middleName'];
        }

        $data['fio'] = $data['name'] . ' ' . mb_strtoupper(mb_substr($data['surname'], 0, 1)) . '.';

        if (isset($userInfo['mobile'])) {
            $phone = $userInfo['mobile'];
            $phone = substr($phone, 0, 2) . ' '
                . substr($phone, 2, 5) . ' '
                . substr($phone, 7, 3) . '-'
                . substr($phone, 10, 2) . '-'
                . substr($phone, 12, 2);
            $data['phone'] = $phone;
        }

        $data['email'] = isset($userInfo['email']) ? mb_strtolower($userInfo['email']) : null;
        $data['snils'] = $userInfo['snils'] ?? null;

        if (isset($userInfo['city'])) {
            $address = ($userInfo['city']['residence'] ?? $userInfo['city']['registration'])['settlement'];
            $results = DB::table('dicts.d_areas')
                ->select('area_id')
                ->where([
                    [
                        'area_s_name',
                        'like',
                        '%' . implode(preg_grep('/[А-ЯЁ]\w+/u', explode(' ', $address)))
                    ],
                    ['area_id', '<>', 0]
                ])
                ->get()
                ->first();

            $data['city'] = $results ? $results->area_id : 0;
        }

        if (isset($userInfo['docs']) && isset($userInfo['docs']['rf_passport'])) {
            $esiaPassport = $userInfo['docs']['rf_passport'];
            $passport = [];
            $passport['number'] = isset($esiaPassport['series']) && isset($esiaPassport['number'])
                ? ($esiaPassport['series'] . ' ' . $esiaPassport['number'])
                : null;
            $d = isset($esiaPassport['issueDate'])
                ? date_create_from_format('d.m.Y', $esiaPassport['issueDate'])
                : false;
            $passport['issue_date'] = $d ? $d->format('Y-m-d') : null;
            $passport['issued_by'] = $esiaPassport['issuedBy'] ?? null;
            $passport['issue_id'] = $esiaPassport['issueId'] ?? null;
            $data['passport'] = $passport;
        }

        $data['org_info'] = $userInfo['org_info'] ?? null;
        $data['latest_esia_scope'] = $userInfo['scope'] ?? null;
        $data['trusted'] = $userInfo['trusted'] ?? null;

        return $data;
    }

    /**
     * Удаление привязанного профиля
     * @param string $profile
     * @param User $user
     * @return bool
     */
    private function removeExternalProfile(string $profile, User $user): bool
    {
        $res = false;
        $currentDate = date('Y-m-d H:i:s');
        switch ($profile) {
            case 'vk':
            {
                $user->vk_user_id = null;
                $user->vk_updated_at = $currentDate;
                $res = $user->save();
                break;
            }
            case 'fb':
            {
                $user->fb_user_id = null;
                $user->fb_updated_at = $currentDate;
                $res = $user->save();
                break;
            }
            case 'esia':
            {
                $user->esia_user_id = null;
                $user->esia_info = null;
                $user->esia_updated_at = $currentDate;
                $res = $user->save();

                RefUserOrganization::where('user_id', $user->id)->delete();
                break;
            }
        }
        return $res;
    }

    private static function updateUser($data, $is_esia = true): bool
    {
        $userId = $data['id'] ?? false;

        if (!$userId) {
            return false;
        }

        if ($is_esia && isset($data['org_info'])) {
            $refIds = [];
            foreach ($data['org_info'] as $org) {
                $orgId = self::updateOrCreateOrganization($org);
                $refIds[] = self::createRefUserOrganization($userId, $orgId, $org);
            }
            DB::table('organizations.ref_user_organization')
                ->where('user_id', $userId)
                ->whereNotIn('id', $refIds)
                ->delete();
        }

        $fields = User::getFields();
        $binds = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $fields)) {
                $binds[$key] = $value;
            }
        }

        $binds['updated_at'] = date('Y-m-d H:i:s');
        $binds['jwt'] = self::generateToken(['user_id' => $userId]);

        $res = DB::table('oauth.users')
            ->where('id', $userId)
            ->update($binds);

        return (bool)$res;
    }

    /**
     * Создание организации из ЕСИА.
     * @param array $data
     * @return integer
     */
    private static function updateOrCreateOrganization(array $data): int
    {
        $org = DB::table('organizations.organizations')
            ->select('id')
            ->where('esia_id', $data['oid'])
            ->get()
            ->first();

        if ($org) {
            $orgId = $org->id;
            DB::table('organizations.organizations')
                ->where('id', $orgId)
                ->update([
                    'full_name' => $data['fullName'] ?? null,
                    'short_name' => $data['shortName'] ?? null,
                    'ogrn' => $data['ogrn'] ?? null,
                    'org_type' => $data['type'] ?? null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            $orgId = DB::table('organizations.organizations')->insertGetId([
                'esia_id' => $data['oid'],
                'full_name' => $data['fullName'] ?? null,
                'short_name' => $data['shortName'] ?? null,
                'ogrn' => $data['ogrn'] ?? null,
                'org_type' => $data['type'] ?? null,
            ]);
        }
        return $orgId;
    }

    /**
     * Создание связи пользователя и организации ЕСИА.
     * @param integer $userId
     * @param integer $orgId
     * @param array $data
     * @return integer
     */
    private static function createRefUserOrganization(int $userId, int $orgId, array $data = []): int
    {
        $ref = DB::table('organizations.ref_user_organization')
            ->where([['user_id', $userId], ['org_id', $orgId]])
            ->select('id')
            ->get()
            ->first();

        $dbDate = [
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'chief' => $data['chief'] ?? false,
        ];

        if ($ref) {
            $dbDate['updated_at'] = date('Y-m-d H:i:s');
            DB::table('organizations.ref_user_organization')
                ->where('id', $ref->id)
                ->update($dbDate);
            return $ref->id;
        } else {
            $dbDate['org_id'] = $orgId;
            $dbDate['user_id'] = $userId;
            return DB::table('organizations.ref_user_organization')->insertGetId($dbDate);
        }
    }

    /**
     * Обновление пользователя с привязыванием профиля ЕСИА
     * @param array $data
     * @param User $user
     * @return bool
     */
    protected function updateUserEsia(array $data, User $user): bool
    {
        $newEsiaData = $user->esia_info ?? (object)[];
        $newEsiaRawData = $user->esia_info->rowEsiaData ?? (object)[];

        foreach ($data as $key => $value) {
            $newEsiaData->$key = $value;
        }

        foreach ($data['rowEsiaData'] as $key => $value) {
            $newEsiaRawData->$key = $value;
        }

        $newEsiaData->rowEsiaData = $newEsiaRawData;

        $params = [
            'esia_info' => json_encode($newEsiaData),
            'esia_user_id' => $data['esia_user_id'],
            'id' => $user->id,
            'org_info' => $data['org_info'] ?? null,
            'esia_updated_at' => date('Y-m-d H:i:s.u P'),
            'latest_esia_scope' => json_encode($data['latest_esia_scope'])
        ];

        if ($user->email_verified_at == null) {
            $params['email_verified_at'] = date('Y-m-d H:i:s');
        }
        return $this::updateUser($params);
    }

    /**
     * Авторизация пользователя по ID редирект в нужные места
     * @param int $userId пользователь
     * @param string|null $redirect адрес редиректа после авторизации
     * @param bool $isOAuth это OAuth авторизация
     * @return mixed
     */
    protected function authorizeByUserId(int $userId, string $redirect = null, bool $isOAuth = false)
    {
        if (Auth::loginUsingId($userId)) {
            $user = Auth::user();
            $user->setLatestAuthMethod('esia');

            $clientId = session()->get('mobileClientId')
                ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));
            AuthLog::create(['user_id' => $userId, 'auth_method' => 'esia', 'client_id' => $clientId]);
            Helpers::emmitEventToYM($user->id, "login_esia", time());

            Log::info('Вызван метод authorizeByUserId, т.е. авторизация выполнена успешно', [
                'request' => request()->all(),
                'redirect' => $redirect
            ]);

            if ($isOAuth) {
                return response()->json(['redirect' => route('passport.authorizations.approve')]);
            }

            return response()->json(['redirect' => $redirect]);
        } else {
            $errors = Lang::get('errors.13');
            return response()->json(['error' => $errors], 400);
        }
    }

    /**
     * Вывод ошибки при ошибке взаимодействия с ЕСИА
     * @param Exception $e
     * @return JsonResponse
     */
    protected function getEsiaError(Exception $e): JsonResponse
    {
        $lastError = \EsiaAuthService::get()->getLastError();
        $errors = [];

        switch ($lastError) {
            case 'access_denied':
            {
                $error = 'errors.access_denied';
                if (isset($_REQUEST['oid'])) {
                    $error .= '_oid';
                }
                $errors[] = Lang::get($error);
                break;
            }
            case 'temporary_unavailable':
            {
                $errors[] = Lang::get('errors.temporarily_unavailable');
                break;
            }
        }

        $code = $e->getCode();
        Log::info('Exception ESIA: code ' . $e->getCode() . ' message "' . $e->getMessage() . '"', [
            'request' => request()->all(),
            'errors' => $errors,
            'lastError' => $lastError
        ]);

        $message = Lang::get('errors.' . $code);

        if ($message != 'errors.' . $code) {
            $errors[] = $message;
        }

        if (count($errors) == 0) {
            $errors[] = Lang::get('errors.500');
        }
        return response()->json(['errors' => $errors], 400);
    }

    /**
     * Показ страницы с вопросом о создании нового пользователя или привязке к уже существующему
     * @param array $userData
     * @param Request $request
     * @param null $redirect
     * @param bool $isOAuth
     * @return JsonResponse
     */
    protected function showQuestionCreateNewUserOrAddOld(
        array $userData, Request $request, $redirect = null, bool $isOAuth = false
    ): JsonResponse
    {
        if (!$redirect) {
            $redirect = redirect()->intended()->getTargetUrl();
        }

        $route = app('router')->getRoutes()->match(app('request')->create($request));
        if (in_array($route->getName(), ['add_to_user', 'question_for_social_user'])) {
            $redirect = redirect()->route('home');
        }
        $request->session()->put('socialUserdata', [
            'method' => 'esia', 'data' => $userData, 'redirect' => $redirect, 'isOAuth' => $isOAuth
        ]);
        $request->session()->save();
        return response()->json(['redirect' => route('question_for_social_user', ['method' => 'esia'])]);

    }

    /**
     * Генерация JWT-токена
     * @param array $params
     * @return string
     */
    public static function generateToken(array $params): string
    {
        return JWT::encode($params, config('jwt.key'));
    }


    public function getProfileByPhone(Request $request): JsonResponse
    {
        $phone = '+' . $request->input('phone');
        $user = User::with('iwalk_client')->firstWhere('phone', $phone);
        if ($user) {
            $data = [
                'elk_user_id' => $user->id,
                'iwalk_user_id' => $user->iwalk_client->id
            ];
            return response()->json(['status' => true, 'data' => $data]);
        }
        return response()->json(['status' => false], 404);
    }
}
