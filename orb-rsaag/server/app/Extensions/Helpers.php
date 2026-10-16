<?php

namespace App\Extensions;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Artisan, Auth, DB, Log, Route, Validator};

class Helpers
{
    /**
     * Функция проверяет что у пользователя есть определённая роль
     * @param integer $user_id
     * @param string $role
     * @return boolean
     */
    public static function checkRoleUser(int $user_id, string $role): bool
    {
        $res = DB::table('roles.ref_user_roles')
            ->leftJoin('roles.roles', 'roles.ref_user_roles.role_id', '=', 'roles.roles.id')
            ->where([['roles.roles.name', $role], ['roles.ref_user_roles.user_id', $user_id]])
            ->first();
        return (bool)$res;
    }

    /**
     * Функция записывает в БД данные о запросе к серверу
     * @param Request $request
     * @return void
     */
    public static function loggingRoute(Request $request)
    {
        $params = [
            'user_id' => Auth::check() ? Auth::id() : null,
            'url' => $request->path(),
            'method' => $request->method(),
            'get' => json_encode($request->query()),
            'header' => json_encode($request->header())
        ];

        $inputSerialized = array_map('serialize', $request->input());
        $querySerialized = array_map('serialize', $request->query());
        $postData = array_map('unserialize', array_diff($inputSerialized, $querySerialized));

        $sensitiveKeys = [
            'password', '_token', 'old_password', 'password_confirmation', 'name',
            'surname', 'patroname', 'email', 'phone', 'city', 'birthday', 'gender',
            'vk_user_id', 'esia_user_id', 'fb_user_id', 'login', 'snils', 'passport_number',
            'passport_issue_date', 'passport_issued_by', 'passport_issue_id', 'is_accepted',
            'age_period', 'email_verified_at', 'blocked', 'g-recaptcha-response', 'sms_hash',
            'verify_sms_code_id', 'sms_code'
        ];
        foreach ($sensitiveKeys as $key) {
            unset($postData[$key]);
        }
        $params['post'] = json_encode($postData);

        if ($ip = self::getClientIP()) {
            $params['ip'] = $ip;
        }
        DB::connection('editor')->table('logs.log_route')->insert($params);
    }

    /**
     * Returns client IP address
     * @access public
     * @return string
     */

    public static function getClientIP(): string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        return '';
    }

    /**
     * Функция преобразует период возраста в дату
     * @param string $age_period
     * @return string|null
     * @throws Exception
     */
    public static function getDateFromAgePeriod(string $age_period): ?string
    {
        $intervals = [
            '1' => null,
            '2' => 'P18Y',
            '3' => 'P35Y',
            '4' => 'P55Y'
        ];
        if (!array_key_exists($age_period, $intervals)) {
            return null;
        }

        $date = new DateTime();

        if ($intervals[$age_period]) {
            $date->sub(new DateInterval($intervals[$age_period]));
        }
        return $date->format('Y-m-d');
    }

    /**
     * Функция преобразует дату в период возраста
     * @param string $date
     * @return string|null
     * @throws Exception
     */
    public static function getAgePeriodFromDate(string $date): ?string
    {
        $dateNow = new DateTime();
        $birthday = new DateTime($date);
        $diff = $dateNow->diff($birthday);
        $year = $diff->y;

        if ($year < 18) {
            return '1';
        }

        if ($year < 35) {
            return '2';
        }

        if ($year < 55) {
            return '3';
        }

        if ($year >= 55) {
            return '4';
        }

        return null;
    }

    /**
     * Проверка результата рекапчи v3 в гугл.
     * @param string $token
     * @return boolean
     * @throws GuzzleException
     */
    public static function checkReCaptchaV3(string $token): bool
    {
        if (!$token) {
            return false;
        }
        $http = new GuzzleHttp\Client(array(
            'verify' => false
        ));//Чтобы не валилось с ошибкой проверки серитфиката

        $ip = self::getClientIP();

        $query = [
            'secret' => env('RECAPTCHA_SECRET_KEY_V3'),
            'response' => $token,
            'remoteip' => $ip,
        ];
        $response = $http->post('https://www.google.com/recaptcha/api/siteverify', [
            'query' => $query
        ]);

        $content = json_decode($response->getBody());

        $res = isset($content->success) && $content->success;
        if (isset($content->score)) {
            $res = $content->score > env('RECAPTCHA_SCORE', 0.5);
            if (config('app.env') != 'production' && config('app.env') != 'preproduction') {
                DB::connection('editor')->table('logs.log_recaptcha')->insert(['token' => $token, 'ip' => $ip, 'score' => $content->score]);
            }
        }

        return $res;
    }

    /**
     * Проверка результата рекапчи v2 в гугл.
     * @param string $token
     * @return boolean
     * @throws GuzzleException
     */
    public static function checkReCaptchaV2($token): bool
    {
        $http = new GuzzleHttp\Client(array(
            'verify' => false
        ));//Чтобы не валилось с ошибкой проверки сертификата
        $response = $http->get('https://www.google.com/recaptcha/api/siteverify', [
            'query' => [
                'secret' => env('RECAPTCHA_SECRET_KEY_V2'),
                'response' => $token,
                'remoteip' => self::getClientIP(),
            ]
        ]);

        $content = json_decode($response->getBody());

        return (isset($content->success) && $content->success);
    }

    /**
     * Функция отправки смс сообщения по номеру телефона.
     * @param string $phone
     * @param string $message
     * @return boolean
     * @throws GuzzleException
     */
    public static function sendSms(string $phone, string $message): bool
    {

        $phone = preg_replace('/[\(\)\s\-\+]/u', '', $phone);
        $query = [
            'msid' => $phone,
            'message' => $message,
            'naming' => env('SMS_NAMING', 'permkrai.ru'),
        ];

        try {
            $http = new GuzzleHttp\Client(array(
                'verify' => false
            ));//Чтобы не валилось с ошибкой проверки сертификата

            $url = env('SMS_SERVE') . '/SendMessage';
            $response = $http->get($url, [
                'query' => $query,
                'headers' => [
                    'Authorization' => 'Bearer ' . env('SMS_API_KEY'),
                    'Host' => env('SMS_HOST')
                ]
            ]);

            $content = simplexml_load_string($response->getBody());

            DB::connection('editor')->table('logs.log_sms_action')->insert(['action_url' => $url, 'params' => json_encode($query), 'answer' => json_encode($content)]);

            return isset($content[0]);
        } catch (Exception $e) {
            Log::error($e->getMessage(), $query);
            return false;
        }
    }

    /**
     * Функция проверки подтверждения смс.
     * @param string $phone
     * @param string $hash
     * @param string $action
     * @return boolean
     */
    public static function verifySmsHash(string $phone, string $hash, string $action): bool
    {

        $validator = Validator::make(['sms_phone' => $phone, 'sms_hash' => $hash], [
            'sms_phone' => ['required', 'max:255', 'regex:/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/'],
            'sms_hash' => ['required', 'regex:/^[0-9a-z]{32}$/u']
        ]);

        if ($validator->fails()) {
            return false;
        }
        $row_verify = DB::table('mobile.sms_verify')->where([['phone', $phone], ['verify_hash', $hash], ['action', $action], ['not_actual', false]])->get()->first();
        return isset($row_verify);
    }

    /**
     * Сброс каунтера смс.
     * @param string $phone
     * @param string $action
     * @return boolean
     */
    public static function resetSmsCount(string $phone, string $action): bool
    {
        $res = DB::table('mobile.sms_verify')->where([['phone', $phone], ['action', $action], ['not_actual', false]])->update(['not_actual' => true]);
        return (boolean)$res;
    }

    /**
     * Получение пользовательского соглашения системы.
     * @param integer $clientId
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public static function getClientAgreement(int $clientId)
    {
        $agreementRow = DB::table('public.client_agreement')
            ->where('client_id', $clientId)
            ->orderBy('id', 'desc')
            ->first();

        if ($agreementRow && is_string($agreementRow->agreement) && strlen($agreementRow->agreement) > 0)
            return $agreementRow;
        return null;
    }

    public static function getUserEsiaJson($userId)
    {
        $user = DB::table('oauth.users')->where('id', $userId)->first();
        return json_decode($user->esia_info);
    }

    /**
     * Принял ли пользователь соглашение
     * @param integer $userId
     * @param integer $agreementId
     * @return boolean
     */
    public static function checkAcceptClientAgreement(int $userId, int $agreementId): bool
    {
        $agreementRow = DB::table('public.ref_user_client_agreement')->where([['user_id', $userId], ['client_agreement', $agreementId]])->first();
        return (bool)$agreementRow;
    }

    /**
     * Получение организаций пользователя.
     * @return array
     */
    public static function getUserOrg(): array
    {
        $user = Auth::user();
        $userOrgs = DB::table('organizations.ref_user_organization')->where('user_id', $user->getAttribute('id'))
            ->leftJoin('organizations.organizations', 'organizations.ref_user_organization.org_id', '=', 'organizations.organizations.id')
            ->select('organizations.organizations.id AS id',
                'organizations.organizations.full_name AS full_name',
                'organizations.organizations.short_name AS short_name',
                'organizations.organizations.inn AS inn')
            ->where('organizations.organizations.inn', '<>', null)
            ->get()->all();
        return $userOrgs;
    }

    /**
     * Является ли пользователь зарегистрированным в есиа
     * @return boolean
     */
    public static function isEsiaUser(): bool
    {
        $user = Auth::user();
        return !!$user->getAttribute('esia_user_id');
    }

    /**
     * Нужно ли пользователю обновить поля из есиа
     * @return boolean
     */
    public static function needEsiaFields(): bool
    {
        $user = Auth::user();
        return !$user->getAttribute('esia_info');
    }

    /**
     * Получение открытых данных пользователя
     * @return array
     */
    public static function getUserInfo(): ?array
    {
        if (!Auth::check())
            return null;

        $user = Auth::user();
        return [
            'id' => $user->id,
            'fio' => $user->fio,
            'gender' => $user->gender,
            'email' => strripos($user->email, '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN') === false ? $user->email : null
        ];
    }

    /**
     * Отправка нотификации в телегу
     * @param string $text
     * @param string $user
     * @param string $type
     * @param string $lvl
     * @return boolean
     */
    public static function noticeError(string $text, string $user, string $type, string $lvl): bool
    {
        $params = [
            'notice_user' => $user,
            'notice_text' => $text,
            'notice_type' => $type,
            'notice_level' => $lvl
        ];
        $url = 'http://uv.perm.aitne.ru/program/api/v1/monitoring/notice/add';
        $headers = [
            'monitoring-secret: ' . crypt('perm:demo', gmdate('d-m-y'))
        ];
        $ch = curl_init();
        $optArray = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $params
        ];

        curl_setopt_array($ch, $optArray);
        $result = curl_exec($ch);
        $res = true;
        if (!$result) {
            $res = false;
        }
        curl_close($ch);
        return $res;
    }

    public static function getHeaderMenu(): array
    {
        $menu = [
            [
                'name' => 'Доступные системы',
                'url' => route('home'),
                'icon' => 'initiatives',
                'active' => Route::currentRouteName() === 'home'
            ],
            [
                'name' => 'Мой профиль',
                'url' => route('profile'),
                'icon' => 'user',
                'active' => Route::currentRouteName() === 'profile'
            ],
            [
                'name' => 'О сервисе',
                'url' => route('agreement'),
                'icon' => 'help',
                'active' => Route::currentRouteName() === 'agreement'
            ]
        ];
        if (Auth::user() && self::isEsiaUser()) {
            array_splice($menu, 0, 0, [[
                'name' => 'Мои заявления',
                'url' => route('my_notifications_page'),
                'icon' => 'user',
                'active' => Route::currentRouteName() === 'my_notifications_page'
            ]]);
        }
        if (false && Auth::user() && self::checkRoleUser(Auth::user()->getAttribute('id'), 'trusted_user')) {
            array_splice($menu, 0, 0, [[
                'name' => 'Мои системы',
                'url' => route('my_clients_page'),
                'icon' => 'user',
                'active' => Route::currentRouteName() === 'my_clients_page'
            ]]);
        }

        return $menu;
    }

    public static function getDescription()
    {
        $description = DB::table('public.system_attrs')->where('key', 'description')->get()->first();
        return isset($description) ? $description->value : null;
    }

    /**
     * Преобразует строковое описание уровня учётной записи ЕСИА в числовое
     * @param string $strLevel
     * @return int
     */
    public static function stringToTrustedLevel(string $strLevel): int
    {
        switch ($strLevel) {
            case 'TRUSTED':
                return 3;
            case 'STANDART':
                return 2;
            default:
                return 1;
        }
    }

    /**
     * Отправка нотификации в телегу
     * @param string $userEmail
     * @param string $type
     * @param array $params
     * @return void
     */
    public static function sendEmail(string $userEmail, string $type, array $params): void
    {
        if (strripos($userEmail, '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN') !== false) {
            Log::error('Email is service email', ['email' => $userEmail, 'type' => $type, 'params' => $params]);
        }
        try {
            $mailId = DB::table('mailer.user_mails')->insertGetId([
                'email' => $userEmail,
                'type' => $type,
                'params' => json_encode($params),
            ]);
            Artisan::call('send_emails', [
                '--id' => $mailId
            ]);
        } catch (Exception $e) {
            Log::error('Email did not send', ['email' => $userEmail, 'type' => $type, 'params' => $params]);
        }
    }

    /**
     * Возвращает случайную последовательность рандомных положительных целых чисел
     * @access public
     * @static
     * @param int $length
     * @param int $min
     * @param int|null $max
     * @return array
     */
    public static function randNumbers(int $length = 1, int $min = 0, int $max = null): array
    {
        $max = $max ?? mt_getrandmax();
        $res = [];
        for ($i = 0; $i < $length; $i++) {
            $rnd = mt_rand($min, $max);
            if (in_array($rnd, $res)) {
                $i--;
            } else {
                $res[] = $rnd;
            }
        }
        return $res;
    }

    /**
     * Записывает событие и формирует файл для яндекс метрики
     * @access public
     * @static
     * @param integer $userId
     * @param string $target
     * @param integer $datetime
     * @return void
     */
    public static function emmitEventToYM($userId, $target, $datetime)
    {
        $fileName = 'ym_' . date('Y-m-d') . '.csv';
        $path = storage_path('ym');
        if (!file_exists($path)) {
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                Log::error('Cant create a directory', ['path' => $path]);
                return null;
            }
        }
        $filePath = "$path/$fileName";
        if (file_exists($filePath)) {
            $file = fopen($filePath, 'r');
            $events = [];
            while ($row = fgetcsv($file)) {
                $events[] = $row;
            }
            fclose($file);
            $isNew = false;
        } else {
            $events = [['UserId', 'Target', 'DateTime']];
            $isNew = true;
        }

        $events[] = [$userId, $target, $datetime];

        $file = fopen($filePath, 'w');
        foreach ($events as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
        if ($isNew) {
            chmod($filePath, 0777);
        }
    }

    /**
     * Возвращает случайную последовательность рандомных положительных целых чисел
     * @access public
     * @static
     * @param string $authRequest
     * @return int|null
     */
    public static function getClientIdFromUrl($authRequest): ?int
    {
        $clientId = null;
        if (isset($authRequest)) {
            $urlComponents = parse_url($authRequest);
            if (isset($urlComponents['query'])) {
                parse_str($urlComponents['query'], $getParams);
                if (isset($getParams['client_id'])) {
                    $client = DB::table('oauth.oauth_clients')->where('id', $getParams['client_id'])->select('name')->get()->first();
                    if (isset($client)) {
                        $clientId = $getParams['client_id'];
                    }
                }
            }
        }
        return $clientId;
    }


    public static function generateString($length): string
    {
        $letters = ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '!', '%', '/'];
        $numbers = self::randNumbers($length, 0, count($letters) - 1);
        $rndString = '';
        foreach ($numbers as $number) {
            $rndString .= $letters[$number];
        }
        return $rndString;
    }
}
