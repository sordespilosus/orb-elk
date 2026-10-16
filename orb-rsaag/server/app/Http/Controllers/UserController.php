<?php

namespace App\Http\Controllers;

use App\{AccessKey,
    AuthLog,
    EmailsChange,
    EsiaScope,
    OauthClient,
    RefUserOrganization,
    RegistrationCode,
    User,
    UserMail,
    UsersOrganization
};
use App\Extensions\Helpers;
use App\Http\Requests\ConfirmSmsRequest;
use App\Http\Traits\OrganizationsTrait;
use Carbon\Carbon;
use DateTime;
use Firebase\JWT\JWT;
use GuzzleHttp;
use Illuminate\Http\Request;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\{Artisan, Auth, DB, Hash, Lang, Log, Redirect, Session, Storage, Validator, View};
use Illuminate\Validation\Rule;


class UserController extends Controller
{
    use OrganizationsTrait;

    /**
     * Авторизация пользователя по ID редирект в нужные места
     * @param int $userId пользователь
     * @param string $method Метод авторизации
     * @param string $redirect адрес редиректа после авторизации
     * @param bool $isOAuth это OAuth авторизация
     * @param string $loginBy
     * @return mixed
     */
    protected function authorizeByUserId($userId, $method = 'form', $redirect = null, $isOAuth = false, $loginBy = 'login')
    {
        if (Auth::loginUsingId($userId)) {
            $user = Auth::user();
            $user->setLatestAuthMethod($loginBy);
            // Аутентификация успешна...
            $clientId = session()->get('mobileClientId') ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));
            AuthLog::create(['user_id' => $userId, 'auth_method' => $method, 'client_id' => $clientId]);
            Helpers::emmitEventToYM($user->id, "login_$loginBy", time());
            if ($isOAuth) {
                return redirect()->route('passport.authorizations.approve');
            }
            return $redirect !== null ? redirect($redirect) : redirect()->intended(route('home'));
        } else {
            $errors = Lang::get('errors.13');
            return response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
        }
    }

    /**
     * Вывод ошибки при ошибке взаимодействия с ЕСИА
     * @param \Exception $e
     * @return mixed
     */
    protected function getEsiaError(\Exception $e)
    {
        $last_error = \EsiaAuthService::get()->getLastError();
        $errors = array();
        switch ($last_error) {
            case 'access_denied':
                if (isset($_REQUEST['oid'])) {
                    $errors[] = Lang::get('errors.' . 'access_denied_oid');
                } else {
                    $errors[] = Lang::get('errors.' . 'access_denied');
                }
                break;
            case 'temporarily_unavailable':
                $errors[] = Lang::get('errors.' . 'temporarily_unavailable');
                break;
        }
        $code = $e->getCode();
        Log::info('Exception ESIA: code ' . $e->getCode() . ' message "' . $e->getMessage() . '"');
        $message = Lang::get('errors.' . $code);
        if ($message != 'errors.' . $code)
            $errors[] = $message;
        if (count($errors) == 0)
            $errors[] = Lang::get('errors.500');
        return response()->view("errors/error", [
            'errors' => $errors,
        ], 400);
    }

    /**
     * Обновление пользователя с привязыванием профиля ЕСИА
     * @param array $data
     * @param User $user
     * @return bool
     */
    protected function updateUserEsia($data, $user)
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

        // $address = (isset($newEsiaData->rowEsiaData->city['residence']) ? $newEsiaData->rowEsiaData->city['residence'] : $newEsiaData->rowEsiaData->city['registration'])['settlement'];
        // $results = DB::table('dicts.d_areas')->select('area_id')
        //   ->where([['area_s_name', 'like', '%' . implode(preg_grep('/[А-ЯЁ]\w+/u', explode(" ", $address)))], ['area_id', '<>', 0]])->get()->first();

        // if ($results){
        //   $data['city'] = $results->area_id;
        // }
        // else{
        //   $data['city'] = 0;
        // }

        $params = [
            'esia_info' => json_encode($newEsiaData),
            'esia_user_id' => $data['esia_user_id'],
            'id' => $user->id,
            'org_info' => isset($data['org_info']) ? $data['org_info'] : null,
            'esia_updated_at' => date('Y-m-d H:i:s.u P'),
            'latest_esia_scope' => json_encode($data['latest_esia_scope']),
            // 'city' => $data['city']
        ];
        if ($user->email_verified_at == null)
            $params['email_verified_at'] = date('Y-m-d H:i:s');
        $res = $this::updateUser($params, true);

        return $res;
    }

    /**
     * Создание нового пользователя из данных ЕСИА
     * @param array $data
     * @return User
     */
    protected function createUserEsia($data)
    {
        $data['esia_info'] = $data;
        $data['registration_method'] = 'esia';
        $data['esia_updated_at'] = date('Y-m-d H:i:s.u P');
        $data['password'] = Helpers::generateString(8);

        $user = $this->insertNewUser($data, true, !isset($data['email']));
        return $user;
    }

    /**
     * Регистрация и вход через ЕСИА
     * @param Request $request
     * @param SessionStore $session
     * @return mixed
     */
    public function loginESIA(Request $request, SessionStore $session)
    {
        require_once __DIR__ . '/Plugins/ESIAAuth/EsiaAuthService.php';

        $redirect = $request->get('redirect', null);

        if ($redirect !== null) {
            $request->session()->put('esiaRedirect', $redirect);
            $request->session()->save();
        }
        $clientId = $request->get('client_id', null);
        if ($clientId !== null) {
            $request->session()->put('esiaClientId', $clientId);
            $request->session()->save();
        }

        // проверяем наличие аутентификации в ЕСИА
        $result = \EsiaAuthService::get()->checkAuth();

        try {
            if ($result === false) {
                throw new \Exception('Ошибка проверки доступа в ЕСИА', 1);
            }

            if (strpos($result, 'http') === 0) {
                // необходимо перенаправление в ЕСИА
                header('location: ' . $result);
                exit();
            } else {
                // получаем id пользователя из ЕСИА
                $esia_user_id = \EsiaAuthService::get()->getOID();

                if (!$esia_user_id) {
                    throw new \Exception('Ошибка получения id пользователя из ЕСИА', 2);
                }

                if (!\EsiaAuthService::get()->isAccessToken()) {
                    // если маркер является маркером идентификации, то перенаправляем пользователя на страницу запроса личных данных
                    //$needOrg = $request->session()->pull('esiaRedirect');
                    //$request->session()->save();
                    $clientId = $request->session()->pull('esiaClientId');
                    $scopes = [];
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
                    if (count($scopes))
                        \EsiaAuthService::get()->scopes = implode(' ', $scopes);
                    header('location: ' . \EsiaAuthService::get()->getUserInfoURL());
                    exit();
                } else {
                    $parseData = function ($user_info) {
                        $data = [
                            'name' => $user_info['firstName'],
                            'surname' => $user_info['lastName'],
                            //'esia_updated_at' => date('Y-m-d H:i:s'),
                            'scope' => $user_info['scope']
                        ];
                        if (isset($user_info['birthDate'])) {
                            $d = date_create_from_format('d.m.Y', $user_info['birthDate']);
                            $data['birthday'] = $d != false ? $d->format('Y-m-d') : null;
                            $data['age_period'] = $data['birthday'] ? Helpers::getAgePeriodFromDate($data['birthday']) : null;
                        }
                        if (isset($user_info['gender']) && preg_match('/^(M|F)/i', $user_info['gender'], $matches)) {
                            switch ($matches[1]) {
                                case 'F':
                                    $data['gender'] = 'female';
                                    break;
                                case 'M':
                                    $data['gender'] = 'male';
                                    break;
                                default:
                                    $data['gender'] = null;
                            }
                        }
                        if (isset($user_info['middleName'])) {
                            $data['patroname'] = $user_info['middleName'];
                        }
                        $data['fio'] = $data['name'] . ' ' . mb_strtoupper(mb_substr($data['surname'], 0, 1)) . '.';
                        if (isset($user_info['mobile'])) {
                            $phone = $user_info['mobile'];
                            $phone = substr($phone, 0, 2) . ' '
                                . substr($phone, 2, 5) . ' '
                                . substr($phone, 7, 3) . '-'
                                . substr($phone, 10, 2) . '-'
                                . substr($phone, 12, 2);
                            $data['phone'] = $phone;
                        }
                        if (isset($user_info['email'])) {
                            $data['email'] = mb_strtolower($user_info['email']);
                        }
                        if (isset($user_info['snils'])) {
                            $data['snils'] = $user_info['snils'];
                        }
                        if (isset($user_info['city'])) {
                            $address = (isset($user_info['city']['residence']) ? $user_info['city']['residence'] : $user_info['city']['registration'])['settlement'];
                            $results = DB::table('dicts.d_areas')->select('area_id')
                                ->where([['area_s_name', 'like', '%' . implode(preg_grep('/[А-ЯЁ]\w+/u', explode(" ", $address)))], ['area_id', '<>', 0]])->get()->first(); //TODO

                            if ($results) {
                                $data['city'] = $results->area_id;
                            } else {
                                $data['city'] = 0;
                            }
                        }

                        if (isset($user_info['docs']) && isset($user_info['docs']['rf_passport'])) {
                            $esiaPassport = $user_info['docs']['rf_passport'];
                            $passport = [];
                            $passport['number'] = isset($esiaPassport['series']) && isset($esiaPassport['number']) ? ($esiaPassport['series'] . ' ' . $esiaPassport['number']) : null;
                            $d = isset($esiaPassport['issueDate']) ? date_create_from_format('d.m.Y', $esiaPassport['issueDate']) : false;
                            $passport['issue_date'] = $d != false ? $d->format('Y-m-d') : null;
                            $passport['issued_by'] = isset($esiaPassport['issuedBy']) ? $esiaPassport['issuedBy'] : null;
                            $passport['issue_id'] = isset($esiaPassport['issueId']) ? $esiaPassport['issueId'] : null;
                            $data['passport'] = $passport;
                        }

                        if (isset($user_info['org_info'])) { //TODO организации
                            $data['org_info'] = $user_info['org_info'];
                        }

                        if (isset($user_info['scope'])) {
                            $data['latest_esia_scope'] = $user_info['scope'];
                        }

                        if (isset($user_info['trusted'])) {
                            $data['trusted'] = $user_info['trusted'];
                        }
                        return $data;
                    };

                    // если маркер является маркером доступа, то извлекаем дополнительную информацию о пользователе из ЕСИА
                    $user_info = \EsiaAuthService::get()->getUserInfo(); //TODO Контактная иформация

                    if ($user_info === false || $user_info === null) {
                        throw new \Exception('Ошибка получения информации о пользователе из ЕСИА', 4);
                    }

                    $clientId = $request->session()->pull('esiaClientId');
                    if ($clientId) {
                        $request->session()->save();
                        $client = OauthClient::find($clientId);
                        $esiaLevelFilter = Helpers::stringToTrustedLevel($client->esia_level);
                    } else {
                        $esiaLevelFilter = Helpers::stringToTrustedLevel(env('ESIA_TRUSTED', 'NONE'));
                    }

                    if (Helpers::stringToTrustedLevel($user_info['trusted']) < $esiaLevelFilter) {
                        $code = $esiaLevelFilter === 3 ? 104 : 105;
                        $message = Lang::get('errors.' . $code);
                        Log::info('Exception ESIA: code ' . $code);

                        return response()->view("errors/error", [
                            'errors' => [$message],
                            'type' => 'info'
                        ], 400);
                    }

                    $notReRegistration = $request->session()->pull('notReRegistration');
                    $isOAuth = $request->session()->pull('goToOAuth');
                    $isAuthorizeMobileWithSocial = $request->session()->pull('isAuthorizeMobileWithSocial');
                    $redirect = $request->session()->pull('esiaRedirect');
                    $request->session()->save();
                    //Если пользователь авторизован, то привязываем к его профилю ЕСИА и направляем на дом
                    if (Auth::check() && !$notReRegistration) {
                        $authUser = Auth::user();

                        $issetUser = User::where([['esia_user_id', $esia_user_id], ['id', '<>', $authUser->getAttribute('id')]])->first();
                        /*$results = DB::table('oauth.users')->select('id', 'email_verified_at')
                            ->where([['esia_user_id', $esia_user_id], ['id', '<>', $authUser->getAttribute('id')]])->get()->first();*/
                        if ($issetUser) {
                            if ($issetUser->email_verified_at && !$issetUser->blocked && !$issetUser->is_deleted)
                                throw new \Exception('Профиль ЕСИА привязан к другому пользователю', 3);
                            else {
                                $this->removeExternalProfile('esia', $issetUser);
                            }
                        }

                        $data = $parseData($user_info);
                        $data['rowEsiaData'] = $user_info;
                        $data['esia_user_id'] = $esia_user_id;

                        if ($isOAuth && $authUser->getAttribute('esia_user_id') && $authUser->getAttribute('esia_user_id') != $esia_user_id)
                            throw new \Exception('', 9);

                        $res = $this->updateUserEsia($data, $authUser);

                        /*$res = $this::updateUser([
                            'esia_info' => json_encode($data),
                            'esia_user_id' => $esia_user_id,
                            'id' => $authUser->id,
                            'org_info' => isset($data['org_info']) ? $data['org_info'] : null,
                            'esia_updated_at' => date('Y-m-d H:i:s'),
                            'latest_esia_scope' => json_encode($data['latest_esia_scope']),
                        ], true);*/

                        return $this->authorizeByUserId($authUser->getAttribute('id'), 'esia', $redirect, $isOAuth || $isAuthorizeMobileWithSocial, 'esia');
                    }

                    // проверяем наличие пользователя в локальной бд по id из ЕСИА
                    $resultsUser = User::where('esia_user_id', $esia_user_id)->first();


                    if ($resultsUser) {
                        /*if ($resultsUser->email_verified_at == null){
                            throw new \Exception('Ваша учётная запись не потверждена', 11);
                        }*/
                        if ($resultsUser->blocked) {
                            throw new \Exception('Пользователь заблокирован', 16);
                        }
                        if ($resultsUser->is_deleted) {
                            throw new \Exception('Пользователь удалён', 17);
                        }
                        // если пользователь есть, то обновляем данные о пользователе из ЕСИА
                        $user_id = $resultsUser->id;

                        $data = $parseData($user_info);
                        $data['rowEsiaData'] = $user_info;
                        $data['esia_user_id'] = $esia_user_id;


                        //$isOAuth = $request->session()->pull('goToOAuth');
                        if ($isOAuth && $resultsUser->esia_user_id && $resultsUser->esia_user_id != $esia_user_id)
                            throw new \Exception('', 9);
                        $this->updateUserEsia($data, $resultsUser);
                        /*$this->updateUser([
                            'esia_info' => json_encode($data),
                            'id' => $user_id,
                            'org_info' => isset($data['org_info']) ? $data['org_info'] : null,
                            'esia_updated_at' => date('Y-m-d H:i:s'),
                            'latest_esia_scope' => json_encode($data['latest_esia_scope']),
                        ], true);*/

                        // и авторизуем
                        return $this->authorizeByUserId($resultsUser->id, 'esia', $redirect, $isOAuth || $isAuthorizeMobileWithSocial, 'esia');
                    } elseif (!isset($user_info['email']) || empty($user_info['email'])) { //TODO
                        // если пользователя нет и из ЕСИА вернулся пустой email,
                        $data = $parseData($user_info);
                        $data['rowEsiaData'] = $user_info;
                        $data['esia_user_id'] = $esia_user_id;
                        $data['client_id'] = session()->get('mobileClientId') ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));
                        return $this->showQuestionCreateNewUserOrAddOld($data, 'esia', $request, $redirect, $isOAuth || $isAuthorizeMobileWithSocial);

                        /*$user = $this->insertNewUser($data, true, true);

                        if ($user)
                            return $this->authorizeByUserId($user->getAttribute('id'), $redirect, $isOAuth || $isAuthorizeMobileWithSociall, 'esia');
                        else
                            throw new \Exception('Зарегистрировать пользователя не удалось', 12);*/
                    } else {
                        // если пользователя нет и из ЕСИА вернулся не пустой email,
                        // то проверяем наличие пользователя по email из ЕСИА
                        $resultsUser = User::where('email', mb_strtolower($user_info['email']))->first();

                        if (isset($resultsUser)) {
                            // если найден пользователь с тем же email, то привязываем его к ЕСИА и обновляем данные

                            /*if ($resultsUser->email_verified_at == null){
                                throw new \Exception('Ваша учётная запись не потверждена', 11);
                            }*/
                            if ($resultsUser->blocked) {
                                throw new \Exception('Пользователь заблокирован', 16);
                            }
                            if ($resultsUser->is_deleted) {
                                throw new \Exception('Пользователь удалён', 17);
                            }

                            $data = $parseData($user_info);
                            $data['rowEsiaData'] = $user_info;
                            $data['esia_user_id'] = $esia_user_id;
                            $this->updateUserEsia($data, $resultsUser);

                            return $this->authorizeByUserId($resultsUser->id, 'esia', $redirect, $isOAuth || $isAuthorizeMobileWithSocial, 'esia');
                        } else { //TODO
                            // если не найден пользователь с тем же email, то добавляем
                            $data = $parseData($user_info);
                            $data['rowEsiaData'] = $user_info;
                            $data['esia_user_id'] = $esia_user_id;
                            /*$data['esia_info'] = $data;
                            $data['esia_user_id'] = $esia_user_id;
                            $data['password'] = md5(microtime() . rand(100000, 999999));*/
                            $data['client_id'] = session()->get('mobileClientId') ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));
                            return $this->showQuestionCreateNewUserOrAddOld($data, 'esia', $request, $redirect, $isOAuth || $isAuthorizeMobileWithSocial);

                            /*$user = $this->insertNewUser($data, true);

                            if ($user)
                                return $this->authorizeByUserId($user->getAttribute('id'), $redirect, $isOAuth || $isAuthorizeMobileWithSocial, 'esia');
                            else
                                throw new \Exception('Зарегистрировать пользователя не удалось', 12);*/
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return $this->getEsiaError($e);
        }

        return redirect()->route('home');
    }


    /**
     * Регистрация и вход через ВКонтакте
     * @param Request $request
     * @return mixed
     */
    public function registrationFromVK(Request $request)
    {

        //Идём за авторизацией в вк
        $display = $request->get('display', 'page'); //TODO Определять с чего сидит пользователь
        $redir = $request->get('redirect', null);
        $params = [
            'client_id' => env('VK_CLIENT_ID'),
            'redirect_uri' => env('VK_REDIRECT_URL'),
            'response_type' => 'code',
            'scope' => 'email',
            'display' => $display,
            'v' => env('VK_API_VERSION'),
        ];
        if ($redir) {
            $params['state'] = $redir;
        }
        $query = http_build_query($params);

        return redirect('https://oauth.vk.com/authorize?' . $query);
    }

    /**
     * Обновление пользователя из ВК
     * @param array $data
     * @param User $user
     * @return mixed
     */
    protected function updateUserVk($data, $user)
    {
        $user->vk_updated_at = date('Y-m-d H:i:s.u P');
        $user->vk_user_id = $data['vk_user_id'];
        if ($user->email_verified_at == null) {
            $user->email_verified_at = date('Y-m-d H:i:s');
        }
        return $user->save();
    }

    /**
     * Создание нового пользователя из данных VK
     * @param array $data
     * @return User
     */
    protected function createUserVk($data)
    {
        $data['password'] = Helpers::generateString(8);
        $data['vk_updated_at'] = date('Y-m-d H:i:s.u P');
        $data['registration_method'] = 'vk';

        $user = $this->insertNewUser($data, true, !isset($data['email']));
        return $user;
    }

    /**
     * Разбор ответа ВК (Сюда возвращаются данные после авторизации в ВК)
     * @param Request $request
     * @return mixed
     */
    public function loginVK(Request $request, SessionStore $session)
    {
        $code = $request->get('code', false);
        $error = $request->get('error', false);
        $errorDescription = $request->get('error_description', false);
        $isAuthorizeMobileWithSocial = $request->session()->pull('isAuthorizeMobileWithSocial');
        $request->session()->save();

        $redirect = $request->get('state');

        try {
            if ($error !== false || !$code) {
                //$errors[] = ['message' => $error . ': ' . $errorDescription];
                throw new \Exception('', 20);
            }

            //Получаем токен пользователя
            $http = new GuzzleHttp\Client(array(
                'verify' => false
            )); // Чтобы не валилось с ошибкой проверки сертификата
            $response = $http->get('https://oauth.vk.com/access_token', [
                'query' => [
                    'client_id' => env('VK_CLIENT_ID'),
                    'client_secret' => env('VK_CLIENT_SECRET'),
                    'redirect_uri' => env('VK_REDIRECT_URL'),
                    'code' => $code,
                ]
            ]);

            $content = json_decode($response->getBody());

            if (!$content || isset($content->error)) {
                //$errors[] = ['message' => 'Авторизация через ВКонтакте временно недоступна'];
                throw new \Exception('', 20);
            }

            $accessToken = $content->access_token;
            $expiresIn = $content->expires_in;
            $userId = $content->user_id;
            $userEmail = isset($content->email) ? mb_strtolower($content->email) : false;

            // Если пользователь авторизован, то привязываем к его профилю ВК и направляем на дом
            if (Auth::check()) {
                $authUser = Auth::user();

                $issetUser = User::where([['vk_user_id', $userId], ['id', '<>', $authUser->getAttribute('id')]])->first();
                if ($issetUser) {
                    if ($issetUser->email_verified_at && !$issetUser->blocked)
                        throw new \Exception('', 21);
                    else {
                        $this->removeExternalProfile('vk', $issetUser);
                    }
                }

                $this->updateUserVk(['vk_user_id' => $userId], $authUser);

                return $this->authorizeByUserId($authUser->getAttribute('id'), 'vk', $redirect, $isAuthorizeMobileWithSocial, 'vk');
            }

            // Смотрим есть пользователь в бд
            $resultsUser = User::where('vk_user_id', $userId)->first();

            if ($resultsUser) {
                // Если есть пользователь в бд, то авторизуем его
                $resultsUser->checkAvailable();
                return $this->authorizeByUserId($resultsUser->id, 'vk', $redirect, $isAuthorizeMobileWithSocial, 'vk');
            }

            if ($userEmail) {
                // Смотрим есть ли пользователь с таким мылом
                $resultsUser = User::where('email', $userEmail)->first();

                if ($resultsUser) {
                    //Если пользователь есть привязываем его к вк и авторизуем
                    $resultsUser->checkAvailable(false);

                    $this->updateUserVk(['vk_user_id' => $userId], $resultsUser);

                    return $this->authorizeByUserId($resultsUser->id, 'vk', $redirect, $isAuthorizeMobileWithSocial, 'vk');
                } else {
                    //если пользователя нет получаем данные и создаём его
                    $data = $this->getVKUser($userId, $accessToken);
                    if (!$data) {
                        throw new \Exception('', 22);
                    }
                    $data['email'] = $userEmail;
                    $data['client_id'] = session()->get('mobileClientId') ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));
                    return $this->showQuestionCreateNewUserOrAddOld($data, 'vk', $request, $redirect, $isAuthorizeMobileWithSocial);
                }
            } else {
                //Если не пришло мыло перенаправляем на страницу для ввода почты
                $data = $this->getVKUser($userId, $accessToken);
                if (!$data) {
                    throw new \Exception('', 22);
                }
                $data['client_id'] = session()->get('mobileClientId') ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));
                return $this->showQuestionCreateNewUserOrAddOld($data, 'vk', $request, $redirect, $isAuthorizeMobileWithSocial);
            }
        } catch (\Exception $e) {
            $errors = Lang::get('errors.' . $e->getCode());
            if ($errors == 'errors.' . $e->getCode())
                $errors = Lang::get('errors.500');
            return response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
        }
    }

    /**
     * Получение данные о пользователе из ВК
     * @param integer $userId
     * @param string $accessToken
     * @return mixed
     */
    private function getVKUser($userId, $accessToken)
    {
        $http = new GuzzleHttp\Client(array(
            'verify' => false
        )); //Чтобы не валилось с ошибкой проверки сертификата
        $response = $http->get('https://api.vk.com/method/users.get', [
            'query' => [
                'user_ids' => $userId,
                'fields' => 'first_name,last_name,bdate,city,sex',
                'name_case' => 'nom',
                'access_token' => $accessToken,
                'v' => env('VK_API_VERSION'),
            ]
        ]);

        $content = json_decode($response->getBody());

        if (!$content) {
            //$errors[] = ['message' => 'Авторизация через ВКонтакте временно недоступна'];
            return false;
        }

        if (isset($content->error) || !isset($content->response)) {
            //$errors[] = ['message' => $content->error . ': ' . $content->error_description];
            return false;
        }

        if (count($content->response) == 0)
            return false;

        $content = $content->response[0];

        $gender = null;
        if ($content->sex == 1) {
            $gender = 'female';
        } elseif ($content->sex == 2) {
            $gender = 'male';
        }

        $city = null;
        if (isset($content->city)) {
            $results = DB::table('dicts.d_areas')->select('area_id')
                ->where([['area_s_name', 'like', '%' . $content->city->title], ['area_id', '<>', 0]])
                ->first();

            if ($results) {
                $city = $results->area_id;
            } else {
                $city = 0;
            }
        }

        $d = isset($content->bdate) ? date_create_from_format('j.n.Y', $content->bdate) : false;
        $data = array(
            'vk_user_id' => $content->id,
            'name' => $content->first_name,
            'surname' => $content->last_name,
            'city' => $city,
            'birthday' => $d != false ? $d->format('Y-m-d') : null,
            'gender' => $gender,
            'vk_updated_at' => date('Y-m-d H:i:s'),
        );

        return $data;
    }

    /**
     * Обновление данных пользователя
     * @param array $data
     * @param boolean $is_esia
     * @return boolean
     */
    public static function updateUser($data, $is_esia = false)
    {
        $userId = isset($data['id']) ? $data['id'] : false;
        if (!$userId) {
            return false;
        }

        unset($data['id']);

        /*if ($is_esia)
            self::pushEsiaNewData($data);*/

        if ($is_esia && isset($data['org_info'])) { //TODO организации
            $refids = [];
            foreach ($data['org_info'] as $org) {
                $orgId = self::updateOrCreateOrganization($org);
                $refids[] = self::createRefUserOrganization($userId, $orgId, $org);
            }
            DB::table('organizations.ref_user_organization')
                ->where('user_id', $userId)
                ->whereNotIn('id', $refids)
                ->delete();
        }

        unset($data['org_info']);

        $fields = User::getFields();
        $binds = array();
        foreach ($data as $k => $v) {
            if (in_array($k, $fields))
                $binds[$k] = $v;
        }

        $binds['updated_at'] = date('Y-m-d H:i:s');
        $binds['jwt'] = self::generateToken(['user_id' => $userId]);

        $res = DB::table('oauth.users')
            ->where('id', $userId)
            ->update($binds);

        return (bool)$res;
    }

    /**
     * Функция получения валидирующий строк данных пользователя
     * @param boolean $isCreated
     * @param boolean $needReCapchaV2
     * @return array
     */
    public static function validatorFields($isCreated = false, $needReCapchaV2 = false)
    {
        $email = ['required', 'email', 'max:255',];
        $phone = ['nullable', 'string', 'max:255', 'regex:/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/'];
        $login = ['required', 'string', 'max:255', 'regex:/^[^а-яА-ЯёЁ]*$/u',];
        $vk_user_id = ['nullable', 'numeric',];
        $esia_user_id = ['nullable', 'numeric',];
        $fb_user_id = ['nullable', 'numeric',];
        $snils = ['nullable', 'string', 'max:255', 'regex:/^[0-9]{3}-[0-9]{3}-[0-9]{3} [0-9]{2}$/u'];
        $passport_number = ['nullable', 'string', 'max:255', 'regex:/^[0-9]{4} [0-9]{6}$/u'];
        $international_passport = ['nullable', 'string', 'max:10']; //TODO Более серьёзные валидаторы
        $inn = ['nullable', 'string', 'max:12']; //TODO Более серьёзные валидаторы
        $pension_certificate = ['nullable', 'string', 'max:14']; //TODO Более серьёзные валидаторы
        $driver_license = ['nullable', 'string', 'max:12']; //TODO Более серьёзные валидаторы


        if (Auth::check()) {
            $user = Auth::user();
            $email[] = Rule::unique('users')->ignore($user->getAttribute('id'));
            /*if (!env('SMS_SECURITY', true)) {
                $phone[] = Rule::unique('users')->ignore($user->getAttribute('id'));
            }*/
            $login[] = Rule::unique('users')->ignore($user->getAttribute('id'));
            $vk_user_id[] = Rule::unique('users')->ignore($user->getAttribute('id'));
            $esia_user_id[] = Rule::unique('users')->ignore($user->getAttribute('id'));
            $fb_user_id[] = Rule::unique('users')->ignore($user->getAttribute('id'));
            $snils[] = Rule::unique('users')->ignore($user->getAttribute('id'));
            $passport_number[] = Rule::unique('users')->ignore($user->getAttribute('id'));
        } else {
            $email[] = 'unique:users';
            /*if (!env('SMS_SECURITY', true)) {
                $phone[] = 'unique:users';
            }*/
            $login[] = 'unique:users';
            $vk_user_id[] = 'unique:users';
            $esia_user_id[] = 'unique:users';
            $fb_user_id[] = 'unique:users';
            $snils[] = 'unique:users';
            $passport_number[] = 'unique:users';
        }

        $fields = [
            'name' => ['required', 'string', 'max:255', 'regex:/^[а-яА-ЯёЁa-zA-Z]+(\-[а-яА-ЯёЁa-zA-Z]+)*$/u'],
            'email' => $email,
            'password' => 'required|string|min:6|confirmed',

            'surname' => ['required', 'string', 'max:255', 'regex:/^[а-яА-ЯёЁa-zA-Z]+(\-[а-яА-ЯёЁa-zA-Z]+)*$/u'],
            'patroname' => ['nullable', 'string', 'max:255', 'regex:/^[а-яА-ЯёЁa-zA-Z]+(\-[а-яА-ЯёЁa-zA-Z]+)*$/u'],
            'phone' => $phone,
            'city' => ['nullable', 'numeric'],
            'birthday' => 'nullable|date',
            'gender' => ['nullable', 'string', 'regex:/^(male|female){1}$/'],

            'vk_user_id' => $vk_user_id,
            'esia_user_id' => $esia_user_id,
            'fb_user_id' => $fb_user_id,

            'login' => $login,

            'snils' => $snils,

            'passport_number' => $passport_number,
            'passport_issue_date' => ['nullable', 'date'],
            'passport_issued_by' => ['nullable', 'string', 'max:255'],
            'passport_issue_id' => ['nullable', 'string', 'max:255', 'regex:/^[0-9]{3}-[0-9]{3}$/u'],

            'is_accepted' => ['required', 'accepted'],
            //'age_period' => ['nullable', Rule::in(['1', '2', '3', '4'])],
            'sms_hash' => ['required', 'regex:/^[0-9a-z]{32}$/u'],

            'international_passport' => $international_passport,
            'inn' => $inn,
            'pension_certificate' => $pension_certificate,
            'driver_license' => $driver_license,

            'client_id' => ['nullable', 'integer']
        ];

        if ($isCreated) {
            $fields['email_verified_at'] = 'required';
            $fields['blocked'] = ['required', Rule::in([false])];
            if (env('NEED_RECAPTCHA', false) && (env('RECAPTCHA_V', 3) == 2 || $needReCapchaV2))
                $fields['g-recaptcha-response'] = ['required', 'string'];
            //$fields['sms_hash'] = ['required', 'regex:/^[0-9a-z]{32}$/u'];
        }
        return $fields;
    }

    /**
     * Валидация перед созданием пользователя через регистрацию
     * @param array $data
     * @param boolean $needReCapchaV2
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validatorByCreateUser(array $data, $needReCapchaV2 = false)
    {
        $fields = UserController::validatorFields(true, $needReCapchaV2);

        unset($fields['login']);
        return Validator::make($data, $fields);
    }

    /**
     * Функция валидации любых полей пользователя
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validatorUserData(array $data)
    {
        $vlidators = self::validatorFields();
        $fields = [];

        foreach ($data as $k => $v) {
            if (isset($vlidators[$k]))
                $fields[$k] = $vlidators[$k];
        }

        return Validator::make($data, $fields);
    }

    /**
     * Создание нового пользователя
     * @param array $data
     * @param boolean $isActive
     * @param boolean $generateLogin
     * @return User
     */
    public static function insertNewUser($data, $isActive = false, $generateLogin = false)
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
            'patroname' => isset($data['patroname']) ? $data['patroname'] : null,
            'email' => !$generateLogin ? mb_strtolower($data['email']) : $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => isset($data['phone']) ? $data['phone'] : null,
            'esia_user_id' => isset($data['esia_user_id']) ? $data['esia_user_id'] : null,
            'esia_updated_at' => isset($data['esia_user_id']) ? date('Y-m-d H:i:s') : null,
            'email_verified_at' => $isActive ? date('Y-m-d H:i:s') : null,
            'city' => isset($data['city']) ? $data['city'] : 0,
            'activation_hash' => $activationHash,
            'birthday' => isset($data['birthday']) ? date('Y-m-d', strtotime($data['birthday'])) : null,
            //'age_period' => isset($data['age_period']) ? $data['age_period'] : null,
            'gender' => isset($data['gender']) ? $data['gender'] : null,
            'is_accepted' => isset($data['is_accepted']) || $isActive ? true : false,

            'vk_user_id' => isset($data['vk_user_id']) ? $data['vk_user_id'] : null,
            'vk_updated_at' => isset($data['vk_user_id']) ? date('Y-m-d H:i:s') : null,

            'login' => !$generateLogin ? mb_strtolower($data['email']) : $data['email'],
            'fio' => $data['name'] . ' ' . mb_strtoupper(mb_substr($data['surname'], 0, 1)) . '.',

            'fb_user_id' => isset($data['fb_user_id']) ? $data['fb_user_id'] : null,
            'fb_updated_at' => isset($data['fb_user_id']) ? date('Y-m-d H:i:s') : null,

            //'snils' => isset($data['snils']) ? $data['snils'] : null,
            /*'passport' => (isset($data['passport_number']) && isset($data['passport_issue_date']) && isset($data['passport_issued_by']) && isset($data['passport_issue_id'])) ? json_encode(array(
                'number' => $data['passport_number'],
                'issue_date' => $data['passport_issue_date'],
                'issued_by' => $data['passport_issued_by'],
                'issue_id' => $data['passport_issue_id'],
            )) : null,*/
            'esia_info' => isset($data['esia_info']) ? $data['esia_info'] : null,
            'latest_esia_scope' => isset($data['latest_esia_scope']) ? $data['latest_esia_scope'] : null,

            //'role' => isset($data['role']) ? $data['role'] : null,

            'international_passport' => isset($data['international_passport']) ? $data['international_passport'] : null,
            'inn' => isset($data['inn']) ? $data['inn'] : null,
            'pension_certificate' => isset($data['pension_certificate']) ? $data['pension_certificate'] : null,
            'driver_license' => isset($data['driver_license']) ? $data['driver_license'] : null,

            'registration_method' => $data['registration_method'] ?? 'form',

        ]);

        if (isset($data['org_info'])) { //TODO организации
            foreach ($data['org_info'] as $org) {
                $orgId = self::updateOrCreateOrganization($org);
                $refid = self::createRefUserOrganization($user->getAttribute('id'), $orgId, $org);
            }
        }

        if (!$isActive && $user) {
            do {
                $registrationCode = implode('', Helpers::randNumbers(6, 0, 9));
                $regCode = RegistrationCode::where([['code', $registrationCode], ['is_used', false], ['expired_at', '>=', date('Y-m-d H:i:s')], ['user_email', $user->email]])->first();
            } while (isset($regCode));
            $expiredAt = Carbon::now()->addMinutes(env('MIX_REGISTRATION_CODE_MINUTES_LIFETIME', 60));
            $regCode = RegistrationCode::create(['code' => $registrationCode, 'expired_at' => $expiredAt->format('Y-m-d H:i:s'), 'user_email' => $user->email]);

            self::sendActivationEmail($user->getAttribute('id'), $user->getAttribute('email'), $activationHash, $registrationCode, $data['client_id'] ?? null);
        } elseif ($isActive && $user && strripos($user->email, '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN') === false) {
            $client = isset($data['client_id']) ? OauthClient::find($data['client_id']) : null;
            self::sendSuccessRegistrationEmail($user->email, $client->name ?? null, $client->description ?? null, null, null, true);
        }

        return $user;
    }

    private static function sendActivationEmail($id, $email, $activationHash, $registrationCode, $clientId)
    {
        $link = env('APP_URL') . "/user/activate?user_id=$id&code=$activationHash";
        if ($clientId) {
            $link .= "&client_id=$clientId";
        }

        /*$mail = new RegistrationMail($link);

        Mail::to($user->getAttribute('email'))->send($mail);*/
        $mailId = DB::table('mailer.user_mails')->insertGetId([
            'email' => $email,
            'type' => 'register',
            'params' => json_encode(['link' => $link, 'code' => $registrationCode]),
        ]);
        $exitCode = Artisan::call('send_emails', [
            '--id' => $mailId
        ]);
    }

    /**
     * Отправка письма с подтвержденияем регистрации
     * @param string $email
     * @param int $clientName
     * @param string $clientDescription
     * @param string $userLogin
     * @param string $userPassword
     * @return void
     */
    private static function sendSuccessRegistrationEmail($email, $clientName = null, $clientDescription = null, $userLogin = null, $userPassword = null, $esiaLogin = false)
    {
        $mailId = DB::table('mailer.user_mails')->insertGetId([
            'email' => $email,
            'type' => 'success_register',
            'params' => json_encode([
                'clientName' => $clientName,
                'clientDescription' => $clientDescription,
                'userLogin' => $userLogin,
                'userPassword' => $userPassword,
                'esiaLogin' => $esiaLogin,
            ]),
        ]);
        $exitCode = Artisan::call('send_emails', [
            '--id' => $mailId
        ]);
    }

    /**
     * Функция подтверждения регистрации
     * @param Request $request
     * @return View
     */
    public function confirmRegistration(Request $request)
    {
        $userId = $request->get('user_id');
        $activationCode = $request->get('code');
        $clientId = $request->get('client_id', null);

        $binds = [
            'id' => $userId,
            'activation_code' => $activationCode,
            'client_id' => $clientId
        ];

        $validator = Validator::make($binds, [
            'id' => 'required|numeric',
            'activation_code' => 'required|string|max:32',
            'client_id' => 'nullable|integer'
        ]);

        $funcError = function ($code) {
            $errors = Lang::get('errors.' . $code);
            return response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
        };

        if ($validator->fails()) return $funcError(400);
        $user = DB::table('oauth.users')->where([['id', $userId], ['activation_hash', $activationCode]])->select(['id', 'email_verified_at', 'email'])->get()->first();

        if (!$user) return $funcError(14);
        if ($user->email_verified_at != null) return $funcError(13);

        $res = DB::table('oauth.users')
            ->where('id', $userId)
            ->update(['email_verified_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);

        if ($res == 1) {
            if (Auth::loginUsingId($userId)) {

                $client = isset($clientId) ? OauthClient::find($clientId) : null;

                self::sendSuccessRegistrationEmail($user->email, $client->name ?? null, $client->description ?? null);

                if ($client) {
                    return redirect($client->url_login);
                }

                $request->session()->put('isUserActivation', true);
                return redirect()->route('profile');
            } else
                return $funcError(10);
        } else {
            return $funcError(11);
        }
    }

    /**
     * Функция окончания регистрации
     * @param Request $request
     * @return View
     */
    public function endRegistation(Request $request)
    {
        $email = $request->get('email', '');
        return view('auth/endRegistration', ['email' => $email]);
    }

    /**
     * Функция переадресовывающая на регистрацию
     * @param array $data
     * @param Session $session
     * @return void
     */
    private function toRegistration($data, $session)
    {

        $session->put('registerData', $data);
        $session->save();

        return redirect()->route('register');
    }


    /**
     * Функция получения профиля пользователя
     * @param Request $request
     * @return View
     */
    public function getProfile(Request $request)
    {
        $user = Auth::user();
        $userField = [];

        $isUserActivation = $request->session()->pull('isUserActivation', false);

        //$userField['login'] = $user->getAttribute('login');
        $userField['birthday'] = $user->getAttribute('birthday') ? date('d.m.Y', strtotime($user->getAttribute('birthday'))) : null;
        $userField['gender'] = $user->getAttribute('gender');
        $userField['city'] = $user->getAttribute('city');
        $userField['name'] = $user->getAttribute('name');
        $userField['surname'] = $user->getAttribute('surname');
        $userField['patroname'] = $user->getAttribute('patroname');
        $userField['email'] = strripos($user->getAttribute('email'), '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN') === false ? $user->getAttribute('email') : null;
        $userField['phone'] = $user->getAttribute('phone');
        //$userField['snils'] = $user->getAttribute('snils');
        $userField['photo'] = $user->getAttribute('photo');
        $userField['points'] = $user->getAttribute('points');
        $userField['is_accepted'] = $user->getAttribute('is_accepted');
        /*$passport = json_decode($user->getAttribute('passport'), JSON_OBJECT_AS_ARRAY);
        $userField['passport_number'] = $passport['number'];
        $userField['passport_issue_date'] = $passport['issue_date'];
        $userField['passport_issued_by'] = $passport['issued_by'];
        $userField['passport_issue_id'] = $passport['issue_id'];*/

        $esiaInfo = $user->getAttribute('esia_info');
        if (isset($user->esia_info)) {

            $esiaFields = [
                'surname',
                'name',
                'patroname',
                'birthday',
                'email',
                'gender',
                'phone',
                'snils',
                'trusted',
                'passport',
            ];

            $eisaRawFields = ['kids', 'address'];
            $esiaData = (object)[];

            foreach ($esiaFields as $esiaField) {
                if (isset($user->esia_info->$esiaField)) {
                    $esiaData->$esiaField = $user->esia_info->$esiaField;
                }
            }

            foreach ($eisaRawFields as $eisaRawField) {
                if (isset($user->esia_info->rowEsiaData->$eisaRawField)) {
                    $esiaData->$eisaRawField = $user->esia_info->rowEsiaData->$eisaRawField;
                }
            }

            if (isset($esiaData)) {
                $esiaData->birthday = isset($esiaData->birthday) ? date('d.m.Y', strtotime($esiaData->birthday)) : null;
                /**
                 * todo: hehhohhehhoh@gmail.com
                 */
                $userField["birthday"] = isset($esiaData->birthday) ? date('d.m.Y', strtotime($esiaData->birthday)) : null;

                if (isset($esiaData->passport)) {
                    $esiaData->passport->issue_date = isset($esiaData->passport->issue_date) ? date('d.m.Y', strtotime($esiaData->passport->issue_date)) : null;
                }

                if (isset($esiaData->gender)) {
                    $esiaData->gender = $esiaData->gender == 'female' ? 'Женский' : 'Мужской';
                }

                $esiaData->trusted = $esiaData->trusted == 'TRUSTED' ? 'Подтвержденная' : ($esiaData->trusted == 'STANDART' ? 'Стандартная' : 'Упрощенная');

                if (isset($esiaData->kids)) {
                    $esiaData->kids = array_map(function ($item) {
                        return (object)[
                            'birthDate' => $item->birthDate ?? null,
                            'firstName' => $item->firstName,
                            'gender' => $item->gender == 'F' ? 'Женский' : 'Мужской',
                            'lastName' => $item->lastName ?? null,
                            'middleName' => $item->middleName ?? null
                        ];
                    }, $esiaData->kids);
                }
            }
        }

        $userField['international_passport'] = $user->getAttribute('international_passport');
        $userField['inn'] = $user->getAttribute('inn');
        $userField['pension_certificate'] = $user->getAttribute('pension_certificate');
        $userField['driver_license'] = $user->getAttribute('driver_license');

        $vk_updated_at = null;
        if ($user->getAttribute('vk_updated_at')) {
            $date = new DateTime($user->getAttribute('vk_updated_at'));
            $vk_updated_at = $date->format('d.m.Y H:i');
        }
        $fb_updated_at = null;
        if ($user->getAttribute('fb_updated_at')) {
            $date = new DateTime($user->getAttribute('fb_updated_at'));
            $fb_updated_at = $date->format('d.m.Y H:i');
        }
        $esia_updated_at = null;
        if ($user->getAttribute('esia_updated_at')) {
            $date = new DateTime($user->getAttribute('esia_updated_at'));
            $esia_updated_at = $date->format('d.m.Y H:i');
        }

        $social = [
            [
                'id' => 'vk',
                'name' => 'ВКонтакте',
                'isActive' => $user->getAttribute('vk_user_id') ? true : false,
                'updatedAt' => $vk_updated_at,
                'url' => route('login_from_vk')
            ],
            // [
            //     'id' => 'fb',
            //     'name' => 'Facebook',
            //     'isActive' => $user->getAttribute('fb_user_id') ? true : false,
            //     'updatedAt' => $fb_updated_at,
            //     'url' => route('login_from_fb')
            // ],
            [
                'id' => 'esia',
                'name' => 'Госуслуги',
                'isActive' => $user->getAttribute('esia_user_id') ? true : false,
                'updatedAt' => $esia_updated_at,
                'url' => route('loginESIA')
            ],
        ];

        //$cities = DB::table('dicts.d_areas')->select('area_id', 'area_prnt_id', 'area_name', 'area_s_name', 'area_s_name')->where('area_prnt_id', 0)->orWhere('area_id', 0)->orderBy('area_order')->get()->all();
        $cities = DB::select('select * from dicts.get_tree_areas(in_area_lvl => 1)');

        /*$userField['age_period'] = null;
        if ($user->getAttribute('age_period'))
            $userField['age_period'] = $user->getAttribute('age_period');
        elseif ($user->getAttribute('birthday')) {
            $userField['age_period'] = Helpers::getAgePeriodFromDate($user->getAttribute('birthday'));
        }*/
        $userOrgs = $this->normalizeOrgModel($this->getOrganizationsUserWithModel());
        $isOrgUser = $userOrgs && count($userOrgs);

        return view('profile', [
            'old' => $userField,
            'cities' => $cities,
            'social' => $social,
            'userOrgs' => $userOrgs,
            'isOrgUser' => $isOrgUser,
            'isUserActivation' => $isUserActivation,
            'esiaInfo' => $esiaData ?? null
        ]);
    }

    /**
     * @OA\Post(path="/profile/send_image", tags={"UserController"}, summary="Изменение картинки профиля",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="file", description="Изображение")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Фотография успешно сохранена",
     *         @OA\JsonContent(
     *             @OA\Property(property="file_name", type="string", description="Имя файла"),
     *             @OA\Property(property="status", type="string", description="Статус изменения картинки")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Недопустимый формат файла",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", description="Статус изменения картинки")
     *         )
     *     )
     * )
     *
     * Загрузка картинки
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendImage(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->getAttribute('id');
        $file = $request->file('file')->getClientOriginalName();
        $extension = $request->file('file')->getClientOriginalExtension();
        $arrayExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($arrayExtensions as $item) {
            if ($extension == $item) {
                $userNamePhoto = DB::table('oauth.users')->select('photo')->where('id', '=', $user_id)->get();
                Storage::disk('public')->delete('avatars/' . $userNamePhoto[0]->photo);

                $fileName = md5(rand(0, 1000000) . microtime() . $file) . ".$item";

                DB::table('oauth.users')->where('id', '=', $user_id)->update(['photo' => $fileName]);
                $request->file('file')->storeAs('avatars', $fileName, 'public');

                return response()->json(['file_name' => $fileName, 'status' => 'Фотография успешно сохранена']);
            }
        }
        return response()->json(['status' => 'Недопустимый формат файла'], 400);
    }

    /**
     * @OA\Post(path="/profile", summary="Функция изменения профиля пользователя (в т.ч. изменение города)", tags={"UserController"},
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(
     *                 required={"birthday", "gender", "city", "name", "surname", "patroname", "phone", "sms_hash"},
     *                 @OA\Property(property="birthday", type="string", format="date", description="Дата рождения"),
     *                 @OA\Property(property="gender", type="string", description="Пол"),
     *                 @OA\Property(property="city", type="string", description="Город"),
     *                 @OA\Property(property="name", type="string", description="Имя"),
     *                 @OA\Property(property="surname", type="string", description="Фамилия"),
     *                 @OA\Property(property="patroname", type="string", description="Отчество"),
     *                 @OA\Property(property="phone", type="string", description="Телефон"),
     *                 @OA\Property(property="sms_hash", type="string", description="Хеш SMS")
     *             ),
     *         )
     *     ),
     *     @OA\Response(response=200, description="Профиль обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", description="Статус обновления")
     *         )
     *     )
     * )
     *
     * Функция изменения профиля пользователя
     * @param Request $request
     * @return array
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->getAttribute('id');
        $fields = $request->only([
            //'login',
            'birthday',
            'gender',
            'city',
            'name',
            'surname',
            'patroname',
            //'email',
            'phone',
            'sms_hash',
            //'age_period',
            //'snils',
            //'passport_number',
            //'passport_issue_date',
            //'passport_issued_by',
            //'passport_issue_id',
            'international_passport',
            'inn',
            'pension_certificate',
            'driver_license',
            'avatar',
        ]);

        if (isset($fields['phone'])) {
            if ($fields['phone'] == $user->getAttribute('phone')) {
                $fields['sms_hash'] = '90be854aa5e1f11d02763c9a5da0a047';
            } elseif (env('SMS_SECURITY', true) == false) {
                $fields['sms_hash'] = '90be854aa5e1f11d02763c9a5da0a047';
            } elseif (!Helpers::verifySmsHash($fields['phone'], $fields['sms_hash'], 'profile')) {
                $fields['sms_hash'] = null;
            }
        } else {
            $fields['sms_hash'] = '90be854aa5e1f11d02763c9a5da0a047';
        }

        /*$esiaFields = json_decode($user->getAttribute('esia_info'), JSON_OBJECT_AS_ARRAY);
        if ($esiaFields){
            foreach ($esiaFields as $field){
                if ($field == 'passport')
                    unset($fields['passport_number'], $fields['passport_issue_date'], $fields['passport_issued_by'], $fields['passport_issue_id']);
                unset($fields[$field]);
            }
        }*/
        $validator = $this->validatorUserData($fields);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return ['status' => false, 'errors' => $errors->messages()];
        }

        if (isset($fields['phone']) && !env('SMS_SECURITY', true)) {
            $userPhones = DB::table('oauth.users')->select('email_verified_at', 'blocked', 'is_deleted', 'id')->where([['phone', $fields['phone']], ['id', '<>', $user->id]])->get()->all();
            if (count($userPhones)) {
                $isAllowed = true;
                foreach ($userPhones as $userPhone) {
                    if ($userPhone->email_verified_at && !$userPhone->blocked && !$userPhone->is_deleted) {
                        $isAllowed = false;
                    }
                }
                if (!$isAllowed) {
                    $res = ['status' => false, 'errors' => ['phone' => ['Пользователь с данным телефоном уже зарегистрирован']]];
                    return $res;
                }
            }
        }

        if (isset($fields['phone']) && $fields['phone'] != $user->getAttribute('phone')) {
            Helpers::resetSmsCount($fields['phone'], 'profile');
            $usersWithSamePhone = User::where([['phone', $fields['phone']], ['id', '<>', $user->id]])->get()->all();
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
                            $fields['phone'] = null;
                        }
                    }
                }
            }
        }

        if (isset($fields['name']) && isset($fields['surname'])) {
            $fields['fio'] = $fields['name'] . ' ' . mb_strtoupper(mb_substr($fields['surname'], 0, 1)) . '.';
        }

        /*if (isset($fields['age_period'])){
            $dateNow = new DateTime();
            switch ($fields['age_period']){
                case '1':
                    $fields['birthday'] = $dateNow->format('Y-m-d');
                    break;
                case '2':
                    $fields['birthday'] = $dateNow->sub(new DateInterval('P18Y'))->format('Y-m-d');
                    break;
                case '3':
                    $fields['birthday'] = $dateNow->sub(new DateInterval('P35Y'))->format('Y-m-d');
                    break;
                case '4':
                    $fields['birthday'] = $dateNow->sub(new DateInterval('P55Y'))->format('Y-m-d');
                    break;
            }
        }*/

        if (isset($fields['birthday'])) {
            $fields['birthday'] = date('Y-m-d', strtotime($fields['birthday']));
        }

        $fields['id'] = $user_id;
        $res = $this::updateUser($fields);

        return ['status' => $res];
    }

    /**
     * Функция изменения пароля пользователя
     * @param Request $request
     * @return array
     */
    public function passwordChange(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->getAttribute('id');
        //$field = $request->all();
        $field = $request->only([
            'old_password',
            'password',
            'password_confirmation',
        ]);

        if (!isset($field['old_password']) || !isset($field['password']) || !isset($field['password_confirmation'])) {
            return ['status' => false, 'errors' => ['old_password' => ['Заполните все поля']]];
        }

        if ($field['old_password'] == $field['password']) {
            return ['status' => false, 'errors' => ['password' => ['Новый пароль не может совпадать со старым']]];
        }
        $credentials = ['login' => $user->getAttribute('login'), 'password' => $field['old_password']];

        $check = Auth::validate($credentials);
        if (!$check) {
            return ['status' => false, 'errors' => ['old_password' => ['Пароль не верный']]];
        }

        unset($field['_token']);

        $validator = $this->validatorUserData($field);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return ['status' => false, 'errors' => $errors->messages()];
        }

        $data = ['id' => $user_id, 'password' => Hash::make($field['password'])];

        $res = $this::updateUser($data);
        return ['status' => $res];
    }

    /**
     * Функция отвязки профиля ВК от пользователя
     * @param Request $request
     * @return array
     */
    public function vkRemove(Request $request)
    {
        $user = Auth::user();
        $res = $this->removeExternalProfile('vk', $user);
        //$res = $this::updateUser(['id' => $user->getAttribute('id'), 'vk_user_id' => null, 'vk_updated_at' => null]);

        return ['status' => $res];
    }

    /**
     * Функция отвязки профиля FB от пользователя
     * @param Request $request
     * @return array
     */
    public function fbRemove(Request $request)
    {
        $user = Auth::user();
        $res = $this->removeExternalProfile('fb', $user);
        //$res = $this::updateUser(['id' => $user->getAttribute('id'), 'fb_user_id' => null, 'fb_updated_at' => null]);

        return ['status' => $res];
    }

    /**
     * Функция отвязки профиля ЕСИА от пользователя
     * @param Request $request
     * @return array
     */
    public function esiaRemove(Request $request)
    {
        $user = Auth::user();
        $res = $this->removeExternalProfile('esia', $user);

        /*$res = $this::updateUser(['id' => $user->getAttribute('id'), 'esia_user_id' => null, 'esia_info' => null, 'esia_updated_at' => null], true);

        if ($res)
            DB::table('organizations.ref_user_organization')->where('user_id', $user->getAttribute('id'))->delete();*/

        return ['status' => $res];
    }

    /**
     * Удаление привязанного профиля
     * @param string $profile
     * @param User $user
     * @return bool
     */
    private function removeExternalProfile($profile, $user)
    {
        $res = false;
        switch ($profile) {
            case 'vk':
                $user->vk_user_id = null;
                $user->vk_updated_at = date('Y-m-d H:i:s');
                $res = $user->save();
                //$res = $this::updateUser(['id' => $user->getAttribute('id'), 'vk_user_id' => null, 'vk_updated_at' => null]);
                break;
            case 'fb':
                $user->fb_user_id = null;
                $user->fb_updated_at = date('Y-m-d H:i:s');
                $res = $user->save();
                //$res = $this::updateUser(['id' => $user->getAttribute('id'), 'fb_user_id' => null, 'fb_updated_at' => null]);
                break;
            case 'esia':
                $user->esia_user_id = null;
                $user->esia_info = null;
                $user->esia_updated_at = date('Y-m-d H:i:s');
                $res = $user->save();
                //$res = $this::updateUser(['id' => $user->getAttribute('id'), 'esia_user_id' => null, 'esia_info' => null, 'esia_updated_at'
                RefUserOrganization::where('user_id', $user->id)->delete();
                //DB::table('organizations.ref_user_organization')->where('user_id', $user->getAttribute('id'))->delete();
                break;
        }
        return $res;
    }

    /**
     * Регистрация и вход через Лицокнига(Facebook)
     * @param Request $request
     * @return Redirect
     */
    public function registrationFromFB(Request $request)
    {

        //Идём за авторизацией в fb
        $redir = $request->get('redirect', null);
        $params = [
            'client_id' => env('FB_CLIENT_ID'),
            'redirect_uri' => env('FB_REDIRECT_URL'),
            'response_type' => 'code',
            'scope' => 'email,user_birthday,user_gender,user_location',
        ];
        if ($redir)
            $params['state'] = $redir;
        $query = http_build_query($params);

        return redirect('https://www.facebook.com/v3.2/dialog/oauth?' . $query);
    }

    /**
     * Обновление пользователя из ВК
     * @param array $data
     * @param User $user
     * @return mixed
     */
    protected function updateUserFb($data, $user)
    {
        $user->fb_updated_at = date('Y-m-d H:i:s.u P');
        $user->fb_user_id = $data['fb_user_id'];
        if ($user->email_verified_at == null)
            $user->email_verified_at = date('Y-m-d H:i:s');
        return $user->save();
    }

    /**
     * Создание нового пользователя из данных VK
     * @param array $data
     * @return User
     */
    protected function createUserFb($data)
    {
        $data['password'] = Helpers::generateString(8);
        $data['fb_updated_at'] = date('Y-m-d H:i:s.u P');
        $data['registration_method'] = 'fb';

        $user = $this->insertNewUser($data, true, !isset($data['email']));
        return $user;
    }

    /**
     * разбор ответа от Facebook (Сюда возвращаются данные после авторизации в FB)
     * @param Request $request
     * @param SessionStore $session
     * @return View
     */
    public function loginFB(Request $request, SessionStore $session)
    {
        $code = $request->get('code', false);
        $error = $request->get('error', false);
        $errorDescription = $request->get('error_description', false);
        $isAuthorizeMobileWithSocial = $request->session()->pull('isAuthorizeMobileWithSocial');
        $request->session()->save();

        $redirect = $request->get('state', null);

        try {
            if ($error !== false || !$code) {
                //$errors[] = ['message' => $error . ': ' . $errorDescription];
                throw new \Exception('', 30);
            }

            //Получаем токен пользователя
            $http = new GuzzleHttp\Client(array(
                'verify' => false
            )); //Чтобы не валилось с ошибкой проверки серитфиката
            $response = $http->get('https://graph.facebook.com/v3.2/oauth/access_token', [
                'query' => [
                    'client_id' => env('FB_CLIENT_ID'),
                    'client_secret' => env('FB_CLIENT_SECRET'),
                    'redirect_uri' => env('FB_REDIRECT_URL'),
                    'code' => $code,
                ]
            ]);

            $content = json_decode($response->getBody());

            if (!$content || isset($content->error)) {
                //$errors[] = ['message' => 'Авторизация через FB временно недоступна'];
                throw new \Exception('', 30);
            }

            $accessToken = $content->access_token;
            $expiresIn = $content->expires_in;

            $response = $http->get('https://graph.facebook.com/v3.2/me', [
                'query' => [
                    'access_token' => $accessToken,
                ]
            ]);

            $content = json_decode($response->getBody());
            $userId = $content->id;

            //Если пользователь авторизован, то привязываем к его профилю ВК и направляем на дом
            if (Auth::check()) {
                $authUser = Auth::user();

                $issetUser = User::where([['fb_user_id', $userId], ['id', '<>', $authUser->getAttribute('id')]])->first();
                if ($issetUser) {
                    if ($issetUser->email_verified_at && !$issetUser->blocked)
                        throw new \Exception('', 31);
                    else {
                        $this->removeExternalProfile('fb', $issetUser);
                    }
                }

                $this->updateUserFb(['fb_user_id' => $userId], $authUser);

                return $this->authorizeByUserId($authUser->getAttribute('id'), 'fb', $redirect, $isAuthorizeMobileWithSocial, 'fb');
            }

            //Смотрим есть пользователь в бд
            $resultsUser = User::where('fb_user_id', $userId)->first();

            if ($resultsUser) {
                //Если есть пользователь в бд то авторизуем его
                $resultsUser->checkAvailable();
                return $this->authorizeByUserId($resultsUser->id, 'fb', $redirect, $isAuthorizeMobileWithSocial, 'fb');
            }

            $data = $this->getFBUser($userId, $accessToken);

            if (!$data) {
                throw new \Exception('', 32);
            }

            if ($data['email']) {
                //Смотрим есть ли пользователь с таким мылом
                $resultsUser = User::where('email', $data['email'])->first();

                if ($resultsUser) {
                    //Если пользователь есть привязываем его к fb и авторизуем
                    $resultsUser->checkAvailable(false);

                    $this->updateUserFb(['fb_user_id' => $userId], $resultsUser);

                    return $this->authorizeByUserId($resultsUser->id, 'fb', $redirect, $isAuthorizeMobileWithSocial, 'fb');
                } else {
                    //если пользователя нет получаем данные и создаём его
                    $data['client_id'] = session()->get('mobileClientId') ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));
                    return $this->showQuestionCreateNewUserOrAddOld($data, 'fb', $request, $redirect, $isAuthorizeMobileWithSocial);
                }
            } else {
                //Если не пришло мыло перенаправляем на страницу для ввода почты
                $data['client_id'] = session()->get('mobileClientId') ?? Helpers::getClientIdFromUrl($redirect ?? session()->get('url.intended'));
                return $this->showQuestionCreateNewUserOrAddOld($data, 'fb', $request, $redirect, $isAuthorizeMobileWithSocial);
            }
        } catch (\Exception $e) {
            $errors = Lang::get('errors.' . $e->getCode());
            if ($errors == 'errors.' . $e->getCode())
                $errors = Lang::get('errors.500');
            return response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
        }
    }

    /**
     * Получение данные о пользователе из Facebook
     * @param integer $userId
     * @param string $accessToken
     * @return mixed
     */
    private function getFBUser($userId, $accessToken)
    {
        $http = new GuzzleHttp\Client(array(
            'verify' => false
        )); //Чтобы не валилось с ошибкой проверки серитфиката
        $response = $http->get('https://graph.facebook.com/v3.2/' . $userId, [
            'query' => [
                'fields' => 'id,first_name,last_name,middle_name,email,birthday,gender,location',
                'locale' => 'ru_RU',
                'access_token' => $accessToken,
            ]
        ]);

        $content = json_decode($response->getBody());

        if (!$content || isset($content->error)) {
            //$errors[] = ['message' => 'Авторизация через ВКонтакте временно недоступна'];
            return false;
        }

        $gender = null;
        if (isset($content->gender)) {
            if ($content->gender === 'мужской')
                $gender = 'male';
            else
                $gender = 'female';
        }

        $city = null;
        if (isset($content->location)) {
            $city_res = explode(', ', $content->location->name);

            if (isset($city_res[0])) {
                $results = DB::table('dicts.d_areas')->select('area_id')
                    ->where([['area_s_name', 'like', '%' . $city_res[0]], ['area_id', '<>', 0]])->get()->first(); //TODO

                if ($results) {
                    $city = $results->area_id;
                } else {
                    $city = 0;
                }
            }
        }

        $d = isset($content->birthday) ? date_create_from_format('d/m/Y', $content->birthday) : false;
        $data = array(
            'fb_user_id' => $content->id,
            'email' => isset($content->email) ? mb_strtolower($content->email) : null,
            'name' => $content->first_name,
            'surname' => $content->last_name,
            'patroname' => isset($content->middle_name) ? $content->middle_name : null,
            'city' => $city,
            'birthday' => $d != false ? $d->format('Y-m-d') : null,
            'gender' => $gender,
            'fb_updated_at' => date('Y-m-d H:i:s'),
        );

        return $data;
    }

    /**
     * Отправка сообщения с активационным кодом на почту пользователю
     * @param Request $request
     * @return array
     */
    public function sendEmailActivationAgain(Request $request)
    {
        $email = $request->get('email', null);

        $validator = Validator::make([
            'email' => $email,
        ], [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) return ['status' => false, 'errors' => $validator->errors()->messages()];

        $user = DB::table('oauth.users')->select('id', 'activation_hash')->where([['email', $email], ['email_verified_at', null]])->get()->first();

        if (!$user) return ['status' => false, 'errors' => ['email' => [$errors = Lang::get('errors.15')]]];

        do {
            $registrationCode = implode('', Helpers::randNumbers(6, 0, 9));
            $regCode = RegistrationCode::where([['code', $registrationCode], ['is_used', false], ['expired_at', '>=', date('Y-m-d H:i:s')], ['user_email', $email]])->first();
        } while (isset($regCode));
        $expiredAt = Carbon::now()->addMinutes(env('MIX_REGISTRATION_CODE_MINUTES_LIFETIME', 60));
        $regCode = RegistrationCode::create(['code' => $registrationCode, 'expired_at' => $expiredAt->format('Y-m-d H:i:s'), 'user_email' => $email]);

        self::sendActivationEmail($user->id, $email, $user->activation_hash, $registrationCode, Helpers::getClientIdFromUrl(session()->get('url.intended')));

        return ['status' => true];
    }

    /**
     * Удаление профиля пользователя
     * @param Request $request
     * @return Redirect
     */
    public function deleteProfile(Request $request)
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
        $user->save();

        Auth::logout();

        return redirect()->route('home');
    }

    /**
     * Функция отправки смс сообщения для подтверждения номера.
     * @param Request $request
     * @param String $action
     * @return array
     */

    public static function sendSms(Request $request, $action)
    {
        $phone = $request->get('phone', null);

        $validator = self::validatorUserData(['phone' => $phone]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return ['status' => false, 'errors' => $errors->messages()];
        }

        $date = Carbon::now(config('app.timezone', 'Asia/Yekaterinburg'))->addMinutes(-env('SMS_CODE_LIFETIME_MINUTES', 10))->format('Y-m-d H:i:s.u P');
        $count = DB::table('mobile.sms_verify')->where([['phone', $phone], ['action', $action], ['not_actual', false], ['create_at', '>=', $date]])->count(); //TODO

        if ($count >= env('SMS_COUNT', 5)) {
            $errors = ['phone' => [Lang::get('errors.40')['message']]];
            return ['status' => false, 'errors' => $errors];
        }

        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= rand(0, 9);
        }

        $message = Lang::get('sms.message_registration', ['code' => $code]);

        $res = Helpers::sendSms($phone, $message); //TODO
        //$res = true;

        if ($res) {
            $id = DB::table('mobile.sms_verify')->insertGetId(['phone' => $phone, 'code' => $code, 'action' => $action]);
            return ['status' => true, 'data' => ['id' => $id]]; //TODO Отключить код
        } else {
            $errors = ['phone' => [Lang::get('errors.41')['message']]];
            return ['status' => false, 'errors' => $errors];
        }
    }

    /**
     * Функция подтверждения кода смс.
     * @param Request $request
     * @param String $action
     * @return array
     */
    public static function confirmSms(ConfirmSmsRequest $request, $action)
    {
        $id = $request->get('verify_sms_code_id');
        $code = $request->get('sms_code');

        $rowSmsCode = DB::table('mobile.sms_verify')->where([['id', $id], ['action', $action]])->get()->first();
        if (!$rowSmsCode) {
            $errors = ['sms_code' => [Lang::get('errors.43')['message']]];
            return ['status' => false, 'errors' => $errors];
        }

        if ($rowSmsCode->code == $code) {
            $verifyHash = md5(microtime() . rand(100000, 999999));
            DB::table('mobile.sms_verify')->where('id', $id)->update(['verify_hash' => $verifyHash]);
            return ['status' => true, 'data' => $verifyHash];
        } else {
            $errors = ['sms_code' => [Lang::get('errors.44')['message']]];
            return ['status' => false, 'errors' => $errors];
        }
    }

    /**
     * Отправка смс регистрации.
     * @param Request $request
     * @return array
     */
    public function sendSmsRegister(Request $request)
    {
        return self::sendSms($request, 'registration');
    }

    /**
     * Подтверждение смс регистрации.
     * @param Request $request
     * @return array
     */
    public function confirmSmsRegister(Request $request)
    {
        return self::confirmSms($request, 'registration');
    }

    /**
     * Отправка смс изменеия профиля.
     * @param Request $request
     * @return array
     */
    public function sendSmsProfile(Request $request)
    {
        return self::sendSms($request, 'profile');
    }

    /**
     * Подтверждение смс изменеия профиля.
     * @param Request $request
     * @return array
     */
    public function confirmSmsProfile(Request $request)
    {
        return self::confirmSms($request, 'profile');
    }

    /**
     * Создание организации из ЕСИА.
     * @param array $data
     * @return integer
     */
    private static function updateOrCreateOrganization($data)
    { //TODO организации
        $org = DB::table('organizations.organizations')->select('id')->where('esia_id', $data['oid'])->get()->first();
        if ($org) {
            $orgId = $org->id;
            DB::table('organizations.organizations')->where('id', $orgId)->update([
                'full_name' => isset($data['fullName']) ? $data['fullName'] : null,
                'short_name' => isset($data['shortName']) ? $data['shortName'] : null,
                'ogrn' => isset($data['ogrn']) ? $data['ogrn'] : null,
                'org_type' => isset($data['type']) ? $data['type'] : null,
                //'inn' => isset($data['inn']) ? $data['inn'] : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $orgId = DB::table('organizations.organizations')->insertGetId([
                'esia_id' => $data['oid'],
                'full_name' => isset($data['fullName']) ? $data['fullName'] : null,
                'short_name' => isset($data['shortName']) ? $data['shortName'] : null,
                'ogrn' => isset($data['ogrn']) ? $data['ogrn'] : null,
                'org_type' => isset($data['type']) ? $data['type'] : null,
                //'inn' => isset($data['inn']) ? $data['inn'] : null,
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
    private static function createRefUserOrganization($userId, $orgId, $data = [])
    { //TODO организации
        $ref = DB::table('organizations.ref_user_organization')->where([['user_id', $userId], ['org_id', $orgId]])->select('id')->get()->first();
        $dbDate = [
            'email' => isset($data['email']) ? $data['email'] : null,
            'phone' => isset($data['phone']) ? $data['phone'] : null,
            'chief' => isset($data['chief']) ? $data['chief'] : false,
        ];
        if ($ref) {
            $dbDate['updated_at'] = date('Y-m-d H:i:s');
            DB::table('organizations.ref_user_organization')->where('id', $ref->id)->update($dbDate);
            return $ref->id;
        } else {
            $dbDate['org_id'] = $orgId;
            $dbDate['user_id'] = $userId;
            return DB::table('organizations.ref_user_organization')->insertGetId($dbDate);
        }
    }

    /**
     * Запрос ИНН для организации.
     * @param Request $request
     * @param SessionStore $session
     * @return null
     */
    public function bindINN(Request $request, SessionStore $session)
    { //TODO Придумать ошибки
        require_once __DIR__ . '/Plugins/ESIAAuth/EsiaAuthService.php';

        $esiaOrgID = $request->get('org_oid', null);
        $orgId = $request->get('org_id', null);
        $redirect = $request->get('redirect', null);
        if ($redirect !== null) {
            $request->session()->put('esiaRedirect', $redirect);
            $request->session()->save();
        }
        if ($orgId !== null) {
            $request->session()->put('esiaOrgId', $orgId);
            $request->session()->save();
        }

        \EsiaAuthService::get()->redirectPath = env('APP_URL') . '/profile/bind_inn';

        // проверяем наличие аутентификации в ЕСИА
        $result = \EsiaAuthService::get()->checkAuth();

        try {
            if ($result === false) {
                throw new \Exception('Ошибка проверки доступа в ЕСИА', 1);
            }

            if (strpos($result, 'http') === 0) {
                // необходимо перенаправление в ЕСИА
                header('location: ' . $result);
                exit();
            } elseif ($esiaOrgID) {
                $scope = EsiaScope::where('name', 'ext_org_info')->first();
                \EsiaAuthService::get()->orgScopes = $scope->scopes;
                $orgInfo = \EsiaAuthService::get()->getOrgInfo($esiaOrgID);
                if (!$orgInfo)
                    throw new \Exception('Получить данные организации не удалось', 5);

                $inn = isset($orgInfo['inn']) ? $orgInfo['inn'] : null;
                $org = UsersOrganization::where('esia_id', $esiaOrgID)->first();
                if (!$org)
                    throw new \Exception('Ошибка при получении организации', 7);
                $org->fill(['inn' => $inn, 'org_info' => $orgInfo]);
                $res = $org->save();
                if (!$res)
                    throw new \Exception('Ошибка при получении организации', 7);
                $redirect = $request->session()->pull('esiaRedirect');
                if ($redirect) {
                    $request->session()->save();
                    return redirect($redirect);
                } else
                    return redirect()->intended(route('home'));
            } else {
                $orgId = $request->session()->pull('esiaOrgId');
                if ($orgId) {
                    $request->session()->save();
                }

                $validator = Validator::make(['org_id' => $orgId], ['org_id' => ['required', 'numeric', Rule::exists('auth.organizations.organizations', 'id')]]);
                if ($validator->fails()) {
                    throw new \Exception('Ошибка при получении организации', 7);
                }
                $orgRow = UsersOrganization::find($orgId);
                $scope = EsiaScope::where('name', 'ext_org_info')->first();
                \EsiaAuthService::get()->orgScopes = $scope->scopes;
                $url = \EsiaAuthService::get()->getOrgInfoURL($orgRow->esia_id);
                return redirect($url);
            }
        } catch (\Exception $e) {
            return $this->getEsiaError($e);
        }
    }

    /**
     * Удаление организации пользователя.
     * @param Request $request
     * @return null
     */
    public function removeOrgUser(Request $request)
    { //TODO На будущее, может быть, сейчас не используется
        $orgId = $request->get('org_id', null);
        $userId = Auth::id();

        $validator = Validator::make(['org_id' => $orgId], ['org_id' => 'required|numeric']);
        if ($validator->fails()) {
            return ['status' => false];
        }

        $res = DB::table('organizations.ref_user_organization')->where([['user_id', $userId], ['org_id', $orgId]])->delete();
        return ['status' => $res];
    }

    public static function pushEsiaNewData($data)
    {
        try {
            $http = new GuzzleHttp\Client(array(
                'verify' => false
            )); //Чтобы не валилось с ошибкой проверки серитфиката

            $optionsRequest = [
                'form_params' => $data
            ];
            $response = $http->post(env('OUTER_STORAGE', ''), $optionsRequest);

            $content = json_decode($response->getBody());

            if (!$content || isset($content->error)) {
                throw new \Exception('', 1001);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update data in external storage');
        }
    }

    /**
     * Функция смены Email
     * @param Request $request
     * @return mixed
     */
    public function changeEmail(Request $request)
    {
        $email = $request->post('email');
        $userId = $request->session()->pull('userId');
        $validator = Validator::make(['email' => strtolower(trim($email)), 'user_id' => Auth::check() ? Auth::id() : $userId], [
            'email' => ['required', 'email', Rule::unique('users')],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);
        if ($validator->fails()) {
            if (isset($userId)) $request->session()->put('userId', $userId);
            return ['status' => false, 'errors' => $validator->errors()->messages()];
        }

        if (Auth::check())
            $user = Auth::user();
        else
            $user = User::findOrFail($userId);

        $emailsChange = EmailsChange::create(['email_new' => strtolower(trim($email)), 'activation_hash' => md5(microtime() . rand(100000, 999999)), 'user_id' => $user->id]);

        $mailToNew = UserMail::create([
            'email' => $emailsChange->email_new,
            'type' => 'activateNewEmail',
            'params' => json_encode(['email' => $emailsChange->email_new, 'key' => $emailsChange->activation_hash]),
        ]);
        $mailToOld = UserMail::create([
            'email' => $user->email,
            'type' => 'warningChangeEmailToOldEmail',
            'params' => json_encode(['email' => $emailsChange->email_new]),
        ]);
        Artisan::call('send_emails', [
            '--id' => $mailToNew->id
        ]);
        Artisan::call('send_emails', [
            '--id' => $mailToOld->id
        ]);

        return ['status' => true];
    }

    /**
     * Активация нового мыла
     * @param Request $request
     * @return mixed
     */
    public function activateNewEmail(Request $request)
    {
        $activationKey = $request->get('activation_key');
        $email = $request->get('email');
        $validator = Validator::make(['email' => $email, 'activation_key' => $activationKey], [
            'email' => ['required', 'email', Rule::exists('emails_changes', 'email_new'), Rule::unique('users')],
            'activation_key' => ['required', 'string', Rule::exists('emails_changes', 'activation_hash')],
        ]);
        if ($validator->fails()) {
            abort(404);
        }
        $emailChange = EmailsChange::where([['activation_hash', $activationKey], ['email_new', $email]])->first();
        if (!$emailChange) {
            abort(404);
        }

        $res = self::updateUser(['login' => $email, 'email' => $email, 'id' => $emailChange->user_id]);

        if (!$res) {
            abort(404);
        }

        if (!Auth::check()) {
            Auth::loginUsingId($emailChange->user_id);
        }

        return response()->view('auth.successChangeEmail');
    }

    /**
     * Получение
     * @param Request $request
     * @return mixed
     */
    public function getAccessToken(Request $request)
    {
        $accessKey = AccessKey::create(['user_id' => Auth::id(), 'access_key' => md5(microtime() . rand(100000, 999999) . Auth::id())]);
        return ['status' => true, 'data' => route('login_with_token', ['id' => Auth::id(), 'token' => $accessKey->access_key])];
    }

    /**
     * Показ страницы с вопросом о создании нового пользователя или привязке к уже существующему
     * @param array $userData
     * @param string $parentMethod
     * @param Request $request
     * @param string $redirect
     * @param bool $isOAuth
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    protected function showQuestionCreateNewUserOrAddOld($userData, $parentMethod, Request $request, $redirect = null, $isOAuth = false)
    {
        if (!$redirect) {
            $redirect = redirect()->intended()->getTargetUrl();
        }
        $route = app('router')->getRoutes()->match(app('request')->create($redirect));
        if (in_array($route->getName(), ['add_to_user', 'question_for_social_user'])) {
            $redirect = redirect()->route('home');
        }
        $request->session()->put('socialUserData', ['method' => $parentMethod, 'data' => $userData, 'redirect' => $redirect, 'isOAuth' => $isOAuth]);
        $request->session()->save();
        return redirect()->route('question_for_social_user', ['method' => $parentMethod]);
    }

    /**
     * Создание нового пользователя при ервом входе через соцсети
     * @param Request $request
     * @param string $method
     * @return \Illuminate\Http\Response
     */
    public function questionForSocialUser(Request $request, $method)
    {
        $validator = Validator::make([
            'method' => $method,
        ], [
            'method' => ['required', Rule::in(['esia', 'vk', 'fb'])],
        ]);
        if ($validator->fails()) abort(404);
        return response()->view('question_create_new_user', ['method' => $method]);
    }

    /**
     * Создание нового пользователя при ервом входе через соцсети
     * @param Request $request
     * @return mixed
     */
    public function insertNewSocialUser(Request $request)
    {
        $socialUserData = $request->session()->pull('socialUserData');
        $request->session()->save();
        $getError12Response = function () {
            $errors = Lang::get('errors.12');
            return response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
        };
        if (!$socialUserData) {
            Log::error('12: Empty user data in session');
            return $getError12Response();
        }
        switch ($socialUserData['method']) {
            case 'esia':
                $user = $this->createUserEsia($socialUserData['data']);
                break;
            case 'vk':
                $user = $this->createUserVk($socialUserData['data']);
                break;
            case 'fb':
                $user = $this->createUserFb($socialUserData['data']);
                break;
            default:
                $user = null;
        }
        if (isset($user)) {
            return $this->authorizeByUserId($user->id, $socialUserData['method'], $socialUserData['redirect'], $socialUserData['isOAuth'], $socialUserData['method']);
        } else {
            Log::error('12: Registration failed');
            return $getError12Response();
        }
    }

    /**
     * Создание нового пользователя при ервом входе через соцсети
     * @param Request $request
     * @return mixed
     */
    public function addSocialToUser(Request $request)
    {
        $socialUserData = $request->session()->pull('socialUserData');
        $request->session()->save();
        $getErrorResponse = function ($code) {
            $errors = Lang::get('errors.' . $code);
            return response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
        };
        if (!$socialUserData) {
            Log::error('18: Empty user data in session');
            return $getErrorResponse(18);
        }
        switch ($socialUserData['method']) {
            case 'esia':
                $fieldUser = 'esia_user_id';
                $updateFunction = 'updateUserEsia';
                $errorIsset = 3;
                break;
            case 'fb':
                $fieldUser = 'fb_user_id';
                $updateFunction = 'updateUserFb';
                $errorIsset = 31;
                break;
            default:
                $fieldUser = 'vk_user_id';
                $updateFunction = 'updateUserVk';
                $errorIsset = 21;
                break;
        }
        $user = Auth::user();
        $existUser = User::where($fieldUser, $socialUserData['data'][$fieldUser])->first();
        if ($existUser && $existUser->id != $user->id) {
            Log::error($errorIsset . ': Social user to be');
            return $getErrorResponse($errorIsset);
        }
        $this->$updateFunction($socialUserData['data'], $user);
        return $this->authorizeByUserId($user->id, $socialUserData['method'], $socialUserData['redirect'], $socialUserData['isOAuth'], $socialUserData['method']);
    }

    /**
     * Активация пользователя с помощью кода
     * @param Request $request
     * @return array
     */
    public function confirmRegistrationByCode(Request $request)
    {
        $code = $request->post('code');
        $email = $request->post('email');
        $clientId = $request->post('client_id', null);
        $pass = $request->post('password');
        $email = isset($email) ? strtolower($email) : $email;
        $validator = Validator::make(
            [
                'code' => $code,
                'email' => $email,
                'client_id' => $clientId
            ],
            [
                'code' => ['required', 'regex:/^[0-9]{6}$/u'],
                'email' => ['required', 'email', Rule::exists('users', 'email')],
                'client_id' => ['nullable', 'integer']
            ]
        );
        if ($validator->fails()) return ['status' => false, 'errors' => $validator->errors()->messages()];

        $registrationCode = RegistrationCode::where([['code', $code], ['is_used', false], ['expired_at', '>=', date('Y-m-d H:i:s')], ['user_email', $email]])->first();
        if (!isset($registrationCode)) return ['status' => false, 'errors' => ['code' => ['Код не найден']]];
        $registrationCode->is_used = true;
        $saveRegCodeRes = $registrationCode->save();
        $user = User::where('email', $email)->first();

        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();
        $res = Auth::loginUsingId($user->id);
        if (!$res) ['status' => false, 'errors' => ['code' => ['Ну удалось авторизовать пользователя']]];

        $client = isset($clientId) ? OauthClient::find($clientId) : null;

        self::sendSuccessRegistrationEmail($user->email, $client->name ?? null, $client->description ?? null, $email, $pass);

        return ['status' => true, 'data' => ['client_url' => $client->url_login ?? null]];
    }

    public static function generateToken($params)
    {
        $secret = config('jwt.key');
        $jwt = JWT::encode($params, $secret, 'HS256');
        return $jwt;
    }
}
