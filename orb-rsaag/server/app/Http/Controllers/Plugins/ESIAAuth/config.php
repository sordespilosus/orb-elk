<?php
/**
 * Created by PhpStorm.
 * User: kuzminykh
 * Date: 09.11.2018
 * Time: 16:05
 */

/*if (gethostname() == 'ks-web') {
    // Боевой
    $esia_hostname = 'esia.gosuslugi.ru';
    $private_key_password = 'a2NSPsfQcGh56ZScd3d7';
} else {
    // Тестовый
    $esia_hostname = 'esia-portal1.test.gosuslugi.ru';
    $private_key_password = '';
}*/

$esia_hostname = env('ESIA_HOSTNAME');
$private_key_password = env('ESIA_PRIVATE_KEY');
$config = array(
	/*
	 * Идентификатор системы-клиента (мнемоника системы в ЕСИА)
	 * XXXXNN – мнемоника региональной информационной системы,
	 * где YYYY – мнемоника регионального Участника; NN – двухзначный цифровой номер информационной системы ведомства.
	 */
	'clientId' => '019110',

	/*
	 * Адрес страницы получения кода авторизации в ЕСИА
	 */
	'esiaAuthCodeURL' => "https://{$esia_hostname}/aas/oauth2/ac",

	/*
	 * Адрес страницы получения маркера идентификации или маркера доступа в ЕСИА
	 */
	'esiaTokenURL' => "https://{$esia_hostname}/aas/oauth2/te",

	/*
	 * Адрес страницы в ЕСИА для получения персональных данных пользователя
	 */
	'esiaPersonDataURL' => "https://{$esia_hostname}/rs/prns",

	/*
	 * Путь к файлу сертификата ЕСИА для проверки авторизационного кода и маркера идентификации
	 */
	'esiaCertificate' => __DIR__ . '/cert/esia.crt',

	/*
	 * Таймаут запросов на получение кода авторизации и маркера идентификации в ЕСИА, сек.
	 */
	'esiaTimeout' => 10,

	/*
	 * Поле «субъект» используемого сертификата ЕСИА = URL ЕСИА
	 * Регулярное выражение, с которым сравнивается поле.
	 */
	'esiaSubjectName' => '(.*).gosuslugi.ru',

	/*
	 * Путь к файлу сертификата системы
	 */
	'certificate' => __DIR__ . '/cert/esia/ag_test.crt',

	/*
	 * Путь к файлу закрытого ключа системы
	 */
	'privateKey' => __DIR__ . '/cert/esia/ag_test.key',

	/*
	 * Пароль закрытого ключа системы
	 */
	'privateKeyPassword' => $private_key_password,

	/*
	 * Ссылка, по которой должен быть направлен пользователь после аутентификации
	 */
	'redirectURL' => env('APP_URL') . '/loginESIA',

    /*
     * Ссылка, по которой должен быть направлен пользователь после аутентификации (/api/v2)
     */
    'redirectURLV2' => $_GET['redirect'] ?? '',

	/*
	 * Области доступа к данным пользователя
	 */
	'userInfoScope' => 'fullname birthdate gender email mobile contacts snils id_doc',
	/*
	 * Области доступа к данным пользователя с организацией
	 */
	'userInfoScopeWithOrganization' => 'fullname birthdate gender email mobile contacts snils id_doc usr_org',

	/*
	 * Директория для формирования временных файлов подпсиси через openssl
	 */
	'tempDirectory' => sys_get_temp_dir(),

	/*
	 * Адрес страницы в ЕСИА для получения разных данных
	 */
	'esiaURL' => "https://{$esia_hostname}/",

	/*
	 * Адрес страницы в ЕСИА для получения данных организации
	 */
	'esiaOrgDataURL' => "https://{$esia_hostname}/rs/orgs",
);

