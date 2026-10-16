<?php

namespace App\Http\Controllers;

use App\OauthClient;
use App\Extensions\Helpers;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Validator};
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Response as Psr7Response;

class RsaagApproveAuthorizationController extends ApproveAuthorizationController
{
    public function approve(Request $request)
    {
        $authRequest = $this->getAuthRequestFromSession($request);

        $res = $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, new Psr7Response)
        );

        $clientId = $authRequest->getClient()->getIdentifier();
        if (config('app.env') == 'production' && ($clientId == 24 || $clientId == 26)) {
            return $res;
        }

        $goBackToAuthorize = function ($authRequest) {
            $scopes = $authRequest->getScopes();
            $scopesString = '';
            foreach ($scopes as $scope) {
                $scopesString .= ' ' . $scope->getIdentifier();
            }
            $scopesString = trim($scopesString);
            $url = route('passport.authorizations.authorize', [
                'client_id' => $authRequest->getClient()->getIdentifier(),
                'redirect_uri' => $authRequest->getRedirectUri(),
                'response_type' => 'code',
                'scope' => $scopesString,
                'state' => $authRequest->getState()
            ]);
            return redirect($url);
        };

        $user = Auth::user();
        $clientReq = $authRequest->getClient();
        $client = OauthClient::find($clientReq->getIdentifier());
        if (!$client || $client->status !== 'approved') {
            throw OAuthServerException::invalidClient();
        }

        $hasRequiredScopes = $user->checkScopeData($client->scope_required);
        if (count($hasRequiredScopes)) {
            return $goBackToAuthorize($authRequest);
        }

        if ($client->need_esia) {
            $scopesClient = $client->esiaScopes()->get();
            $scopes = [];
            foreach ($scopesClient as $scope) {
                if ($scope->name !== 'ext_org_info') {
                    $scopes = array_merge($scopes, $scope->scopes);
                }
            }
            $diffScope = array_diff($scopes, $user->latest_esia_scope ?? []);
            $dateUpdatedEsia = $user->getAttribute('esia_updated_at');
            if ($dateUpdatedEsia && !count($diffScope)) {
                $hasUserESIAFields = !Helpers::needEsiaFields();
                $now = Carbon::now();
                $dateUpdatedEsia = Carbon::parse($dateUpdatedEsia);
                $esiaDataLifeTime = $now->diffInMinutes($dateUpdatedEsia);
                if ($esiaDataLifeTime <= env('ESIA_EXPIRE_TIME', 60))
                    $needUpdateEsia = false;
                else {
                    $needUpdateEsia = true;
                }
                $needUpdateEsia = $needUpdateEsia || !$hasUserESIAFields;
            } else {
                $needUpdateEsia = true;
            }
        } else
            $needUpdateEsia = false;

        if ($client->need_esia && Helpers::stringToTrustedLevel($user->getEsiaLevel()) < Helpers::stringToTrustedLevel($client->esia_level)) {
            $needUpdateEsia = true;
        }

        if ($needUpdateEsia) {
            if ($client->only_esia) {
                $request->session()->put('notReRegistration', true);
            }
            $request->session()->put('esiaClientId', $client->id);
            $request->session()->put('goToOAuth', true);
            $request->session()->save();
            return redirect()->route('loginESIA');
        }

        if ($client->need_esia && (!$user->getAttribute('esia_user_id') || !$user->getAttribute('esia_info'))) {
            throw OAuthServerException::accessDenied('The profile ESIA is not attached to the profile or it is not relevant');
        }
        if ($client->need_organization) { //TODO организации
            $orgId = $request->post('org_id');
            $validator = Validator::make(['org_id' => $orgId], [
                'org_id' => 'required|integer'
            ]);
            if ($orgId && !$validator->fails()) {
                $isUserOrg = DB::table('organizations.ref_user_organization')->where([['org_id', $orgId], ['user_id', $user->getAttribute('id')]])->select('id')->get()->first();
                if (!$isUserOrg) {
                    throw OAuthServerException::invalidRequest('org_id');
                }

                $headers = $res->headers;
                $location = $headers->get('location');
                if (strripos($location, '?') === false) {
                    $location .= '?';
                } else {
                    $location .= '&';
                }
                $location .= http_build_query(['org_id' => $orgId]);
                $headers->set('location', $location);
            } else {
                return $goBackToAuthorize($authRequest);
            }
        }

        $agreement = Helpers::getClientAgreement($clientReq->getIdentifier());
        if ($agreement) {
            if (!Helpers::checkAcceptClientAgreement($user->getAttribute('id'), $agreement->id)) {
                DB::table('public.ref_user_client_agreement')
                    ->insert(['user_id' => $user->getAttribute('id'), 'client_agreement' => $agreement->id]);
            }
        }

        if ($client->dont_remember || $client->only_esia) {
            Auth::logout();
        }

        Helpers::emmitEventToYM($user->id, 'login_system_' . $client->id, time());
        $request->session()->forget('esiaClientId');
        return $res;
    }

    public function generateToken($params): string
    {
        $secret = config('jwt.key');
        return JWT::encode($params, $secret);
    }
}
