<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Exceptions\MissingScopeException;
use League\OAuth2\Server\Exception\OAuthServerException;

class checkScopes
{
    /**
     * Проверка верных скопов клиента.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws MissingScopeException
     * @throws Exception
     */
    public function handle(Request $request, Closure $next)
    {
        $clientId = $request->get('client_id');
        $scope = $request->get('scope');
        $scope = explode(' ', $scope);
        $client = DB::table('oauth.oauth_clients')
            ->where('id', $clientId)
            ->select('scope')
            ->first();
        if (!$client) {
            throw new Exception('Invalid client');
        }
        $accessScope = json_decode($client->scope, true);
        if (!$accessScope) {
            $accessScope = [];
        }
        $diffScope = array_diff($scope, $accessScope);
        if (count($diffScope) > 0) {
            throw new MissingScopeException($diffScope);
        }
        return $next($request);
    }
}
