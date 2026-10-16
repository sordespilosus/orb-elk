<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

class RsaagDenyAuthorizationController extends DenyAuthorizationController
{
    /**
     * Get the authorization request from the session.
     *
     * @param Request $request
     * @return AuthorizationRequest
     * @throws \Exception
     */
    protected function getAuthRequestFromSession(Request $request)
    {
        return tap($request->session()->get('authRequest'), function ($authRequest) use ($request) {
            if (!$authRequest) {
                throw new \Exception('Authorization request was not present in the session');
            }

            if (Auth::check()) {
                $authRequest->setUser(new User($request->user()->getKey()));
            }

            $authRequest->setAuthorizationApproved(true);
        });
    }
}
