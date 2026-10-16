<?php

namespace App\Http\Controllers;

use App\{OauthClient, OauthClientsChanges};
use App\Extensions\Helpers;
use App\Http\Requests\Client\{CancelChangesRequest, GetClientAgreementRequest, RevokeAccessRequest};
use App\Http\Traits\ClientsTrait;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Support\Facades\{App, Auth, DB};
use Laravel\Passport\Passport;

class ClientsController extends Controller
{
    use ClientsTrait;

    /**
     * Получение страницы для отображения систем пользователя
     * @param Request $request
     * @return Response
     */
    public function getPage(Request $request): Response
    {
        $res = $this->getUsersClient($request);
        return response()->view('my_clients', $res['data']);
    }

    /**
     * Получение систем пользователя
     * @param Request $request
     * @return array
     */
    public function getUsersClient(Request $request): array
    {
        $user = Auth::user();
        $clientsDb = OauthClient::active()->where('user_id', $user->id)->orderBy('ord')->orderBy('name')->get()->all();
        $clients = [];
        $scope = Passport::$scopes;
        $esiaScopes = $this->getEsiaScopes();
        foreach ($clientsDb as $clientDb) {
            $client = $this->normalizeClientAttrs($clientDb, true, true, true);
            $client = $this->getClientChanges($client, $user->id);
            $clients[] = $client;
        }
        return ['status' => true, 'data' => ['clients' => $clients, 'scope' => $scope, 'esiaScopes' => $esiaScopes]];
    }

    /**
     * Функция обновляет параметры внешней системы
     * @param Request $request
     * @return array
     */
    public function setClientChanges(Request $request): array
    {
        $params = $this->getRequestParams($request);
        $validator = OauthClient::getValidatorClients($params, false);
        if ($validator->fails()) {
            return ['status' => false, 'errors' => $validator->errors()->messages()];
        }

        $user = Auth::user();

        if (isset($params['id'])) {
            $client = OauthClient::find($params['id']);
            if ($client->status === 'rejected') {
                return ['status' => false, 'form' => 'Система находится в недопустимом статусе'];
            }
            if ($client->user_id !== $user->id) {
                return ['status' => false, 'form' => 'Вы не владелец этой системы'];
            }
            $oldChanges = $this->getLastChanges($params['id'], $user->id);
            if ($oldChanges->changeTime) {
                return ['status' => false, 'form' => 'Уже существуют изменения на модерации'];
            } //TODO нужно чтобы не делать множество изменений, хотя система это может
            $clientAttrs = $this->normalizeClientAttrs(OauthClient::find($params['id']), true, false);
            $clientAttrs['esia_scope'] = $clientAttrs['client_hits_esia_scope'];
            $clientAttrs['name_client'] = $clientAttrs['name'];
            $diffChanges = array_udiff_assoc($params, $oldChanges->changesSum, $clientAttrs, function ($newChange, $oldChange) {
                if (is_array($newChange)) {
                    $newElements = array_diff($newChange, $oldChange);
                    $oldElements = array_diff($oldChange, $newChange);
                    return count(array_merge($newElements, $oldElements));
                } else {
                    return ($newChange == $oldChange) ? 0 : 1;
                }
            });
            unset($diffChanges['scope_required_str'], $diffChanges['esia_scope_str'], $diffChanges['scope_str'], $diffChanges['redirect_str']);

            if (!count($diffChanges)) return ['status' => false, 'form' => 'Эти изменения уже запрошены или соответсвуют текущим значениям'];

            $clientChange = OauthClientsChanges::create([
                'client_id' => $params['id'],
                'user_id' => $user->id,
                'changes' => $diffChanges,
            ]);

            if (isset($clientChange)) {
                Helpers::sendEmail($user->email, 'sendChanges', [
                    'clientName' => $client->name,
                ]);
            }

            return ['status' => isset($clientChange)];
        } else {
            return $this->createOrUpdateClient($request, true);
        }
    }

    /**
     * Повторная отправка на проверку
     * @param Request $request
     * @return array
     */
    public function resendClient(Request $request): array
    {
        $params = $this->getRequestParams($request);
        $validator = OauthClient::getValidatorClients($params, true);
        if ($validator->fails()) {
            return ['status' => false, 'errors' => $validator->errors()->messages()];
        }
        $user = Auth::user();
        $client = OauthClient::find($params['id']);
        if ($client->status !== 'rejected') {
            return ['status' => false, 'errors' => ['status' => ['Система находится в недопустимом статусе']]];
        }
        if ($client->user_id !== $user->id) {
            return ['status' => false, 'errors' => ['status' => ['Вы не владелец этой системы']]];
        }

        $client = $this->createOrUpdateClient($request, false, false, true);
        $client->status = 'sent';
        $client->status_changed_at = Carbon::now();
        $res = $client->save();
        if ($res) {
            Helpers::sendEmail(Auth::user()->email, 'sendClient', [
                'clientName' => $client->name,
            ]);
        }
        return ['status' => $res];
    }

    /**
     * Отмена изменений
     * @param CancelChangesRequest $request
     * @return array
     */
    public function cancelChanges(CancelChangesRequest $request): array
    {
        $clientId = $request->get('client_id');
        $changesIds = $request->get('changes_ids');

        $user = Auth::user();
        $client = OauthClient::find($clientId);
        if ($client->user_id !== $user->id) {
            return ['status' => false, 'errors' => ['status' => ['Вы не владелец этой системы']]];
        }

        $changes = $this->getLastChanges($clientId, null, 'awaiting', $changesIds);

        $change = OauthClientsChanges::create([
            'client_id' => $clientId,
            'user_id' => $user->id,
            'changes' => $changes->changesSum,
            'accepted_status' => 'canceled',
        ]);

        if ($change) {
            $date = new DateTime();
            OauthClientsChanges::whereIn('id', $changesIds)->update([
                'accepted_status' => 'aggregated',
                'summary_id' => $change->id,
                'updated_at' => $date->format('Y-m-d H:i:s.u P') //TODO Модель не извлекается преобразования параметров может не быть
            ]);
        }
        return ['status' => isset($change)];
    }

    /**
     * @OA\Get(path="/api/v2/clients", tags={"API(V2)"}, summary="Получить доступные для входа системы",
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string", example="Портал Активный гражданин"),
     *                     @OA\Property(property="logo", type="string", nullable=true, example="data:image/x-icon;base64,AAAB"),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Тестовое описание"),
     *                     @OA\Property(property="url_site", type="string", example="http://example.com"),
     *                     @OA\Property(property="scope", type="object",
     *                         @OA\Property(property="rsaag_id", type="boolean", example=true),
     *                         @OA\Property(property="personal_data", type="boolean", example=false),
     *                         @OA\Property(property="esia_data", type="boolean", example=false),
     *                         @OA\Property(property="email", type="boolean", example=false),
     *                         @OA\Property(property="phone", type="boolean", example=false),
     *                         @OA\Property(property="esia_user_id", type="boolean", example=false),
     *                         @OA\Property(property="fb_user_id", type="boolean", example=false),
     *                         @OA\Property(property="vk_user_id", type="boolean", example=false),
     *                         @OA\Property(property="passport", type="boolean", example=false),
     *                         @OA\Property(property="esia_address", type="boolean", example=false),
     *                         @OA\Property(property="snils", type="boolean", example=false),
     *                         @OA\Property(property="organizations_user", type="boolean", example=false),
     *                         @OA\Property(property="auth_method", type="boolean", example=false)
     *                     ),
     *                     @OA\Property(property="client_hits_esia_scope", type="object",
     *                         @OA\Property(property="ext_org_info", type="boolean", example=false),
     *                         @OA\Property(property="default_data", type="boolean", example=false),
     *                         @OA\Property(property="temporary_residence_doc", type="boolean", example=false),
     *                         @OA\Property(property="drivers_licence_doc", type="boolean", example=false),
     *                         @OA\Property(property="residence_doc", type="boolean", example=false),
     *                         @OA\Property(property="birth_cert_doc", type="boolean", example=false),
     *                         @OA\Property(property="vehicles", type="boolean", example=false),
     *                         @OA\Property(property="kids", type="boolean", example=false)
     *                     ),
     *                     @OA\Property(property="revoked_agreement", type="boolean", example=false),
     *                     @OA\Property(property="agreement_updated_at", type="string", nullable=true, example="01.11.2021")
     *                 )
     *             ),
     *             @OA\Property(property="scope", type="object",
     *                 @OA\Property(property="rsaag_id", type="string", example="Доступ к открытой сессии"),
     *                 @OA\Property(property="personal_data", type="string", example="Доступ к ПД"),
     *                 @OA\Property(property="esia_data", type="string", example="Доступ к ПД из Госуслуг"),
     *                 @OA\Property(property="email", type="string", example="Доступ к Email"),
     *                 @OA\Property(property="phone", type="string", example="Доступ к номеру телефона"),
     *                 @OA\Property(property="esia_user_id", type="string", example="Доступ к ID в Госуслугах"),
     *                 @OA\Property(property="fb_user_id", type="string", example="Доступ к ID в Facebook"),
     *                 @OA\Property(property="vk_user_id", type="string", example="Доступ к ID в ВК"),
     *                 @OA\Property(property="passport", type="string", example="Доступ к паспортным данным"),
     *                 @OA\Property(property="esia_address", type="string", example="Доступ к адресу"),
     *                 @OA\Property(property="snils", type="string", example="Доступ к СНИЛС"),
     *                 @OA\Property(property="organizations_user", type="string", example="Доступ к организации"),
     *                 @OA\Property(property="auth_method", type="string", example="Доступ к методу авторизации")
     *             ),
     *             @OA\Property(property="esiaScopes", type="object",
     *                 @OA\Property(property="ext_org_info", type="object",
     *                     @OA\Property(property="id", type="integer", example=7),
     *                     @OA\Property(property="description", type="string", example="Подробные данные об организации"),
     *                     @OA\Property(property="name", type="string", example="ext_org_info")
     *                 ),
     *                 @OA\Property(property="default_data", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="description", type="string", example="Базовая информация"),
     *                     @OA\Property(property="name", type="string", example="default_data")
     *                 ),
     *                 @OA\Property(property="temporary_residence_doc", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="description", type="string", example="Данные рвп"),
     *                     @OA\Property(property="name", type="string", example="temporary_residence_doc")
     *                 ),
     *                 @OA\Property(property="drivers_licence_doc", type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="description", type="string", example="Данные водительского удостоверения"),
     *                     @OA\Property(property="name", type="string", example="drivers_licence_doc")
     *                 ),
     *                 @OA\Property(property="residence_doc", type="object",
     *                     @OA\Property(property="id", type="integer", example=4),
     *                     @OA\Property(property="description", type="string", example="Данные внж"),
     *                     @OA\Property(property="name", type="string", example="residence_doc")
     *                 ),
     *                 @OA\Property(property="birth_cert_doc", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="description", type="string", example="Данные свидетельства о рождении"),
     *                     @OA\Property(property="name", type="string", example="birth_cert_doc")
     *                 ),
     *                 @OA\Property(property="vehicles", type="object",
     *                     @OA\Property(property="id", type="integer", example=6),
     *                     @OA\Property(property="description", type="string", example="Данные транспортных средств"),
     *                     @OA\Property(property="name", type="string", example="vehicles")
     *                 ),
     *                 @OA\Property(property="kids", type="object",
     *                     @OA\Property(property="id", type="integer", example=8),
     *                     @OA\Property(property="description", type="string", example="Данные о детях"),
     *                     @OA\Property(property="name", type="string", example="kids")
     *                 )
     *             )
     *         )
     *     )
     * )
     * @return JsonResponse
     */
    public function getClients(): JsonResponse
    {
        $user = Auth::user();
        $userId = $user->getAuthIdentifier();
        
        $clientsQuery = OauthClient::active()->status('approved');

        // $isAdmin = Helpers::checkRoleUser($userId, 'admin');
        // if (!$isAdmin) {
        //     $clientsQuery->visible();
        // }
        $clientsQuery->visible();

        $clients = $clientsQuery->orderBy('id')->get()->all();
        $clientsResponse = [];

        foreach ($clients as $client) {
            $revoked = DB::table('oauth.oauth_access_tokens')
                ->where([['client_id', $client->id], ['user_id', $userId]])
                ->first();

            $agreement = $client->clientAgreements()->orderBy('id', 'desc')->first();

            $clientsResponse[] = [
                'id' => $client->id,
                'name' => $client->name,
                'logo' => $client->logo,
                'description' => $client->description,
                'url_site' => $client->url_site,
                'scope' => $this->getClientScopeMap($client),
                'client_hits_esia_scope' => $this->getClientEsiaScopeMap($client),
                'revoked_agreement' => $revoked === null ? true : (bool)$revoked->revoked,
                'agreement_updated_at' => $agreement !== null ? date('d.m.Y', strtotime($agreement->created_at)) : null,
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $clientsResponse,
            'scope' => Passport::$scopes,
            'esiaScopes' => $this->getEsiaScopes()
        ]);
    }

    private function getClientScopeMap(OauthClient $client): array
    {
        $clientScopes = isset($client->scope) ? $client->scope : [];
        $scopeMap = Passport::$scopes;

        array_walk($scopeMap, function (&$value, $key) use ($clientScopes) {
            $value = in_array($key, $clientScopes);
        });

        return $scopeMap;
    }

    private function getClientEsiaScopeMap(OauthClient $client): array
    {
        $esiaScopes = $this->getEsiaScopes();
        $clientEsiaScopes = $client->esiaScopes()->get();

        array_walk($esiaScopes, function (&$value) use ($clientEsiaScopes) {
            $value = $clientEsiaScopes->find($value['id']) !== null;
        });

        return $esiaScopes;
    }

    /**
     * @OA\Get(path="/api/v2/client/{id}", tags={"API(V2)"}, summary="Получить систему",
     *     @OA\Parameter(name="id", in="path", description="ID системы", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="name", type="string", example="ORB-RSAAG Personal Access Client"),
     *                     @OA\Property(property="secret", type="string", example="accessToken"),
     *                     @OA\Property(property="redirect", type="array", items={"rediectUrl"}),
     *                     @OA\Property(property="personal_access_client", type="boolean", example="true"),
     *                     @OA\Property(property="password_client", type="boolean", example="false"),
     *                     @OA\Property(property="revoked", type="boolean", example="false"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="url_login", type="string", example="http://example.com"),
     *                     @OA\Property(property="url_notifications", type="string", example="http://example.com"),
     *                     @OA\Property(property="url_site", type="string", example="http://example.com"),
     *                     @OA\Property(property="logo", type="string", example="data:image/x-icon;base64,AAAB"),
     *                     @OA\Property(property="need_esia", type="boolean", example="false"),
     *                     @OA\Property(property="is_hidden", type="boolean", example="false"),
     *                     @OA\Property(property="ord", type="integer", example=1),
     *                     @OA\Property(property="description", type="string", example="Тестовое описание"),
     *                     @OA\Property(property="need_organization", type="boolean", example="false"),
     *                     @OA\Property(property="scope", type="object",
     *                         @OA\Property(property="rsaag_id", type="boolean", example="true"),
     *                         @OA\Property(property="personal_data", type="boolean", example="false"),
     *                         @OA\Property(property="esia_data", type="boolean", example="false"),
     *                         @OA\Property(property="email", type="boolean", example="false"),
     *                         @OA\Property(property="phone", type="boolean", example="false"),
     *                         @OA\Property(property="esia_user_id", type="boolean", example="false"),
     *                         @OA\Property(property="fb_user_id", type="boolean", example="false"),
     *                         @OA\Property(property="vk_user_id", type="boolean", example="false"),
     *                         @OA\Property(property="passport", type="boolean", example="false"),
     *                         @OA\Property(property="esia_address", type="boolean", example="false"),
     *                         @OA\Property(property="snils", type="boolean", example="false"),
     *                         @OA\Property(property="organization_id", type="boolean", example="false"),
     *                         @OA\Property(property="auth_method", type="boolean", example="false")
     *                     ),
     *                     @OA\Property(property="only_esia", type="boolean", example="false"),
     *                     @OA\Property(property="dont_remember", type="boolean", example="false"),
     *                     @OA\Property(property="scope_required", type="object",
     *                         @OA\Property(property="rsaag_id", type="boolean", example="true"),
     *                         @OA\Property(property="personal_data", type="boolean", example="false"),
     *                         @OA\Property(property="esia_data", type="boolean", example="false"),
     *                         @OA\Property(property="email", type="boolean", example="false"),
     *                         @OA\Property(property="phone", type="boolean", example="false"),
     *                         @OA\Property(property="esia_user_id", type="boolean", example="false"),
     *                         @OA\Property(property="fb_user_id", type="boolean", example="false"),
     *                         @OA\Property(property="vk_user_id", type="boolean", example="false"),
     *                         @OA\Property(property="passport", type="boolean", example="false"),
     *                         @OA\Property(property="esia_address", type="boolean", example="false"),
     *                         @OA\Property(property="snils", type="boolean", example="false"),
     *                         @OA\Property(property="organization_id", type="boolean", example="false"),
     *                         @OA\Property(property="auth_method", type="boolean", example="false")
     *                     ),
     *                     @OA\Property(property="esia_level", type="string", example="TRUSTED"),
     *                     @OA\Property(property="status", type="string", example="sent"),
     *                     @OA\Property(property="status_changed_at", type="string", format="date-time"),
     *                     @OA\Property(property="without_oauth_notify", type="boolean", example="false"),
     *                     @OA\Property(property="auth_methods", type="array", items={"login"}),
     *                     @OA\Property(property="only_login", type="boolean", example="false"),
     *                     @OA\Property(property="reasons", type="string", example="Причина отклонения"),
     *                     @OA\Property(property="reasons_for_rejections", type="array", items={"login"}),
     *                     @OA\Property(property="update_agreement", type="boolean", example="false"),
     *                     @OA\Property(property="client_hits_esia_scope", type="object",
     *                         @OA\Property(property="ext_org_info", type="boolean", example="false"),
     *                         @OA\Property(property="default_data", type="boolean", example="false"),
     *                         @OA\Property(property="temporary_residence_doc", type="boolean", example="false"),
     *                         @OA\Property(property="drivers_licence_doc", type="boolean", example="false"),
     *                         @OA\Property(property="residence_doc", type="boolean", example="false"),
     *                         @OA\Property(property="birth_cert_doc", type="boolean", example="false"),
     *                         @OA\Property(property="vehicles", type="boolean", example="false"),
     *                         @OA\Property(property="kids", type="boolean", example="false")
     *                     ),
     *                     @OA\Property(property="agreement", type="string", example="<h1>TEST</h1>"),
     *                     @OA\Property(property="agreement_updated_at", type="string", example="09.01.2025")
     *                 ),
     *                 @OA\Property(property="scope", type="object",
     *                     @OA\Property(property="rsaag_id", type="string", example="Доступ к открытой сессии"),
     *                     @OA\Property(property="personal_data", type="string", example="Доступ к ПД"),
     *                     @OA\Property(property="esia_data", type="string", example="Доступ к ПД из Госуслуг"),
     *                     @OA\Property(property="email", type="string", example="Доступ к Email"),
     *                     @OA\Property(property="phone", type="string", example="Доступ к номеру телефона"),
     *                     @OA\Property(property="esia_user_id", type="string", example="Доступ к ID в Госуслугах"),
     *                     @OA\Property(property="fb_user_id", type="string", example="Доступ к ID в Facebook"),
     *                     @OA\Property(property="vk_user_id", type="string", example="Доступ к ID в ВК"),
     *                     @OA\Property(property="passport", type="string", example="Доступ к паспортным данным"),
     *                     @OA\Property(property="esia_address", type="string", example="Доступ к адресу"),
     *                     @OA\Property(property="snils", type="string", example="Доступ к СНИЛС"),
     *                     @OA\Property(property="organization_id", type="string", example="Доступ к организации"),
     *                     @OA\Property(property="auth_method", type="string", example="Доступ к методу авторизации")
     *                 ),
     *                 @OA\Property(property="esiaScopes", type="object",
     *                     @OA\Property(property="ext_org_info", type="object",
     *                         @OA\Property(property="id", type="integer", example=7),
     *                         @OA\Property(property="description", type="string", example="Подробные данные об организации"),
     *                         @OA\Property(property="name", type="string", example="ext_org_info")
     *                     ),
     *                     @OA\Property(property="default_data", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Базовая информация"),
     *                         @OA\Property(property="name", type="string", example="default_data")
     *                     ),
     *                     @OA\Property(property="temporary_residence_doc", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Данные рвп"),
     *                         @OA\Property(property="name", type="string", example="temporary_residence_doc")
     *                     ),
     *                     @OA\Property(property="drivers_licence_doc", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Данные водительского удостоверения"),
     *                         @OA\Property(property="name", type="string", example="drivers_licence_doc")
     *                     ),
     *                     @OA\Property(property="residence_doc", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Данные внж"),
     *                         @OA\Property(property="name", type="string", example="residence_doc")
     *                     ),
     *                     @OA\Property(property="birth_cert_doc", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Данные свидетельства о рождении"),
     *                         @OA\Property(property="name", type="string", example="birth_cert_doc")
     *                     ),
     *                     @OA\Property(property="vehicles", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Данные транспортных средств"),
     *                         @OA\Property(property="name", type="string", example="vehicles")
     *                     ),
     *                     @OA\Property(property="kids", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Данные о детях"),
     *                         @OA\Property(property="name", type="string", example="kids")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getClient(Request $request, int $id): JsonResponse
    {
        $client = OauthClient::find($id);
        $agreement = $client->clientAgreements()->orderBy('id', 'desc')->first();
        if ($agreement != null) {
            $client['agreement_updated_at'] = date('d.m.Y', strtotime($agreement->created_at));
        }
        $client = $this->normalizeClientAttrs($client, true, true, true);
        $scope = Passport::$scopes;
        $esiaScopes = $this->getEsiaScopes();

        if (!empty($client['reasons_for_rejections'])) {
            $client['reasons'] = $client['reasons_for_rejections'][0]['reasons'];
        }

        return response()->json(['status' => true, 'data' => [
            'client' => $client,
            'scope' => $scope,
            'esiaScopes' => $esiaScopes
        ]]);
    }

    /**
     * @OA\Get(path="/api/v2/client/{id}/agreement", tags={"API(V2)"}, summary="Получить пользовательское соглашение PDF",
     *     @OA\Parameter(name="id", in="path", description="ID системы", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ (PDF-файл)"),
     *     @OA\Response(response=404, description="Система не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="errors", type="string", example="Соглашение не найдено")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Если в id передано не число",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="id", type="array",
     *                     @OA\Items(example="Идентификатор должен состоять из цифр")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @param GetClientAgreementRequest $request
     * @param $id
     * @return Response|JsonResponse
     */
    public function getClientAgreement(GetClientAgreementRequest $request, $id)
    {
        $agreementRow = DB::table('public.client_agreement')
            ->where('client_id', $id)
            ->orderBy('id', 'desc')
            ->get()->first();

        if ($agreementRow) {
            return $this->renderAgreementPdf($agreementRow->agreement);
        } else {
            return response()->json(['status' => false, 'errors' => 'Соглашение не найдено'], 404);
        }
    }

    /**
     * @OA\Get(path="/api/v2/agreement", tags={"API(V2)"}, summary="Получить пользовательское соглашение PDF",
     *     @OA\Response(response=200, description="Успешный ответ (PDF-файл)"),
     *     @OA\Response(response=404, description="Соглашение не найдено",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="errors", type="string", example="Соглашение не найдено")
     *         )
     *     )
     * )
     *
     * @return Response|JsonResponse
     */
    public function getAgreement()
    {
        $agreementRow = DB::table('public.user_agreement')
            ->select('agreement')
            ->orderBy('date_publ', 'desc')
            ->first();

        if ($agreementRow) {
            return $this->renderAgreementPdf($agreementRow->agreement);
        } else {
            return response()->json(['status' => false, 'errors' => 'Соглашение не найдено'], 404);
        }
    }

    /**
     * Генерация PDF с поддержкой кириллицы.
     *
     * @param string|null $agreement
     * @return Response
     */
    private function renderAgreementPdf(?string $agreement): Response
    {
        $pdf = App::make('dompdf.wrapper');
        $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style>
                    body {
                        font-family: DejaVu Sans, sans-serif;
                        font-size: 14px;
                        line-height: 1.45;
                    }
                </style>
            </head>
            <body>' . ($agreement ?? '') . '</body>
            </html>';

        $pdf->loadHtml($html, 'UTF-8');
        return $pdf->stream();
    }

    /**
     * @OA\Get(path="/api/v2/system-description", tags={"API(V2)"}, summary="Получить описание системы",
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="string", nullable=true, example="Сервис предназначен для авторизации")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function getSystemDescription(): JsonResponse
    {
        $description = DB::table('public.system_attrs')
            ->where('key', 'description')
            ->first();

        return response()->json([
            'status' => true,
            'data' => $description ? $description->value : null,
        ]);
    }

    /**
     * @OA\Delete(path="/api/v2/client/{id}/revoke", tags={"API(V2)"}, summary="Отозвать разрешения для конкретной системы",
     *     @OA\Parameter(name="id", in="path", description="ID системы", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="true")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Если в id передано не число",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="id", type="array",
     *                     @OA\Items(example="Идентификатор должен состоять из цифр")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Если не найден пользователь",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="errors", type="string", example="Нет доступа")
     *         )
     *     )
     * )
     * @param RevokeAccessRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function revokeAccess(RevokeAccessRequest $request, $id): JsonResponse
    {
        $user = Auth::user();

        if ($user == null) {
            return response()->json(['status' => false, 'errors' => 'Нет доступа'], 403);
        }

        $userId = $user->getAuthIdentifier();

        $access = DB::table('oauth.oauth_access_tokens')
            ->where([['user_id', $userId], ['client_id', $id]])
            ->delete();

        return response()->json(['status' => $access > 0]);
    }
}
