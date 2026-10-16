<?php

namespace App\Http\Controllers;

use App\{AuthLog, OauthClient, User};
use App\Extensions\Helpers;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Requests\OAuth\{GetUserCardRequest, GetUserForMobileRequest, LogoutRequest, SetUserCardRequest};
use App\Http\Traits\OrganizationsTrait;
use Firebase\JWT\{JWT, Key};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Lang, Log, Validator};

class OauthController extends Controller
{
    use OrganizationsTrait;

    /**
     * Функция получения данных пользователя через Oauth
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function getUser(Request $request)
    {
        $user = Auth::user();
        $scope = explode(' ', $request->get('scope', 'personal_data email'));
        $getEsiaField = function ($field, $isRow = false) use ($user) {
            if ($isRow) {
                return (isset($user->esia_info->rowEsiaData) && isset($user->esia_info->rowEsiaData->$field)) ? $user->esia_info->rowEsiaData->$field : null;
            } else {
                return (isset($user->esia_info) && isset($user->esia_info->$field)) ? $user->esia_info->$field : null;
            }
        };

        $clientPassport = $user->token()->client;
        if ($clientPassport->id != 18) {
            $client = OauthClient::find($clientPassport->id); // TODO Нужно объединить Паспортного клиента с нашим
            $esiaScopes = $client->esiaScopes()->get();
        }

        $res = array('success' => true, 'user' => ['id' => $user->getAttribute('id')], 'roles' => []);
        if (in_array('personal_data', $scope)) {
            if ($request->user()->tokenCan('personal_data')) {
                $res['user'] = array_merge($res['user'], $this->getPersonalData());
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (personal_data)';
            }
        }

        $getEsiaDoc = function ($doc) use ($user) {
            return isset($user->esia_info->rowEsiaData) && isset($user->esia_info->rowEsiaData->docs) && isset($user->esia_info->rowEsiaData->docs->$doc) ? $user->esia_info->rowEsiaData->docs->$doc : null;
        };

        if (in_array('esia_data', $scope) && isset($esiaScopes)) {
            if ($request->user()->tokenCan('esia_data')) {
                $fields = [];
                $rowFields = [];
                foreach ($esiaScopes as $esiaScope) {
                    $fields = array_merge($fields, $esiaScope->fields['fields']);
                    $rowFields = array_merge($rowFields, $esiaScope->fields['rowFields']);
                }
                $res['user']['esia_data'] = [];
                if (!$request->user()->tokenCan('email')) {
                    $index = array_search('email', $fields);
                    if ($index !== false) {
                        unset($fields[$index]);
                    }
                }
                if (!$request->user()->tokenCan('phone')) {
                    $index = array_search('phone', $fields);
                    if ($index !== false) {
                        unset($fields[$index]);
                    }
                }

                if (in_array('drivers_licence_doc', $rowFields)) {
                    $res['user']['esia_data']['drivers_licence_doc'] = $getEsiaDoc('rf_driving_license');
                }
                if (in_array('birth_cert_doc', $rowFields)) {
                    $res['user']['esia_data']['birth_cert_doc'] = ($getEsiaDoc('rf_brth_cert') ?? $getEsiaDoc('old_brth_cert')) ?? $getEsiaDoc('fid_brth_cert');
                }
                if (in_array('temporary_residence_doc', $rowFields)) {
                    $res['user']['esia_data']['temporary_residence_doc'] = $getEsiaDoc('temporary_residence_doc'); //TODO уточнить точный документ
                }
                if (in_array('residence_doc', $rowFields)) {
                    $res['user']['esia_data']['residence_doc'] = $getEsiaDoc('residence_doc'); //TODO уточнить точный документ
                }

                if (!array_key_exists('kids', $res['user']['esia_data'])) {
                    $res['user']['esia_data']['kids'] = [];
                }

                foreach (['snils', 'passport', 'org_info', 'drivers_licence_doc', 'birth_cert_doc', 'temporary_residence_doc', 'address'] as $item) {
                    $index = array_search($item, $fields);
                    if ($index !== false) {
                        unset($fields[$index]);
                    }
                    $index = array_search($item, $rowFields);
                    if ($index !== false) {
                        unset($rowFields[$index]);
                    }
                }
                foreach ($fields as $field) {
                    $res['user']['esia_data'][$field] = $getEsiaField($field);
                }
                foreach ($rowFields as $field) {
                    $res['user']['esia_data'][$field] = $getEsiaField($field, true);
                }
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (esia_data)';
            }
        }

        $user_esia = Helpers::getUserEsiaJson($user->getAttribute('id'));
        if ($user_esia) {
            $res['user']['esia_snils'] = $user_esia->rowEsiaData->snils;
        }

        if (in_array('email', $scope)) {
            if ($request->user()->tokenCan('email')) {
                $res['user']['email'] = strripos($user->getAttribute('email'), '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN') === false ? $user->getAttribute('email') : null;
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (email)';
            }
        }

        if (in_array('phone', $scope)) {
            if ($request->user()->tokenCan('phone')) {
                $res['user']['phone'] = $user->getAttribute('phone');
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (phone)';
            }
        }

        if (in_array('snils', $scope)) {
            if ($request->user()->tokenCan('snils')) {
                $res['user']['snils'] = $getEsiaField('snils');
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (snils)';
            }
        }

        if (in_array('esia_address', $scope)) {
            if ($request->user()->tokenCan('esia_address')) {
                $res['user']['esia_address'] = $getEsiaField('address', true);
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (esia_address)';
            }
        }

        if (in_array('passport', $scope)) {
            if ($request->user()->tokenCan('passport')) {
                if ($pass = $getEsiaField('passport')) {
                    $pass->type = 'rf_passport';
                } elseif ($passRaw = $getEsiaDoc('fid_doc')) {
                    $pass = (object)[
                        'type' => strtolower($passRaw->type),
                        'number' => isset($passRaw->number) ? $passRaw->number : null,
                        'issue_date' => isset($passRaw->issueDate) ? date('Y-m-d', strtotime($passRaw->issueDate)) : null,
                    ];
                } elseif ($passRaw = $getEsiaDoc('frgn_pass')) {
                    $pass = (object)[
                        'type' => strtolower($passRaw->type),
                        'number' => isset($passRaw->series) && isset($passRaw->number) ? $passRaw->series . ' ' . $passRaw->number : null,
                        'issue_date' => isset($passRaw->issueDate) ? date('Y-m-d', strtotime($passRaw->issueDate)) : null,
                        'expiry_date' => isset($passRaw->expiryDate) ? date('Y-m-d', strtotime($passRaw->expiryDate)) : null,
                        'issued_by' => isset($passRaw->issuedBy) ? $passRaw->issuedBy : null,
                        'last_name' => isset($passRaw->lastName) ? $passRaw->lastName : null,
                        'first_name' => isset($passRaw->firstName) ? $passRaw->firstName : null,
                    ];
                } else {
                    $pass = null;
                }
                $res['user']['passport'] = $pass;
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (passport)';
            }
        }

        if (in_array('esia_user_id', $scope)) {
            if ($request->user()->tokenCan('esia_user_id')) {
                $res['user']['esia_user_id'] = $user->getAttribute('esia_user_id');
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (esia_user_id)';
            }
        }

        if (in_array('vk_user_id', $scope)) {
            if ($request->user()->tokenCan('vk_user_id')) {
                $res['user']['vk_user_id'] = $user->getAttribute('vk_user_id');
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (vk_user_id)';
            }
        }

        if (in_array('fb_user_id', $scope)) {
            if ($request->user()->tokenCan('fb_user_id')) {
                $res['user']['fb_user_id'] = $user->getAttribute('fb_user_id');
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (fb_user_id)';
            }
        }

        if (in_array('organizations_user', $scope)) { //TODO организации
            if ($request->user()->tokenCan('organizations_user')) {

                $orgId = $request->get('org_id');

                $validator = Validator::make(['org_id' => $orgId], [
                    'org_id' => 'nullable|integer'
                ]);
                if (!$validator->fails()) {
                    $issetEsiaScopeOrgInfo = !!$esiaScopes->where('name', 'ext_org_info')->first();
                    $issetEsiaScopeOrgInfo = !!$issetEsiaScopeOrgInfo;
                    $res['organizations'] = $this->normalizeOrgModel($this->getOrganizationsUserWithModel($orgId, true), $issetEsiaScopeOrgInfo ? 'full' : 'middle');
                } else {
                    if (!isset($res['errors'])) {
                        $res['errors'] = [];
                    }
                    $res['errors'][] = 'Invalid parameter (org_id)';
                }
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (organizations_user)';
            }
        }

        if (in_array('auth_method', $scope)) {
            if ($request->user()->tokenCan('auth_method')) {
                $res['user'] = array_merge($res['user'], $this->getLatestAuthMethod($user));
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Access denied (auth_method)';
            }
        }

        if (array_key_exists('esia_data', $res['user'])) {
            if (!array_key_exists('kids', $res['user']['esia_data']) || is_null($res['user']['esia_data']['kids'])) {
                $res['user']['esia_data']['kids'] = [];
            }
        }

        // Поднимаем 'user' на верхний уровень (сохраняя структуру)
        return array_merge($res, $res['user']);
    }

    /**
     * Данные scope personal_data
     * @return array
     * @throws \Exception
     */
    private function getPersonalData(): array
    {
        $user = Auth::user();
        $res = [];
        $res['login'] = $user->getAttribute('login');
        $res['birthday'] = $user->getAttribute('birthday');
        if ($user->getAttribute('age_period')) {
            $res['age_period'] = $user->getAttribute('age_period');
        } elseif ($user->getAttribute('birthday')) {
            $res['age_period'] = Helpers::getAgePeriodFromDate($user->getAttribute('birthday'));
        } else {
            $res['age_period'] = null;
        }
        $res['gender'] = $user->getAttribute('gender');

        $res['city'] = DB::table('dicts.d_areas')
            ->select('area_id', 'area_name', 'area_s_name')
            ->where('area_id', $user->getAttribute('city'))
            ->first();

        $res['fio'] = $user->getAttribute('fio');
        $res['name'] = $user->getAttribute('name');
        $res['surname'] = $user->getAttribute('surname');
        $res['patroname'] = $user->getAttribute('patroname');
        $res['is_accepted'] = $user->getAttribute('is_accepted');
        $res['jwt'] = isset($user['jwt']) ? $user['jwt'] : $this->generateToken(['user_id' => $user['id'], 'user_snils' => $user['snils']]);
        $res['snils'] = $user->getAttribute('snils');
        return $res;
    }

    /**
     * Данные scope roles_user
     * @return array
     */
    private function getRolesUser()
    {
        $user = Auth::user();
        return DB::table('roles.ref_user_roles')->distinct()
            ->leftJoin('roles.roles', 'roles.ref_user_roles.role_id', '=', 'roles.roles.id')
            ->where('roles.ref_user_roles.user_id', $user->getAttribute('id'))
            ->select('roles.roles.name AS name', 'roles.roles.public_name AS public_name')->get()->all();
    }

    public function generateToken($params): string
    {
        $secret = config('jwt.key');
        return JWT::encode($params, $secret);
    }

    public function decodeToken($token)
    {
        $secret = config('jwt.key');
        return JWT::decode($secret, new Key($secret, 'HS256'));
    }

    /**
     * Функция получения данных пользователя с подвохом 😉 feelsBadMan
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function getUserForMobile(GetUserForMobileRequest $request)
    {
        $login = $request->post('login');
        $password = $request->post('password');
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $scope = $request->post('scope', 'personal_data email esia_info');

        $scope = explode(' ', $scope);
        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['Invalid client']];
        }

        $scopeClient = json_decode($client->scope, true);
        if (!$scopeClient) {
            $scopeClient = [];
        }
        $diffScope = array_diff($scope, $scopeClient);
        if (count($diffScope) > 0) {
            return ['status' => false, 'errors' => ['Invalid scope(s) provided']];
        }

        if (!User::where('email', $login)->exists()) {
            return ['status' => false, 'errors' => 'Invalid emails', 'code' => 1008];
        }

        if (!Hash::check($password, User::where('email', $login)->first()->password)) {
            return ['status' => false, 'errors' => 'Invalid password', 'code' => 1009];
        }

        if (!Auth::once(['login' => $login, 'password' => $password])) {
            return ['status' => false, 'errors' => ['Invalid user credentials']];
        }

        $user = Auth::user();
        $user->setLatestAuthMethod('login');
        AuthLog::create(['user_id' => $user->id, 'auth_method' => 'form', 'client_id' => $clientId]);
        Helpers::emmitEventToYM($user->id, 'login_password', time());
        $res = [
            'success' => true,
            'user' => ['id' => $user->getAttribute('id')],
            'roles' => []
        ];
        if (in_array('personal_data', $scope)) {
            $res['user'] = array_merge($res['user'], $this->getPersonalData());
        }

        if (in_array('email', $scope)) {
            $res['user']['email'] = $user->getAttribute('email');
        }

        if (in_array('phone', $scope)) {
            $res['user']['phone'] = $user->getAttribute('phone');
        }

        if (in_array('snils', $scope)) {
            $res['user']['snils'] = $user->getAttribute('snils');
        }

        if (in_array('passport', $scope)) {
            $res['user']['passport'] = $user->getAttribute('passport');
        }

        if (in_array('esia_user_id', $scope)) {
            $res['user']['esia_user_id'] = $user->getAttribute('esia_user_id');
        }

        if (in_array('vk_user_id', $scope)) {
            $res['user']['vk_user_id'] = $user->getAttribute('vk_user_id');
        }

        if (in_array('fb_user_id', $scope)) {
            $res['user']['fb_user_id'] = $user->getAttribute('fb_user_id');
        }

        if (in_array('roles_user', $scope)) {
            $res['roles'] = $this->getRolesUser();
        }

        if (in_array('organizations_user', $scope)) { //TODO организации
            $orgId = $request->get('org_id');

            $validator = Validator::make(['org_id' => $orgId], [
                'org_id' => 'nullable|integer'
            ]);
            if (!$validator->fails()) {
                $res['organizations'] = $this->getOrganizationsUser($orgId);
            } else {
                if (!isset($res['errors'])) {
                    $res['errors'] = [];
                }
                $res['errors'][] = 'Invalid parameter (org_id)';
            }
        }

        if (in_array('auth_method', $scope)) {
            $res['user'] = array_merge($res['user'], $this->getLatestAuthMethod($user));
        }

        $agreement = Helpers::getClientAgreement($clientId);
        $user_esia = Helpers::getUserEsiaJson($user->getAttribute('id'));
        if ($user_esia) {
            $res['user']['esia_snils'] = $user_esia->rowEsiaData->snils;
        }
        if ($agreement) {
            if (!Helpers::checkAcceptClientAgreement($user->getAttribute('id'), $agreement->id)) {
                DB::table('public.ref_user_client_agreement')
                    ->insert([
                        'user_id' => $user->getAttribute('id'),
                        'client_agreement' => $agreement->id
                    ]);
            }
        }
        return $res;
    }

    /**
     * Функция получения данных для регистрации для мобилки feelsBadMan
     * @param Request $request
     * @return array
     */
    public function getRegistrationDataForMobile(Request $request)
    {
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['Invalid client']];
        }

        $cities = DB::select('select * from dicts.get_tree_areas(in_area_lvl => 1)');
        $agePeriods = [
            ['id' => 1, 'name' => 'до 18 лет'],
            ['id' => 2, 'name' => '18 – 35 лет'],
            ['id' => 3, 'name' => '35 -55 лет'],
            ['id' => 4, 'name' => 'более 55 лет'],
        ];

        $fields = UserController::validatorFields(true);
        unset(
            $fields['email_verified_at'],
            $fields['blocked'],
            $fields['passport_issue_date'],
            $fields['passport_number'],
            $fields['passport_issued_by'],
            $fields['passport_issue_id'],
            $fields['snils'],
            $fields['login'],
            $fields['fb_user_id'],
            $fields['esia_user_id'],
            $fields['vk_user_id'],
        );
        $fieldsKey = array_keys($fields);
        $fieldsKey[] = 'password_confirmation';

        return [
            'status' => true,
            'data' => [
                'cities' => $cities,
                'age_periods' => $agePeriods,
                'fields' => $fieldsKey,
                'sms_security' => env('SMS_SECURITY', true)
            ]
        ];
    }

    /**
     * Отправка смс регистрации для мобилки.
     * @param Request $request
     * @return array
     */
    public function sendSmsRegister(Request $request)
    {
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['Invalid client']];
        }
        return UserController::sendSms($request, 'registration_mobile');
    }

    /**
     * Подтверждение смс регистрации для мобилки.
     * @param Request $request
     * @return array
     */
    public function confirmSmsRegister(Request $request)
    {
        $clientId = $request->post('client_id', null);
        $clientSecret = $request->post('client_secret', null);
        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['Invalid client']];
        }
        return UserController::confirmSms($request, 'registration_mobile');
    }

    /**
     * Функция регистрации для мобилки feelsBadMan
     * @param Request $request
     * @return array
     */
    public function registrationForMobile(Request $request)
    {
        $data = $request->all();

        $client = $this->getClient($data['client_id'] ?? null, $data['client_secret'] ?? null);
        if (!$client) {
            return ['status' => false, 'errors' => ['Invalid client']];
        }

        $verified = DB::table('oauth.users')->select('email_verified_at', 'blocked')->where('email', isset($data['email']) ? $data['email'] : null)->get()->first();

        if ($verified) {
            $data['email_verified_at'] = $verified->email_verified_at;
            $data['blocked'] = $verified->blocked;
        } else {
            $data['email_verified_at'] = 'testtest';
            $data['blocked'] = false;
        }

        if (env('SMS_SECURITY', true)) {
            if (isset($data['phone']) && !Helpers::verifySmsHash($data['phone'], $data['sms_hash'], 'registration')) {
                $data['sms_hash'] = null;
            } else {
                $data['sms_hash'] = '90be854aa5e1f11d02763c9a5da0a047';
            }
        } else {
            $data['sms_hash'] = '90be854aa5e1f11d02763c9a5da0a047';
        }
        if (!Helpers::checkReCaptchaV2($data['g-recaptcha-response'])) {
            $data['g-recaptcha-response'] = null;
        }

        $validator = UserController::validatorByCreateUser($data, true);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return ['status' => false, 'errors' => $errors->messages(), 'old_data' => $data];
        }

        if (isset($data['phone']) && env('SMS_SECURITY', true)) {
            Helpers::resetSmsCount($data['phone'], 'registration_mobile');
        }

        $user = UserController::insertNewUser($data);
        $user->setLatestAuthMethod('login');
        AuthLog::create(['user_id' => $user->id, 'auth_method' => 'form', 'client_id' => $client->id]);
        Helpers::emmitEventToYM($user->id, 'login_password', time());
        $data = array_merge($data, $this->getLatestAuthMethod($user));
        return ['status' => true, 'data' => $user->getAttribute('id'), 'old_data' => $data];
    }

    /**
     * Получение клиента по id и секрету feelsBadMan
     * @param int $id
     * @param string $secret
     * @return object|null
     */
    private function getClient($id, $secret)
    {
        return DB::table('oauth.oauth_clients')
            ->where([['id', $id], ['secret', $secret], ['revoked', '<>', true]])
            ->select('id', 'scope')
            ->first();
    }

    /**
     * Получение id клиента социальной сети feelsBadMan
     * @param Request $request
     * @param string|null $social
     * @return array
     */
    public function getIdSocial(Request $request, string $social = null)
    {
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['Invalid client']];
        }

        switch ($social) {
            case 'vk':
                $res = [
                    'id' => env('VK_CLIENT_ID'),
                    'fields' => ['first_name', 'last_name', 'bdate', 'city', 'sex'],
                    'scope' => ['email']
                ];
                break;
            case  'fb':
                $res = [
                    'id' => env('FB_CLIENT_ID'),
                    'fields' => ['id', 'first_name', 'last_name', 'middle_name', 'email', 'birthday', 'gender', 'location'],
                    'scope' => ['email', 'user_birthday', 'user_gender', 'user_location']
                ];
                break;
            default:
                $res = null;
                break;
        }

        if ($res) {
            return ['status' => true, 'data' => $res];
        }
        return ['status' => false, 'errors' => ['Invalid social']];
    }

    public function resetPasswordForMobile(Request $request)
    {
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $email = strtolower($request->post('email'));

        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['Invalid client']];
        }

        $fpController = new ForgotPasswordController();
        $res = $fpController->sendResetLinkEmailOauth($email);
        return $res;
    }

    /**
     * Получение скоупа последнего метода авторизации
     * @param User $user
     * @return array
     */
    private function getLatestAuthMethod(User $user)
    {
        $existingProfiles = [];
        if ($user->esia_user_id) {
            $existingProfiles[] = 'esia';
        }
        if ($user->vk_user_id) {
            $existingProfiles[] = 'vk';
        }
        if ($user->fb_user_id) {
            $existingProfiles[] = 'fb';
        }
        return [
            'latest_auth_method' => $user->latest_auth_method,
            'latest_auth_at' => $user->latest_auth_at,
            'existing_profiles' => $existingProfiles
        ];
    }

    /**
     * Редирект на выход из ЕСИА и из профиля
     * @param Request $request
     * @return string
     */
    public function logout(LogoutRequest $request)
    {
        $params = $request->only(['client_id', 'redirect_uri', 'state']);

        $client = OauthClient::find($params['client_id']);
        $redirectUrls = explode(',', $client->redirect);
        if (!in_array($params['redirect_uri'], $redirectUrls, true)) {
            $errors = Lang::get('errors.60');
            if ($errors == 'errors.60')
                $errors = Lang::get('errors.500');
            return response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
        }

        $request->session()->put('logoutParams', $params);
        $request->session()->save();

        return redirect()->action('Auth\LoginController@beforeLogout');
    }

    /**
     * Привязка бонусной карты пользователя
     * @param SetUserCardRequest $request
     * @return array
     */
    public function setUserCard(SetUserCardRequest $request)
    {
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $userId = $request->post('user_id');
        $cardNumber = $request->post('card_number');

        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['client_id' => ['Invalid client']]];
        }

        try {
            $res = DB::selectOne('SELECT * FROM oauth.add_user_card(in_user_id => :user_id, in_card_id => :card_id)', [
                'user_id' => $userId,
                'card_id' => $cardNumber
            ]);
        } catch (\Exception $exception) {
            $res = null;
        }

        if ($res && $res->status_id > 0) {
            return $this->getUserCardData($userId);
        } else {
            return ['status' => false, 'errors' => ['card_number' => [$res ? $res->status : 'Не удалось привязать карту']]];
        }
    }

    /**
     * Получить данные карты пользователя
     * @param integer $userId
     * @return array
     */
    private function getUserCardData($userId): array
    {
        $cardRes = DB::selectOne('SELECT * FROM oauth.get_user_card(in_user_id => :user_id)', [
            'user_id' => $userId,
        ]);

        if ($cardRes) {
            return [
                'status' => true,
                'data' => [
                    'user_id' => $userId,
                    'card_number' => $cardRes->card_id,
                    'type_id' => $cardRes->type_id,
                    'type_name' => $cardRes->type_name
                ]
            ];
        }

        return ['status' => false, 'errors' => ['user_id' => ['Не удалось получить карту']]];
    }

    /**
     * Получить карту пользователя
     * @param Request $request
     * @return array
     */
    public function getUserCard(GetUserCardRequest $request)
    {
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $userId = $request->post('user_id');

        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['client_id' => ['Invalid client']]];
        }

        return $this->getUserCardData($userId);
    }

    /**
     * Удалить карту пользователя
     * @param Request $request
     * @return array
     */
    public function unsetUserCard(GetUserCardRequest $request)
    {
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $userId = $request->post('user_id');

        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['client_id' => ['Invalid client']]];
        }

        $res = DB::selectOne('SELECT * FROM oauth.delete_user_card(in_user_id => :user_id)',
            ['user_id' => $userId]
        );

        if ($res && $res->delete_user_card > 0) {
            return ['status' => true];
        }

        return ['status' => false, 'errors' => ['user_id' => ['Не удалось отвязать карту']]];
    }

    /**
     * Добавить нотификацию от внешней системы
     * @param Request $request
     * @return array
     */
    public function addUserNotices(Request $request)
    {
        $clientId = $request->post('client_id');
        $clientSecret = $request->post('client_secret');
        $userIds = $request->post('user_ids');
        $noticeTitle = $request->post('notice_title');
        $noticeText = $request->post('notice_text');
        $noticeLink = $request->post('notice_link');
        $noticeDate = $request->post('notice_date');
        $userNotices = $request->post('notices');

        $client = $this->getClient($clientId, $clientSecret);
        if (!$client) {
            return ['status' => false, 'errors' => ['client_id' => ['Invalid client']]];
        }

        if (is_string($userNotices)) {
            $userNotices = json_decode($userNotices, true);
        }

        if (is_array($userNotices)) {
            return $this->addManyUserNotices($userNotices, $client->id);
        }
        return $this->addCommonUserNotices($userIds, $client->id, $noticeTitle, $noticeText, $noticeLink, $noticeDate);
    }

    /**
     * Добавить нотификации в БД
     * @param array $params
     * @return array
     */
    private function addUserNoticesToDB($params)
    {
        try {
            $res = DB::selectOne('SELECT * FROM notices.add_notice_rsaag(in_params => :params)',
                ['params' => json_encode($params)]
            );
            return ['status' => isset($res) && $res->add_notice_rsaag > 0];
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), $exception->getTrace());
            return ['status' => false, 'error' => 'Saving failed'];
        }
    }

    /**
     * Добавить множество нотификаций от внешней системы
     * @param array $notices
     * @param int $systemId
     * @return array
     */
    private function addManyUserNotices($notices, $systemId)
    {
        $validator = Validator::make([
            'notices' => $notices
        ], [
            'notices' => ['required', 'array'],
            'notices.*' => ['required', 'array'],
            'notices.*.user_id' => ['required', 'integer'],
            'notices.*.notice_title' => ['nullable', 'string'],
            'notices.*.notice_text' => ['required', 'string'],
            'notices.*.notice_link' => ['required', 'url'],
            'notices.*.notice_date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return ['status' => false, 'errors' => $validator->errors()->messages()];
        }

        $params = array_map(function ($item) use ($systemId) {
            return [
                'user_id' => $item['user_id'],
                'system_id' => $systemId,
                'notice_name' => $item['notice_title'] ?? null,
                'notice_text' => $item['notice_text'],
                'notice_link' => $item['notice_link'],
                'notice_date' => date('Y-m-d H:i:s', strtotime($item['notice_date'])),
            ];
        }, $notices);

        return $this->addUserNoticesToDB($params);
    }

    /**
     * Добавить однотипные нотификаций от внешней системы
     * @param int|array $userIds
     * @param int $systemId
     * @param string $noticeTitle
     * @param string $noticeText
     * @param string $noticeLink
     * @param string $noticeDate
     * @return array
     */
    private function addCommonUserNotices($userIds, $systemId, $noticeTitle, $noticeText, $noticeLink, $noticeDate)
    {
        if (!is_array($userIds)) {
            $userIds = is_string($userIds) ? json_decode($userIds, true) : [$userIds];
            if (!is_array($userIds)) {
                $userIds = [$userIds];
            }
        }

        $validator = Validator::make([
            'user_ids' => $userIds,
            'notice_title' => $noticeTitle,
            'notice_text' => $noticeText,
            'notice_link' => $noticeLink,
            'notice_date' => $noticeDate
        ], [
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'integer'],
            'notice_title' => ['nullable', 'string'],
            'notice_text' => ['required', 'string'],
            'notice_link' => ['required', 'url'],
            'notice_date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return ['status' => false, 'errors' => $validator->errors()->messages()];
        }

        $params = [];

        foreach ($userIds as $userId) {
            $params[] = [
                'user_id' => $userId,
                'system_id' => $systemId,
                'notice_name' => $noticeTitle,
                'notice_text' => $noticeText,
                'notice_link' => $noticeLink,
                'notice_date' => date('Y-m-d H:i:s', strtotime($noticeDate)),
            ];
        }

        return $this->addUserNoticesToDB($params);
    }
}
