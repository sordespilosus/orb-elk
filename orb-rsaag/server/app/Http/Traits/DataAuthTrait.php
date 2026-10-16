<?php


namespace App\Http\Traits;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait DataAuthTrait
{
    /**
     * Получение данных, которые используется на страницах входа и регистрации
     * @return array
     */
    private function getPageAuthData(): array
    {
        $clients = DB::table('oauth.oauth_clients')
            ->select('name', 'logo', 'id', 'url_site', 'description')
            ->where([['revoked', false], ['is_hidden', '<>', true]])
            ->orderBy('ord')
            ->orderBy('name')
            ->get()
            ->all();
        $cities = DB::select('select * from dicts.get_tree_areas(in_area_lvl => 1)');

        return [
            'clients' => $clients,
            'cities' => $cities
        ];
    }

    /**
     * Получение данных о клиенте, который запрашивает вход
     * @param Request $request
     * @return array
     */
    private function getClientData(Request $request): array
    {
        $authRequest = $request->session()->get('url.intended');
        $clientName = null;
        $authMethods = ['vk', 'fb', 'login', 'esia'];
        $onlyLogin = false;
        $clientId = null;
        if (isset($authRequest)) {
            $urlComponents = parse_url($authRequest);
            if (isset($urlComponents['query'])) {
                parse_str($urlComponents['query'], $getParams);
                if (isset($getParams['client_id'])) {
                    $client = DB::table('oauth.oauth_clients')
                        ->where('id', $getParams['client_id'])
                        ->select('name', 'only_esia', 'need_esia', 'auth_methods', 'only_login')
                        ->first();
                    if (isset($client)) {
                        if ($client->only_esia) {
                            $request->session()->put('notReRegistration', true);
                            return [
                                'onlyEsia' => true
                            ];
                        }
                        $clientId = $getParams['client_id'];
                        $request->session()->put('esiaClientId', $getParams['client_id']);
                        $request->session()->save();
                        $clientName = $client->name;
                        $authMethods = $client->auth_methods ? json_decode($client->auth_methods) : $authMethods;
                        $onlyLogin = $client->only_login || (
                                in_array('login', $authMethods) && count($authMethods) === 1
                            );
                    }
                }
            }
        }

        return [
            'clientId' => $clientId,
            'clientName' => $clientName,
            'onlyLogin' => $onlyLogin,
            'authMethods' => $authMethods,
        ];
    }

    /**
     * Получение данных о методах авторизации и регистрации
     * @param Request $request
     * @return array
     */
    private function getMethodsData(Request $request): array
    {
        $authRequest = $request->session()->get('url.intended');
        $withoutMethod = null;
        $withoutRegistration = false;
        $route = app('router')->getRoutes()->match(app('request')->create($authRequest));
        if ($route->getName() === 'add_to_user') {
            $socialUserData = $request->session()->get('socialUserData');
            if (isset($socialUserData)) {
                $withoutMethod = $socialUserData['method'];
                $withoutRegistration = true;
            }
        }

        return [
            'withoutMethod' => $withoutMethod,
            'withoutRegistration' => $withoutRegistration,
        ];
    }
}
