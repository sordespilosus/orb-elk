<?php

namespace App\Http\Middleware;

use App\OauthClient;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @return string
     */
    protected function redirectTo($request): string
    {
        $oauth_redirect_uri = config('app.url')
            . '/oauth/authorize?'
            . http_build_query($request->input());
        $route = route('login') . "?oauth_redirect_uri=$oauth_redirect_uri";

        $client_id = $request->input('client_id');
        $client = $client_id ? OauthClient::find($client_id) : null;
        if ($client && $client->is_hidden) {
            $route .= '&auto_esia=1';
        }

        return $route;

        $client_id = $request->input('client_id');
        $route = route('login');
        if ($client_id) {
            $client = OauthClient::find($client_id);
            $url_login = $client->url_login;
            $state = $request->input('state');
            if ($state === 'mobile') {
                $url_login = $client->redirect;
            }
            $route .= "?oauth_redirect_uri=$url_login";
        }

        Log::info('Адрес редиректа при первом запросе из внешней системы', [
            'redirectTo' => $route,
            'request' => $request->all()
        ]);
        return $route;
    }
}
