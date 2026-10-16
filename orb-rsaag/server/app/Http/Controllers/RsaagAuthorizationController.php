<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rsaag\AuthorizeMobileWithtSocialRequest;
use App\OauthClient;
use App\Extensions\Helpers;
use Carbon\Carbon;
use Illuminate\Http\{Request, Response};
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\{ClientRepository, Passport, TokenRepository};
use Laravel\Passport\Http\Controllers\AuthorizationController;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;

class RsaagAuthorizationController extends AuthorizationController
{
    /**
     * Authorize a client to access the user's account.
     *
     * @param ServerRequestInterface $psrRequest
     * @param Request $request
     * @param ClientRepository $clients
     * @param TokenRepository $tokens
     * @return Response
     */
    public function authorize(ServerRequestInterface $psrRequest, Request $request, ClientRepository $clients, TokenRepository $tokens)
    {
        return $this->withErrorHandling(function () use ($psrRequest, $request, $clients, $tokens) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            $clientRequest = $authRequest->getClient();
            $clientId = $clientRequest->getIdentifier();
            $user = $request->user();
            $token = Auth::check() ? $tokens->findValidToken($user, $clients->find($authRequest->getClient()->getIdentifier())) : null;
            $request->session()->put('esiaClientId', $clientId);
            $request->session()->save();
            $scopes = $this->parseScopes($authRequest);
            $allScopes = Passport::$scopes;
            if (config('app.env') == 'production' && ($clientId == 24 || $clientId == 26)) {
                $token = Auth::check() ? $tokens->findValidToken($user, $clients->find($authRequest->getClient()->getIdentifier())) : null;
                if (Auth::check() && ($token && $token->scopes === collect($scopes)->pluck('id')->all())) {
                    return $this->approveRequest($authRequest, $user);
                }

                $request->session()->put('authRequest', $authRequest);
                $request->session()->save();

                if ($clientId == 24) {
                    $request->session()->put('notReRegistration', true);
                    $request->session()->put('goToOAuth', true);
                    return redirect()->route('loginESIA');
                } else {
                    $client = OauthClient::find($clientId);
                    $client->setShort();

                    return $this->response->view('passport::authorize', [
                        'client' => $client,
                        'scopes' => $scopes,
                        'request' => $request,
                        'agreement' => null,
                        'organizations' => [],
                        'needUpdateEsia' => false,
                        'hasRequiredScopes' => [],
                        'hasTrustedLevel' => true,
                        'allScopes' => $allScopes,
                    ]);
                }
            }

            $client = OauthClient::find($clientRequest->getIdentifier());
            if ($client->status !== 'approved') {
                throw OAuthServerException::invalidClient();
            }
            $client->setShort();

            $hasRequiredScopes = Auth::check() ? $user->checkScopeData($client->scope_required) : [];

            if (Auth::check() && $client->getAttribute('need_esia')) {
                $_scopesClient = $client->esiaScopes()->get();
                $scopesClient = [];
                foreach ($_scopesClient as $scope) {
                    if ($scope->name !== 'ext_org_info') {
                        $scopesClient = array_merge($scopesClient, $scope->scopes);
                    }
                }
                $diffScope = array_diff($scopesClient, $user->latest_esia_scope ?? []);
                $dateUpdatedEsia = $user->getAttribute('esia_updated_at');
                if ($dateUpdatedEsia && !count($diffScope)) {
                    $hasUserESIAFields = !Helpers::needEsiaFields();
                    $now = Carbon::now();
                    $dateUpdatedEsia = Carbon::parse($dateUpdatedEsia);
                    $esiaDataLifeTime = $now->diffInMinutes($dateUpdatedEsia);
                    if ($esiaDataLifeTime <= env('ESIA_EXPIRE_TIME', 60)) {
                        $needUpdateEsia = false;
                    } else {
                        $needUpdateEsia = true;
                    }
                    $needUpdateEsia = $needUpdateEsia || !$hasUserESIAFields;
                } else {
                    $needUpdateEsia = true;
                }
            } else {
                $needUpdateEsia = false;
            }

            $hasTrustedLevel = !(Auth::check()
                && $client->need_esia
                && Helpers::stringToTrustedLevel($user->getEsiaLevel()) < Helpers::stringToTrustedLevel($client->esia_level));

            if (Auth::check()
                && !count($hasRequiredScopes)
                && $hasTrustedLevel
                && (($token && $token->scopes === collect($scopes)->pluck('id')->all())
                    || (count($scopes) == 1 && reset($scopes)->id === 'rsaag_id')
                    || $client->without_oauth_notify)
                && !$needUpdateEsia && !$client->getAttribute('only_esia')
            ) {
                if ($client->dont_remember) {
                    Auth::logout();
                }
                Helpers::emmitEventToYM($user->id, 'login_system_' . $client->id, time());
                return $this->approveRequest($authRequest, $user);
            }

            $request->session()->put('authRequest', $authRequest);
            $request->session()->save();

            if ($client->getAttribute('only_esia')) {
                if (Auth::check() && !$needUpdateEsia && !count($hasRequiredScopes)) {
                    return redirect()->route('passport.authorizations.approve');
                } else if (!Auth::check() || $needUpdateEsia) {
                    $request->session()->put('notReRegistration', true);
                    $request->session()->put('goToOAuth', true);
                    return redirect()->route('loginESIA');
                }
            }
            $clientEsiaScopes = $client->esiaScopes()->get();
            if ($clientEsiaScopes->count()) {
                $esiaScope = [];
                foreach ($clientEsiaScopes as $clientEsiaScope) {
                    $esiaScope[$clientEsiaScope->name] = $clientEsiaScope->description;
                }
                $client->esia_scope = $esiaScope;
            } else {
                $client->esia_scope = null;
            }
            return $this->response->view('passport::authorize', [
                'client' => $client,
                'scopes' => $scopes,
                'request' => $request,
                'agreement' => Helpers::getClientAgreement($client->id),
                'organizations' => Auth::check() ? Helpers::getUserOrg() : [],
                'needUpdateEsia' => $needUpdateEsia,
                'hasRequiredScopes' => $hasRequiredScopes,
                'hasTrustedLevel' => $hasTrustedLevel,
                'allScopes' => $allScopes,
            ]);
        });
    }


    public function authorizeMobileWithSocial(
        ServerRequestInterface            $psrRequest,
        AuthorizeMobileWithtSocialRequest $request,
        ClientRepository                  $clients,
        string                            $social
    )
    {
        $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
        $clientRequest = $authRequest->getClient();

        $request->session()->put('mobileClientId', $clientRequest->getIdentifier());
        $request->session()->put('authRequest', $authRequest);
        $request->session()->put('isAuthorizeMobileWithSocial', true);

        switch ($social) {
            case 'vk':
            {
                return redirect()->route('login_from_vk');
            }
            case 'esia':
            {
                $queryParams = $psrRequest->getQueryParams();
                return redirect()->to('/oauth/authorize?' . http_build_query($queryParams));
            }
            case 'fb':
            {
                return redirect()->route('login_from_fb');
            }
        }
        abort(404);
    }
}
