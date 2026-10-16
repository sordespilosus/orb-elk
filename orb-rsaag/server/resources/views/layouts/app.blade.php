<?php

use Illuminate\Support\Facades\Auth;

preg_match("/(MSIE|Opera|Firefox|Chrome|Version|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon|Trident|YaBrowser|Safari)(?:\/| )([0-9.]+)/",
    $_SERVER['HTTP_USER_AGENT'], $browser_info);

$browser = isset($browser_info[1]) ? $browser_info[1] : false;
$version = isset($browser_info[2]) ? $browser_info[2] : false;

$oldie = ($browser == 'MSIE' && intval($version) < 12);
$oldff = ($browser == 'Firefox' && intval($version) < 52);
$oldopera = ($browser == 'Opera' && intval($version) < 36);
$oldchrome = ($browser == 'Chrome' && intval($version) < 56);
$oldYaBrowser = ($browser == 'YaBrowser' && intval($version) < 17);
$oldSafari = ($browser == 'Safari' && intval($version) < 10);
$ie = ($browser == 'Trident');
$oldBrowser = false;

if ($oldie || $oldff || $oldopera || $oldchrome || $oldYaBrowser || $oldSafari || $ie) {
    if (empty($_COOKIE['old_browser_message'])) {
        $oldBrowser = true;
    } else {
        try {
            $now = time();
            $cookie_time = $_COOKIE['old_browser_message'];
            if ((($now - $cookie_time / 1000) / 60) > 10) {
                $oldBrowser = true;
            }
        } catch (Exception $e) {
            $oldBrowser = true;
        }
    }
}

$userLinks = [
    [
        'name' => 'Выйти',
        'url' => route('logout'),
        'icon' => 'exit'
    ]
];
if (Auth::check() && \App\Extensions\Helpers::checkRoleUser(Auth::id(), 'admin')) {
    array_splice($userLinks, 0, 0, [[
        'name' => 'Управление системой',
        'url' => route('admin'),
        'icon' => 'options'
    ]]);
}
$headerLinks = \App\Extensions\Helpers::getHeaderMenu();
$mobileUserLinks = array_merge($headerLinks, $userLinks);

$vuex_data = [
    'isSmsSecurity' => env('SMS_SECURITY', true),
    'needRecaptcha' => env('NEED_RECAPTCHA', false),
    'recaptchaVersion' => env('RECAPTCHA_V', 3),
    'userLinks' => $userLinks,
    'headerLinks' => $headerLinks,
    'mobileUserLinks' => $mobileUserLinks,

    'user' => (object)\App\Extensions\Helpers::getUserInfo(),
    'description' => !Auth::check() ? \App\Extensions\Helpers::getDescription() : null,

    'appCircle' => config('app.env'),
    'alarmText' => env('ALARM_TEXT'),
    'alarmCookieName' => env('ALARM_COOKIE_NAME')
];

$browsers = [
    'chrome' => [
        'name' => 'Google Chrome',
        'link' => 'http://www.google.com/chrome?hl=ru',
        'image' => '/images/browsers/chrome.png',
    ],

    'ms_edge' => [
        'name' => 'Microsoft Edge',
        'link' => 'https://www.microsoft.com/ru-ru/windows/microsoft-edge',
        'image' => '/images/browsers/ms-edge.png',
    ],

    'firefox' => [
        'name' => 'Firefox',
        'link' => 'http://www.mozilla.com/firefox/',
        'image' => '/images/browsers/firefox.png'
    ],

    'yandex' => [
        'name' => 'Yandex Browser',
        'link' => 'https://browser.yandex.ru/',
        'image' => '/images/browsers/yandex-browser.png'
    ],

    'safari' => [
        'name' => 'Safari',
        'link' => 'https://support.apple.com/downloads/safari',
        'image' => '/images/browsers/safari.png'
    ]
];

/*@todo: cities, agePeriods*/

?>
    <!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}" id="csrf">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Региональный сервис аутентификации и авторизации гражданина Оренбургской области</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <script src="{{ mix('js/manifest.js') }}" defer></script>
    <script src="{{ mix('js/vendor.js') }}" defer></script>
    <script src="{{ mix('js/app.js') }}" defer></script>
</head>

<body>

<noscript>
    <div class="browser-error">
        <div class="browser-error__wrapper">
            <div class="browser-error__title">Уважаемый пользователь, Ваш браузер заблокировал работу сценариев!</div>
            <div class="browser-error__content">
                <div class="browser-error__text">Для обеспечения полноценной работы портала «Региональный сервис
                    аутентификации и авторизации гражданина» необходима поддержка JavaScript. Сведения о том, как
                    узнать, поддерживает ли браузер JavaScript, и как включить сценарии, см. в справке браузера.
                </div>
            </div>
        </div>
    </div>
</noscript>

@if($oldBrowser)
    <div class="browser-error">
        <div class="browser-error__wrapper">
            <div class="browser-error__title">Уважаемый пользователь, Ваша версия браузера устарела!</div>
            <div class="browser-error__content">
                <div class="browser-error__text">Для обеспечения наибольшей безопасности, скорости, комфорта и
                    удовольствия при работе с порталом «Региональный сервис аутентификации и авторизации гражданина»,
                    обновите версию Вашего браузера. Полноценная работа портала «Региональный сервис аутентификации и
                    авторизации гражданина» в текущей версии браузера не поддерживается.
                </div>
                <div class="browser-error__text">Поддерживаемые браузеры:</div>
                <div class="browser-error__browser-list">
                    @foreach($browsers as $browser)

                        <a
                            href="{{$browser['link']}}"
                            class="browser-error__browser-item">
                            <img
                                src="{{$browser['image']}}"
                                alt="{{$browser['name']}}"
                                class="browser-error__browser-image">

                            <div
                                class="browser-error__browser-name">{{$browser['name']}}</div>
                        </a>

                    @endforeach
                </div>
            </div>
        </div>
    </div>

@else
    <div id="app">
        <app :vuex-data="{{ json_encode($vuex_data) }}">
            @yield('content')
        </app>
    </div>
@endif
@if(config('app.env') == 'production')
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript"> (function (d, w, c) {
            (w[c] = w[c] || []).push(function () {
                try {
                    w.yaCounter56536234 = new Ya.Metrika({
                        id: 56536234,
                        clickmap: true,
                        trackLinks: true,
                        accurateTrackBounce: true,
                        webvisor: true
                    });
                } catch (e) {
                }
            });
            var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () {
                n.parentNode.insertBefore(s, n);
            };
            s.type = "text/javascript";
            s.async = true;
            s.src = "https://mc.yandex.ru/metrika/watch.js";
            if (w.opera == "[object Opera]") {
                d.addEventListener("DOMContentLoaded", f, false);
            } else {
                f();
            }
        })(document, window, "yandex_metrika_callbacks"); </script>
    <noscript>
        <div><img src="https://mc.yandex.ru/watch/56536234" style="position:absolute; left:-9999px;" alt=""/></div>
    </noscript>
    <!-- /Yandex.Metrika counter -->
@endif
<!-- Yandex.Metrika counter 2 -->
<script type="text/javascript">
    (function (m, e, t, r, i, k, a) {
        m[i] = m[i] || function () {
            (m[i].a = m[i].a || []).push(arguments)
        };
        m[i].l = 1 * new Date();
        k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
    })
    (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

    const counter = {{ config('app.env') == 'production' ? env('YA_COUNTER', 47067942) : env('YA_COUNTER', 67213183)}}

    ym(counter, "init", {
        clickmap: true,
        trackLinks: true,
        accurateTrackBounce: true,
        trackHash: true,
        @if(\Illuminate\Support\Facades\Auth::check())
        userParams: {
            UserID: '{{ \Illuminate\Support\Facades\Auth::id() }}'
        }
        @endif
    });
    @if(\Illuminate\Support\Facades\Auth::check())
    ym(counter, 'setUserID', '{{ \Illuminate\Support\Facades\Auth::id() }}');
    @endif
</script>

<noscript>
    <div><img src="https://mc.yandex.ru/watch/47067942" style="position:absolute; left:-9999px;" alt=""/></div>
</noscript>
<!-- /Yandex.Metrika counter -->
</body>

</html>
