<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Laravel\Passport\{ClientRepository, Exceptions\OAuthServerException, TokenRepository};
use Laravel\Passport\Bridge\User;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response as Psr7Response;

class AuthorizationController
{
    use HandlesOAuthErrors;

    protected $server;
    protected $response;

    public function __construct(AuthorizationServer $server, ResponseFactory $response)
    {
        $this->server = $server;
        $this->response = $response;
    }

    /**
     * Авторизация OAuth
     *
     * @throws OAuthServerException
     */
    public function authorize(
        ServerRequestInterface $psrRequest,
        Request                $request,
        ClientRepository       $clients,
        TokenRepository        $tokens
    )
    {
        return $this->withErrorHandling(function () use ($psrRequest, $request, $clients, $tokens) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);
            $user = $request->user();

            $clientRequest = $authRequest->getClient();
            $clientId = $clientRequest->getIdentifier();
            $client = $clients->find($clientId);
            $clientScopes = json_decode($client->scope_required);

            if (in_array('organizations_user', $clientScopes)) {
                $org_info = $user->esia_info->rowEsiaData->org_info;
                if (empty($org_info) || $org_info[0]->type != 'AGENCY') {
                    return redirect()->to('403');
                }
            }

            $authRequest->setUser(new User($user->getKey()));
            $authRequest->setAuthorizationApproved(true);

            return $this->convertResponse(
                $this->server->completeAuthorizationRequest($authRequest, new Psr7Response)
            );
        });
    }
}
