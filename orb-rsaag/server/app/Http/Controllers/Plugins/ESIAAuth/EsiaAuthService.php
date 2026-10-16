<?php

if (!defined('OPENSSL_ALGO_GOST94')) {
    define('OPENSSL_ALGO_GOST94', 11);
}

class EsiaAuthService
{
    protected static $instance;

    protected $config;
    protected $token;
    protected $lastError;
    public $redirectPath;
    public $scopes;
    public $orgScopes = [];

    /**
     * Алгоритм аутентификации в ЕСИА
     *
     * 1) Проверка аутентификации ->checkAuth
     * 2) Если возвращается строка, начинающаяся с "http", то необходимо выполнить перенаправление на этот адрес
     *    После ввода данных пользователем возвращается ответ на адрес системы $this->config['redirectURL']
     * 3) Иначе возвращается маркер идентификации
     * 4) В случае ошибки возвращается false
     */

    protected function __construct() {
        $config = null;
        require 'config.php';
        $this->config = $config;
        $this->redirectPath = $config['redirectURL'];
        if (strpos($_SERVER['REQUEST_URI'],'/api/v2/loginESIA') === 0){
            $this->redirectPath = $config['redirectURLV2'];
        }
        $this->scopes = $config['userInfoScopeWithOrganization'];
    }

    public static function get() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Проверяет аутентификацию в ЕСИА
     *
     * @return mixed - возвращает URL для получения авторизационного кода
     * или строку с маркером идентификации
     * или false при ошибке получения маркера
     */
    public function checkAuth() {
        unset($this->lastError);

        if (isset($_REQUEST['processAuthCodeResponse'])) {
            // обрабатываем ответ на запрос авторизационного кода
            $result = $this->_processAuthCodeResponse();
        } else {
            //$result = $this->_checkHasAuth();
            $result = false;
            if ($result === false) {
                $result = $this->_getAuthCodeURL();
            } else {
                $result = $this->_getToken($result);
            }
        }

        return $result;
    }

    /**
     * Обрабатывает ответ на запрос авторизационного кода
     *
     * @return mixed - возвращает false при ошибке, или строку с маркером идентификации
     */
    protected function _processAuthCodeResponse() {
        if (isset($_REQUEST['code']) && isset($_REQUEST['state'])) {
            $result = $this->_getToken($_REQUEST['code']);
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Определяет является ли последний полученный маркер маркером доступа, а не маркером идентификации
     *
     * @return bool
     */
    public function isAccessToken() {
        list($header_json, $payload_json, $signature) = explode('.', $this->token);
        $header = $this->json_base64urldecode($header_json);

        return $header['sbt'] == 'access';
    }

    protected function _getAuthParams($params = array()) {
        $state = $this->GUID();

        $_SESSION['esia_auth_state'] = $state;

        $params = array_merge(array(
            // идентификатор системы-клиента (мнемоника системы в ЕСИА);
            'client_id' => $this->config['clientId'],
            // ссылка, по которой должен быть направлен пользователь после того, как даст разрешение на проведение аутентификации
            'redirect_uri' => $this->redirectPath . (strpos($this->redirectPath, '?') === false ? '?' : '&') . 'processAuthCodeResponse=1',
            // область доступа, т.е. запрашиваемые права; для проведения аутентификации пользователя scope должен иметь значение openid
            'scope' => 'openid',
            // тип ответа, который ожидается от ЕСИА, имеет значение code, поскольку система-клиент должна получить авторизационный код
            'response_type' => 'code',
            // набор случайных символов, имеющий вид 128-битного идентификатора, генерируется по стандарту UUID
            'state' => $state,
            // время запроса авторизационного кода в формате yyyy.MM.dd HH:mm:ss Z (например, 2013.01.25 14:36:11 +0400)
            'timestamp' => date('Y.m.d H:i:s O')
        ), $params);

        // подпись запроса в формате PKCS#7 detached signature в кодировке UTF-8
        // от значений следующих параметров HTTP–запроса: scope, timestamp, client_id, state (без разделителей).
        // <client_secret> должен быть закодирован в формате base64 url safe
        $params['client_secret'] = $this->_sign("{$params['scope']}{$params['timestamp']}{$params['client_id']}{$params['state']}");

        return $params;
    }

    /**
     * Проверка наличия аутентификации в фоновом режиме
     *
     * @return mixed - возвращает false при отсутствии аутентификации или строку с авторизационным кодом
     */
    protected function _checkHasAuth() {
        $url = $this->config['esiaAuthCodeURL'];

        $params = $this->_getAuthParams(array(
            'prompt' => 'none'
        ));

        $options = array(
            CURLOPT_URL => $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($params),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => $this->config['esiaTimeout']
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        if ($result == false) {
            $this->_setError(curl_error($ch));
        }
        curl_close($ch);

        // если возвращается строка адреса перенаправления с параметром
        // error=login_required, то пользователь не аутентифицирован или
        // error=interaction_required, то пользователь не дал согласие на проведение идентификации и аутентификации
        if ($result) {
            if (preg_match('/^http/i', $result)) {
                $link = $result;
            } elseif (preg_match('/<a\s+href\s*=\s*(?|"(.*?)"|\'(.*?)\'|([^\'">\s]+))\s*\/?>/i', $result, $matches)) {
                $link = html_entity_decode($matches[1]);
            } else {
                $link = null;
            }
            parse_str(substr($link, strpos($link, '?') + 1), $result_params);
            if (!empty($result_params['error'])) {
                $this->_setError(!empty($result_params['error_description']) ? urldecode($result_params['error_description']) : $result_params['error']);
                $result = false;
            }
        }

        if ($result === ''){
            $result = false;
        }


        return $result;
    }

    /**
     * Возвращает URL для получения авторизационного кода
     *
     * @return string
     */
    protected function _getAuthCodeURL() {
        $url = $this->config['esiaAuthCodeURL'];

        $params = $this->_getAuthParams(array(
            'access_type' => 'online'
        ));

        $url .= '?' . http_build_query($params);

        return $url;
    }

    /**
     * Возвращает URL для получения авторизационного кода для запроса данных пользователя
     *
     * @return string
     */
    public function getUserInfoURL() {
        $oid = $this->getOID();

        $url = $this->config['esiaAuthCodeURL'];

        $params = $this->_getAuthParams(array(
            'scope' => $this->scopes,
            'access_type' => 'online',
            //'oid' => $oid
        ));

        $params['redirect_uri'] .= "&oid={$oid}";

        $url .= '?' . http_build_query($params);

        return $url;
    }

    /**
     * Возвращает маркер идентификации или маркер доступа
     *
     * @param array $params
     * @return bool|mixed
     */
    protected function _requestToken($params = array()) {
        $params = $this->_getAuthParams($params);

        $url = $this->config['esiaTokenURL'];

        $options = array(
            CURLOPT_POST => 1,
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => $this->config['esiaTimeout'],
            CURLOPT_POSTFIELDS => http_build_query($params)
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        if ($result === false) {
            $this->_setError(curl_error($ch));
        }
        curl_close($ch);

        // если не возвращается ответ в формате JSON, то возвращается код ошибки
        if ($result !== false && !preg_match('/^\{/', $result)) {
            $this->_setError($result);
            $result = false;
        }

        return $result;
    }

    /**
     * Возвращает маркер идентификации или маркер доступа в обмен на авторизационный код
     *
     * @param string $authCode - авторизационный код
     * @return mixed - возвращает false при ошибке, или строку с маркером идентификации
     */
    protected function _getToken($authCode) {
        $code_data = $this->_checkAuthCode($authCode);
        if (!$code_data) {
            $result = false;
        } else {
            $params = array();
            $params['code'] = $authCode;
            $params['token_type'] = 'Bearer';
            $params['grant_type'] = 'authorization_code';

            $result = $this->_requestToken($params);

            if ($result !== false) {
                $result = $this->_checkToken($result);
                $this->token = $result;
            }
        }

        return $result;
    }

    /**
     * Проверка авторизационного кода
     *
     * возвращает массив с содержанием кода вида array('header' => ..., 'payload' => ...)
     * или false при ошибке проверки
     *
     * @param string $code - строка с авторизационным кодом
     * @return array|bool
     */
    protected function _checkAuthCode($code) {
        // Разбор маркера
        list($header_json, $payload_json, $signature) = explode('.', $code);
        $header = $this->json_base64urldecode($header_json);
        $payload = $this->json_base64urldecode($payload_json);
        $signature = $this->base64urldecode($signature);

        $result = array('header' => $header, 'payload' => $payload);

        $time = time();
        // погрешность синхронизации времени в секундах
        $time_delay = 60;

        // Проверка подписи авторизационного кода
        if (false && !$this->_verifySign("{$header_json}.{$payload_json}", $signature, $header['alg'])) {
            $this->_setError('Некорректная подпись авторизационного кода');
            $result = false;
        }

        // Проверка времени выдачи ('iat'), начала действия ('nbf') и прекращения действия ('exp') маркера
        if ($time + $time_delay < $payload['nbf']) {
            $this->_setError("Текущее время меньше времени начала действия авторизационного кода: {$time} < {$payload['nbf']}");
            $result = false;
        }
        if ($time - $time_delay > $payload['exp']) {
            $this->_setError("Текущее время больше времени прекращения действия авторизационного кода: {$time} > {$payload['exp']}");
            $result = false;
        }
        if ($time + $time_delay < $payload['iat']) {
            $this->_setError("Текущее время меньше времени выдачи авторизационного кода: {$time} < {$payload['iat']}");
            $result = false;
        }

        // Проверка организации, выпустившей маркер на совпадение с URL ЕСИА
        if ($this->_checkSubjectName($payload['iss'])) {
            $this->_setError('Организация, выпустившая маркер, не совпадает с полем «субъект» в сертификате ЕСИА');
            $result = false;
        }

        // Проверка адресата маркера
        if ($payload['client_id'] != $this->config['clientId']) {
            $this->_setError('Адресат маркера не совпадает с мнемоникой ИС');
            $result = false;
        }

        return $result;
    }

    protected function _checkSubjectName($value)
    {
        return preg_match('#' . $this->config['esiaSubjectName'] . '#', $value) != 1;
    }

    /**
     * Проверка маркера идентификации или маркера доступа
     *
     * @param string $json - ответ ЕСИА в формате json
     * @return mixed - возвращает false при некорректном маркере или строку с маркером идентификации
     */
    protected function _checkToken($json) {
        $response = json_decode($json, true);

        $result = isset($response['id_token']) ? $response['id_token'] : (isset($response['access_token']) ? $response['access_token'] : null);

        if ($result === null){
            $this->_setError('Марке доступа не был предоставлен');
        }

        // Проверка идентификатора запроса
        if ($_SESSION['esia_auth_state'] !== $response['state']) {
            $result = false;
        } else {
            unset($_SESSION['esia_auth_state']);

            // Разбор маркера
            list($header_json, $payload_json, $signature) = explode('.', $result);
            $header = $this->json_base64urldecode($header_json);
            $payload = $this->json_base64urldecode($payload_json);
            $signature = $this->base64urldecode($signature);

            $time = time();
            // погрешность синхронизации времени в секундах
            $time_delay = 60;

            // Проверка идентификатора (мнемоники) ЕСИА, содержащейся в маркере идентификации
            if ($this->_checkSubjectName($payload['iss'])) {
                $this->_setError('Организация, выпустившая маркер, не совпадает с полем «субъект» в сертификате ЕСИА');
                $result = false;
            }

            // Проверка идентификатора (мнемоники) системы-клиента
            if (isset($payload['aud']) && $payload['aud'] != $this->config['clientId']) {
                $this->_setError('Адресат маркера не совпадает с мнемоникой ИС');
                $result = false;
            }

            // Проверка подписи маркера идентификации
            if (false && !$this->_verifySign("{$header_json}.{$payload_json}", $signature, $header['alg'])) {
                $this->_setError('Некорректная подпись маркера доступа');
                $result = false;
            }

            // Текущее время должно быть не позднее, чем время прекращения срока действия маркера идентификации
            if ($time - $time_delay > $payload['exp']) {
                $this->_setError("Текущее время больше времени прекращения действия маркера доступа: {$time} > {$payload['exp']}");
                $result = false;
            }
        }

        return $result;
    }

    protected function _requestData($url) {
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => $this->config['esiaTimeout'],
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$this->token}"
            )
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        if ($result === false) {
            $this->_setError(curl_error($ch));
        }
        curl_close($ch);

        // если не возвращается ответ в формате JSON, то возвращается код ошибки
        if ($result !== false && !preg_match('/^\{/', $result)) {
            $this->_setError($result);
            $result = false;
        }

        return $result;
    }

    /**
     * Извлекает oid учетной записи пользователя
     *
     * @return string
     */
    public function getOID() {
        // Разбор маркера
        list($header_json, $payload_json, $signature) = explode('.', $this->token);
        $payload = $this->json_base64urldecode($payload_json);
        $oid = isset($payload['urn:esia:sbj']) ? $payload['urn:esia:sbj']['urn:esia:sbj:oid'] : (isset($_REQUEST['oid']) ? (int)$_REQUEST['oid'] : false);
        return $oid;
    }

    /**
     * Получение дополнительных данных о пользователе
     *
     * @return array|bool - возвращает false при ошибке или массив вида
     * ['firstName' => ..., 'lastName' => ..., 'middleName' => ..., 'email' => ..., 'mobile' => ...]
     */
    public function getUserInfo() {
        // oid учетной записи пользователя
        $oid = $this->getOID();

        $url = "{$this->config['esiaPersonDataURL']}/{$oid}";

        $getData = function($url, $field) {
            // Получение данных
            $result = $this->_requestData($url);
            if ($result === false) {
                throw new Exception("Ошибка получения данных {$field}");
            }

            if (!preg_match('/^\{/', $result)) {
                throw new Exception("Ошибка формата ответа {$field}");
            }

            $result = json_decode($result, true);

            return $result;
        };

        list($header_json, $payload_json, $signature) = explode('.', $this->token);
        $payload = $this->json_base64urldecode($payload_json);

        try {
            $person_data = $getData($url, 'person_data');

            $info = array(
                'firstName' => $person_data['firstName'],
                'lastName' => $person_data['lastName'],
                'email' => null,
                'mobile' => null,
                'trusted' => 'NONE',
            );
            $optionalFields = ['middleName', 'birthDate', 'gender', 'snils', 'inn', 'citizenship', 'rIdDoc', 'updatedOn', 'verifying', 'status'];
            foreach ($optionalFields as $optionalField){
                if (isset($person_data[$optionalField])) {
                    $info[$optionalField] = $person_data[$optionalField];
                }
            }

            if (preg_match('/^(.*\s)?(email|mobile)([\s\?].*)?$/i', $payload['scope'])) { //TODO Подтверждённый
                $contacts_data = $getData("{$url}/ctts?embed=(elements)", 'contacts');
                if (isset($contacts_data['elements'])) {
                    $fields = [
                        'EML' => ['esiaField' => 'EML', 'dataField' => 'email', 'verify' => true],
                        'MBT' => ['esiaField' => 'MBT', 'dataField' => 'mobile', 'verify' => true],
                        'PHN' => ['esiaField' => 'PHN', 'dataField' => 'home_phone', 'verify' => false],
                    ];
                    foreach ($contacts_data['elements'] as $element) {
                        if (($fields[$element['type']]['verify'] && isset($element['vrfValStu']) && $element['vrfValStu'] === 'VERIFIED') || (!$fields[$element['type']]['verify'] && isset($element['verifyingValue']))){
                            $info[$fields[$element['type']]['dataField']] = $element['verifyingValue'];
                        }
                        elseif ((($fields[$element['type']]['verify'] && isset($element['vrfStu']) && $element['vrfStu'] === 'VERIFIED') || !$fields[$element['type']]['verify']) && isset($element['value'])){
                            $info[$fields[$element['type']]['dataField']] = $element['value'];
                        }
                    }
                }
            }

            if (preg_match('/^(.*\s)?(id_doc)([\s\?].*)?$/i', $payload['scope'])) { //TODO
                $contacts_data = $getData("{$url}/docs?embed=(elements)", 'id_doc');
                $info['docs'] = [];
                foreach ($contacts_data['elements'] as $element) {
                    if (isset($element['vrfStu']) && $element['vrfStu'] === 'VERIFIED'){
                        $info['docs'][strtolower($element['type'])] = $element;
                        $info['trusted'] = 'STANDART';
                    }
                }
            }

            if (preg_match('/^(.*\s)?(contacts)([\s\?].*)?$/i', $payload['scope'])) {
                $contacts_data = $getData("{$url}/addrs?embed=(elements)", 'addrs');
                $matchingType = ['PLV' => 'residence', 'PRG' => 'registration'];
                $info['address'] = [];
                foreach ($contacts_data['elements'] as $element) {
                    if (isset($matchingType[$element['type']])){
                        $info['address'][$matchingType[$element['type']]] = $element;
                    }
                }
            }

            if (preg_match('/^(.*\s)?kid_[\w]+([\s\?].*)?$/i', $payload['scope'])) {
                $kids_data = $getData("{$url}/kids?embed=(elements)", 'kids');
                $info['kids'] = $kids_data['elements'];
            }

            if (preg_match('/^(.*\s)?(vehicles)([\s\?].*)?$/i', $payload['scope'])) {
                $vehicles_data = $getData("{$url}/vhls?embed=(elements)", 'vehicles');
                $info['vehicles'] = $vehicles_data['elements'];
            }

            if (preg_match('/^(.*\s)?(usr_org)([\s\?].*)?$/i', $payload['scope'])) {
                $org_data = $getData("{$url}/roles", 'organizations');
                $info['org_info'] = [];
                foreach ($org_data['elements'] as $element){
                    if (!$element['active'])
                        continue;
                    $info['org_info'][] = $element;
                }
            }

            $scopes = explode(' ',$payload['scope']);
            $scopes = array_map(function ($val){
                $explodeScope = explode('?',$val);
                return array_shift($explodeScope);
            }, $scopes);

            if (isset($person_data['trusted']) && $person_data['trusted']){
                $info['trusted'] = 'TRUSTED';
            }

            $info['scope'] = $scopes;

        }
        catch (Exception $e) {
            $this->_setError($e->getMessage());
            $info = false;
        }

        return $info;
    }

    /**
     * Возвращает подпись в формате base64
     *
     * @param string $data
     * @param int $signAlgorithm - алгортим шифрования подписи (только для $type = null)
     * @param string $type - тип алгортима проверки подписи: 'pkcs7' - через фукнцию openssl_pkcs7_sign ; null - через функцию openssl_sign
     * @return string
     */
    protected function _sign($data, $signAlgorithm = OPENSSL_ALGO_SHA256, $type = 'pkcs7') {
        if ($type == 'pkcs7') {
            $in_file_name = tempnam($this->config['tempDirectory'], 'Sig');
            $out_file_name = tempnam($this->config['tempDirectory'], 'Sig');

            $ifh = fopen($in_file_name, 'w');
            fwrite($ifh, $data);
            fclose($ifh);

            // формирование подписи
            $result = openssl_pkcs7_sign(
                $in_file_name,
                $out_file_name,
                "file://{$this->config['certificate']}",
                array("file://{$this->config['privateKey']}", $this->config['privateKeyPassword']),
                array(),
                PKCS7_DETACHED
            );

            if ($result === false) {
                $this->_setError(openssl_error_string());
                $sign = null;
            } else {
                $ofh = fopen($out_file_name, 'r');

                $sign_base64 = '';
                fseek($ofh, 0);
                $content_type = fgets($ofh) ? fgets($ofh) : '';
                if (preg_match('/boundary="([^"]+)"/', $content_type, $matches)) {
                    $boundary = $matches[1];
                    $parts = explode("--{$boundary}", fread($ofh, filesize($out_file_name)));
                    $sig_parts = explode("\n\n", $parts[2]);
                    $sign_base64 = $sig_parts[1];
                }

                $sign = $this->base64urlencode(base64_decode(str_replace("\n", '', $sign_base64)));
                fclose($ofh);
            }

            // удаление файлов подписи
            unlink($in_file_name);
            unlink($out_file_name);
        } else {
            // получение закрытого ключа
            $private_key_id = openssl_pkey_get_private("file://{$this->config['privateKey']}");

            // формирование подписи
            $sign = null;
            $result = openssl_sign($data, $sign, $private_key_id, $signAlgorithm);

            // освобождение памяти
            openssl_free_key($private_key_id);

            if ($result === false) {
                $this->_setError(openssl_error_string());
            } else {
                $sign = $this->base64urlencode($sign);
            }
        }

        return $sign;
    }

    /**
     * Проверяет корректность подписи
     *
     * @param string $data - данные, которые были исползованы ранее для генерации подписи
     * @param string $sign - двоичная строка подписи
     * @param int $signAlgorithm - алгортим шифрования подписи (только для $type = null)
     * @param string $type - тип алгортима проверки подписи: 'pkcs7' - через фукнцию openssl_pkcs7_verify ; null - через функцию openssl_verify
     * @return bool
     */
    protected function _verifySign($data, $sign, $signAlgorithm = OPENSSL_ALGO_SHA256, $type = null) {
        if ($type == 'pkcs7') {
            // создаем временный файл для проверки подписи
            $p7_file_name = tempnam($this->config['tempDirectory'], 'p7');

            $pfh = fopen($p7_file_name, 'wb');

            // формируем заголовок pkcs7 сообщения
            $boundary = '----' . md5(microtime() . 'boundary');
            $body_headers = "MIME-Version: 1.0\n";
            $body_headers .= "Content-Type: multipart/signed; protocol=\"application/pkcs7-signature\"; micalg=sha1; boundary=\"{$boundary}\"\n\n";
            $body_headers .= "This is an S/MIME signed message\n\n";
            $body_headers .= "--{$boundary}\n";
            fputs($pfh, $body_headers);

            // записываем данные
            fputs($pfh, $data);
            fputs($pfh, "\n");
            fputs($pfh, "--{$boundary}\n");

            // формируем заголовок подписи
            $sign_headers = "Content-Type: application/pkcs7-signature; smime-type=signed-data; name=smime.p7s\n";
            $sign_headers .= "Content-Transfer-Encoding: base64\n";
            $sign_headers .= "Content-Disposition: attachment; filename=\"smime.p7s\"\n\n";
            fputs($pfh, $sign_headers);

            // записываем подпись
            fputs($pfh, chunk_split(base64_encode($sign), 64, "\n"));
            fputs($pfh, "\n--{$boundary}--\n\n");

            fclose($pfh);

            // проверка подписи
            $result = openssl_pkcs7_verify(
                $p7_file_name,
                PKCS7_BINARY | PKCS7_NOVERIFY
            );

            // удаление файлов подписи
            unlink($p7_file_name);
        } else {
            if ($signAlgorithm == 'RS256') {
                $signAlgorithm = OPENSSL_ALGO_SHA256;
            } elseif ($signAlgorithm == 'GOST3410') {
                // Алгортим ГОСТ Р 34.10-2001
                $signAlgorithm = OPENSSL_ALGO_GOST94;
            }

            // получение открытого ключа
            $pub_key_id = openssl_pkey_get_public("file://{$this->config['esiaCertificate']}");

            // проверка подписи
            $result = openssl_verify($data, $sign, $pub_key_id, $signAlgorithm);

            // освобождение памяти
            openssl_free_key($pub_key_id);
        }

        if ($result == -1) {
            $this->_setError(openssl_error_string());
        }

        return $result > 0;
    }

    protected function GUID() {
        if (function_exists('com_create_guid') === true) {
            $guid = trim(com_create_guid(), '{}');
        } else {
            $guid = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        }

        return strtolower($guid);
    }

    protected function base64urlencode($str) {
        return str_replace(array('+', '/'), array('-', '_'), rtrim(base64_encode($str), '='));
    }

    protected function base64urldecode($str) {
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $str));
    }

    protected function json_base64urldecode($str) {
        return json_decode($this->base64urldecode($str), true);
    }

    protected function _setError($message) {
        $this->lastError = $message;
        try{
            trigger_error($this->lastError, E_USER_WARNING);
        }
        catch (Exception $e) {
            $errors = ['code' => '8', 'title' => 'ESIA Error', 'message' => "Приветствуем! К сожалению, мы столкнулись с проблемой при попытке авторизовать вас.<br/>Пожалуйста, напишите нам на ag@mail.orb.ru. В письме укажите вашу электронную почту из <a class='link' href='https://www.gosuslugi.ru/'>профиля Госуслуг</a> и ФИО полностью, чтобы мы смогли быстро разобраться в ситуации.<br/>Приносим свои извинения за доставленные неудобства и надеемся на скорейшее решение вопроса!<br/>С уважением,<br/>команда Минцифры"];
            echo response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
            exit();
        }
        //
    }

    public function getLastError() {
        return isset($this->lastError) ?  $this->lastError : (isset($_REQUEST['error']) ? $_REQUEST['error'] : null);
    }

    /**
     * Возвращает URL для получения авторизационного кода для запроса данных организации
     *
     * @return string
     */
    public function getOrgInfoURL($orgId) {
        $oid = $this->getOID();

        $url = $this->config['esiaAuthCodeURL'];

        $scope = [];
        foreach ($this->orgScopes as $orgScope){
            $scope[] = "http://esia.gosuslugi.ru/{$orgScope}?org_oid={$orgId}";
        }

        $params = $this->_getAuthParams(array(
            'scope' => implode(' ', $scope),
            'access_type' => 'online',
            //'oid' => $oid
        ));

        $params['redirect_uri'] .= "&oid={$oid}&org_oid={$orgId}";

        $url .= '?' . http_build_query($params);

        return $url;
    }

    /**
     * Возвращает ИНН организации
     *
     * @return array
     */
    public function getOrgInfo($orgId) { //TODO Отрефакторить
        $result = $this->_requestData("{$this->config['esiaOrgDataURL']}/$orgId");
        if ($result === false || !preg_match('/^\{/', $result))
            return null;
        $result = json_decode($result, true);

        $getAttrs = function ($url){
            $attrs = [];
            $_res = $this->_requestData($url);
            $_res = $_res === false || !preg_match('/^\{/', $_res) ? [] : json_decode($_res, true)['elements'];
            foreach ($_res as $itemUrl){
                $item = $this->_requestData($itemUrl);
                if ($item !== false || preg_match('/^\{/', $item)){
                    $attrs[] = json_decode($item, true);
                }
            }
            return $attrs;
        };

        if (in_array('org_addrs', $this->orgScopes))
            $result['address'] = $getAttrs("{$this->config['esiaOrgDataURL']}/$orgId/addrs");
        else
            $result['address'] = [];


        $result['ctts'] = [];
        if (in_array('org_ctts', $this->orgScopes))
            $result['ctts'] = $getAttrs("{$this->config['esiaOrgDataURL']}/$orgId/ctts");
        else
            $result['ctts'] = [];

        $result['branches'] = [];
        if (in_array('org_brhs', $this->orgScopes)){
            $orgBrhs = $getAttrs("{$this->config['esiaOrgDataURL']}/$orgId/brhs");

            foreach ($orgBrhs as $branch) {
                if (in_array('org_brhs_ctts', $this->orgScopes))
                    $branch['ctts'] = $getAttrs("{$this->config['esiaOrgDataURL']}/$orgId/brhs/{$branch['brhOid']}/ctts");
                else
                    $branch['ctts'] = [];

                $branch['address'] = [];
                if (in_array('org_brhs_addrs', $this->orgScopes))
                    $branch['address'] = $getAttrs("{$this->config['esiaOrgDataURL']}/$orgId/brhs/{$branch['brhOid']}/addrs");
                else
                    $branch['address'] = [];

                $result['branches'][] = $branch;
            }
        }



        return $result;
    }

    /**
     * Редирект на выход из ЕСИА
     * @param string $redirectUrl
     * @return string
     */
    public function getUrlLogout($redirectUrl) {
        $params = [
            'client_id' => $this->config['clientId'],
            'redirect_url' => $redirectUrl,
        ];
        $url = $this->config['esiaURL'] . 'idp/ext/Logout?' . http_build_query($params);

        return $url;
    }
}
