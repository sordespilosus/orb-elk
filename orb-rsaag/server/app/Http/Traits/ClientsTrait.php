<?php

namespace App\Http\Traits;

use App\{
    ClientAgreement,
    EsiaScope,
    OauthClient,
    OauthClientsChanges,
    ReasonsForRejection,
    RefEsiaScopesOauthClient,
    User
};
use App\Extensions\Helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Validator};
use Illuminate\Validation\Rule;
use Laravel\Passport\Passport;


trait ClientsTrait
{
    /**
     * Функция отдаёт внешние системы
     * @param OauthClient $client
     * @param bool $redirectUrlToArray Преобразовать строку с редиректами в массив
     * @param bool $needScopesMapping Пеобразовать скоупы в удобный для вывода вид
     * @param bool $reasonsRejecting Добавить причины отклонения
     * @param array $hiddenFields Поля, которые не попадут в вывод
     * @return array
     */
    protected function normalizeClientAttrs(
        OauthClient $client,
        bool        $redirectUrlToArray = false,
        bool        $needScopesMapping = true,
        bool        $reasonsRejecting = false,
        array       $hiddenFields = []
    ): array
    {
        $clientAttrs = $client->attributesToArray();
        foreach (['created_at', 'updated_at', 'status_changed_at'] as $item) {
            $clientAttrs[$item] = date('d.m.Y H:i', strtotime($clientAttrs[$item]));
        }
        if ($reasonsRejecting) {
            $reasons = $client->clientsReasonsForRejection()->where('status', 'rejected')->orderBy('created_at', 'desc')->get()->all();
            $clientAttrs['reasons_for_rejections'] = array_map(function ($item) {
                return [
                    'reasons' => $item->reasons,
                    'created_at' => date('d.m.Y H:i ', strtotime($item->created_at))
                ];
            }, $reasons);
        }
        $scope = Passport::$scopes;
        $esiaScopes = $this->getEsiaScopes();
        $scopeClient = isset($clientAttrs['scope']) ? $clientAttrs['scope'] : [];
        $scopeRequiredClient = isset($client->scope_required) ? $client->scope_required : [];
        $clientMappingScope = $scope;
        $clientMappingScopeRequired = $scope;
        $esiaClientScopes = $client->esiaScopes()->get();
        $clientMappingEsiaScope = $esiaScopes;
        if ($needScopesMapping) {
            array_walk($clientMappingScope, function (&$val, $key) use ($scopeClient) {
                $val = in_array($key, $scopeClient);
            });
            array_walk($clientMappingScopeRequired, function (&$val, $key) use ($scopeRequiredClient) {
                $val = in_array($key, $scopeRequiredClient);
            });
            array_walk($clientMappingEsiaScope, function (&$val, $key) use ($esiaClientScopes) {
                $hitScope = $esiaClientScopes->find($val['id']);
                $val = isset($hitScope);
            });
        } else {
            $clientMappingScope = $scopeClient;
            $clientMappingScopeRequired = $scopeRequiredClient;
            $clientMappingEsiaScope = array_map(function ($item) {
                return $item->name;
            }, $esiaClientScopes->all());
        }
        $clientAttrs['update_agreement'] = false;
        $clientAttrs['scope'] = $clientMappingScope;
        $clientAttrs['scope_required'] = $clientMappingScopeRequired;
        $agreement = $client->clientAgreements()->orderBy('id', 'desc')->first();
        $clientAttrs['client_hits_esia_scope'] = $clientMappingEsiaScope;
        $clientAttrs['agreement'] = isset($agreement) ? $agreement->agreement : null;
        $clientAttrs['agreement_updated_at'] = isset($agreement) ? date('d.m.Y', strtotime($agreement->created_at)) : null;
        $clientAttrs['auth_methods'] = isset($clientAttrs['auth_methods']) ? json_decode($clientAttrs['auth_methods']) : ['login', 'esia', 'vk', 'fb'];

        if ($redirectUrlToArray) {
            $clientAttrs['redirect'] = explode(',', $clientAttrs['redirect']);
        }

        foreach ($hiddenFields as $field) {
            unset($clientAttrs[$field]);
        }

        return $clientAttrs;
    }

    /**
     * Формировка именнованного массива со скоупами ЕСИА
     * @return array
     */
    protected function getEsiaScopes(): array
    {
        $esiaScopesRows = EsiaScope::all();
        $esiaScopes = [];
        foreach ($esiaScopesRows as $esiaScopesRow) {
            $esiaScopes[$esiaScopesRow->name] = ['id' => $esiaScopesRow->id, 'description' => $esiaScopesRow->description, 'name' => $esiaScopesRow->name];
        }
        return $esiaScopes;
    }

    /**
     * Функция достаёт данные системы из запроса
     * @param Request $request
     * @param bool $isAdmin
     * @return array
     */
    protected function getRequestParams(Request $request, bool $isAdmin = false): array
    {
        $data = [];
        $data['id'] = $request->get('id');
        $data['name_client'] = $request->get('name');
        $data['redirect'] = $request->get('redirect');
        //$data['redirect'] = explode(',', $data['redirect_str']);
        $data['url_login'] = $request->get('url_login', null);
        $data['url_notifications'] = $request->get('url_notifications');
        $data['need_esia'] = $request->get('need_esia', false);
        $data['logo'] = $request->get('logo');
        $data['url_site'] = $request->get('url_site');
        $data['is_hidden'] = $request->get('is_hidden', false);
        $data['description'] = $request->get('description');
        $data['agreement'] = $request->get('agreement');
        $data['update_agreement'] = $request->get('update_agreement', false);
        $data['agreement_updated_at'] = $request->get('agreement_updated_at');
        $data['need_organization'] = $request->get('need_organization', false);
        $data['only_esia'] = $request->get('only_esia', false);
        $data['dont_remember'] = $request->get('dont_remember', false);
        $data['scope_str'] = $request->get('scope');
        $data['scope'] = json_decode($data['scope_str']);
        $data['esia_scope_str'] = $request->get('client_hits_esia_scope');
        $data['esia_scope'] = json_decode($data['esia_scope_str']);
        $data['scope_required_str'] = $request->get('scope_required');
        $data['scope_required'] = json_decode($data['scope_required_str']);
        $data['esia_level'] = $request->get('esia_level');
        $data['without_oauth_notify'] = $request->get('without_oauth_notify');
        $data['auth_methods'] = $request->get('auth_methods');
        $data['only_login'] = $request->get('only_login');
        if ($isAdmin) {
            $data['status'] = $request->get('status');
            $data['ord'] = $request->get('ord');
            $data['reasons'] = $request->get('reasons');
        }
        return $data;
    }

    /**
     * Функция обновляет параметры внешней системы
     * @param Request $request
     * @param bool $toCreate СОздание новой системы
     * @param bool $isAdmin Действие производит администратор
     * @param bool $rawOutput Не валидировать ввод и отдать клиента, а не массив
     * @param array|null $data Данные для изменений
     * @return array|OauthClient|\Laravel\Passport\Client
     */
    protected function createOrUpdateClient(
        Request $request,
        bool    $toCreate = false,
        bool    $isAdmin = false,
        bool    $rawOutput = false,
        array   $data = null
    )
    {
        if (!$data) {
            $data = $this->getRequestParams($request, $isAdmin);
        }

        if (!$rawOutput) {
            $validator = OauthClient::getValidatorClients($data, !$toCreate);

            if ($validator->fails()) {
                return ['status' => false, 'errors' => $validator->errors()->messages()];
            }
        }
        $newClientData = [];
        $fields = ['url_login', 'url_notifications', 'need_esia', 'only_esia', 'dont_remember', 'logo', 'url_site', 'is_hidden', 'description', 'need_organization', 'scope', 'scope_required', 'esia_level', 'ord', 'without_oauth_notify', 'auth_methods', 'only_login'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $newClientData[$field] = $data[$field];
            }
        }
        if (isset($newClientData['auth_methods'])) {
            $newClientData['auth_methods'] = json_encode($newClientData['auth_methods']);
        }
        if (isset($data['name_client'])) {
            $newClientData['name'] = $data['name_client'];
        }
        if (isset($data['redirect'])) {
            $newClientData['redirect'] = implode(',', $data['redirect']);
        }

        if ($toCreate) {
            $client = Passport::client();
            $newClientData = array_merge($newClientData, [
                'secret' => str_random(40),
                'personal_access_client' => false,
                'password_client' => false,
                'revoked' => false,
                'user_id' => Auth::id(),
            ]);
            foreach (['scope', 'scope_required'] as $item) {
                if (isset($newClientData[$item])) {
                    $newClientData[$item] = json_encode($newClientData[$item]);
                }
            }
        } else {
            $client = OauthClient::find($data['id']);
            if (!$client) {
                return ['status' => false, 'errors' => true];
            }
        }

        $createReasons = false;
        if ($isAdmin && isset($data['status']) && $data['status'] !== $client->status) {
            $newClientData['status'] = $data['status'];
            if ($toCreate) {
                $newClientData['status_changed_at'] = Carbon::now();
            }
            if (!$rawOutput) {
                $validator = Validator::make([
                    'status' => $data['status'],
                    'reasons' => $data['reasons']
                ], [
                    'status' => ['required', Rule::in(['rejected', 'approved', 'sent'])],
                    'reasons' => 'nullable|string',
                ]);
                $validator->sometimes('reasons', 'required|string', function ($input) {
                    return $input->status === 'rejected';
                });
                if ($validator->fails()) {
                    return ['status' => false, 'errors' => $validator->errors()->messages()];
                }
            }

            $createReasons = true;
        }


        $client->forceFill($newClientData);
        $res = $client->save();
        $id = $client->id;

        if ($createReasons) {
            $reasonsObject = ReasonsForRejection::create([
                'user_id' => Auth::id(),
                'client_id' => $client->id,
                'reasons' => $data['reasons'] ?? null,
                'status' => $data['status'],
            ]);
        }

        if (!$toCreate) {
            $tokens = Passport::token()
                ->whereRevoked(0)
                ->where([['client_id', $id], ['expires_at', '>', Carbon::now()]])
                ->get();
            foreach ($tokens as $token) {
                $token->revoke();
            }
        }

        if (!empty($data['update_agreement']) || $toCreate) {
            $this->createClientAgreement($data['agreement'] ?? '', $id, $data['agreement_updated_at'] ?? null);
        }

        if (isset($data['esia_scope'])) {
            $this->createClientEsiaScopes($data['esia_scope'], $id, true);
        }
        if (!$rawOutput && $toCreate) {
            $owner = User::find($client->user_id);
            Helpers::sendEmail($owner->email, 'sendClient', [
                'clientName' => $client->name,
            ]);
        }

        if (!$rawOutput) {
            return ['status' => (bool)$res, 'data' => $res ? $client : null];
        } else {
            return $client;
        }
    }

    /**
     * Обновление пользовтаельского соглашения системы
     * @param string $agreement
     * @param integer $clientId
     * @param string|null $agreementUpdatedAt
     * @return void
     */
    protected function createClientAgreement(string $agreement, int $clientId, ?string $agreementUpdatedAt = null)
    {
        $agreement = strip_tags($agreement, '<h1><h2><h3><h4><h5><h6><div><p><ul><ol><li><b><i>');
        $clientAgreement = ['client_id' => $clientId, 'agreement' => $agreement];
        $createdAt = $this->parseAgreementUpdatedAt($agreementUpdatedAt);
        if ($createdAt !== null) {
            $clientAgreement['created_at'] = $createdAt;
        }
        ClientAgreement::create($clientAgreement);
    }

    /**
     * Преобразование даты редакции соглашения к формату БД.
     * @param string|null $agreementUpdatedAt
     * @return string|null
     */
    protected function parseAgreementUpdatedAt(?string $agreementUpdatedAt): ?string
    {
        if (!$agreementUpdatedAt) {
            return null;
        }

        $format = strpos($agreementUpdatedAt, '.') !== false ? 'd.m.Y' : 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $agreementUpdatedAt);
        $errors = \DateTime::getLastErrors();

        if (
            !$date ||
            ($errors !== false && ($errors['warning_count'] || $errors['error_count']))
        ) {
            return null;
        }

        return $date->format('Y-m-d 00:00:00');
    }

    /**
     * Обновление пользовтаельского соглашения системы
     * @param string $esiaScope
     * @param int $clientId
     * @param bool $removeOld
     * @return void
     */
    protected function createClientEsiaScopes($esiaScope, $clientId, $removeOld = false)
    {
        if ($removeOld) {
            RefEsiaScopesOauthClient::where('oauth_client_id', $clientId)->delete();
        }
        if (!isset($esiaScope)) {
            return;
        }
        $esiaScopeCollect = EsiaScope::whereIn('name', $esiaScope)->get();
        $refs = [];

        foreach ($esiaScope as $item) {
            $refs[] = [
                'esia_scope_id' => $esiaScopeCollect->where('name', $item)->first()->id,
                'oauth_client_id' => $clientId
            ];
        }
        RefEsiaScopesOauthClient::insert($refs);
    }

    /**
     * Получение последнего накопительного изменения изменения
     * @param integer $clientId
     * @param integer $userId
     * @param string $type in ['awaiting', 'accepted', 'rejected', 'aggregated']
     * @param array $ids
     * @param int $summaryId
     * @return object
     */
    protected function getLastChanges($clientId, $userId = null, $type = 'awaiting', $ids = null, $summaryId = null)
    {
        $whereBinds = [['client_id', $clientId], ['accepted_status', $type]];
        if ($userId) {
            $whereBinds[] = ['user_id', $userId];
        }
        if ($summaryId) {
            $whereBinds[] = ['summary_id', $summaryId];
        }
        $changes = OauthClientsChanges::where($whereBinds)->orderBy('created_at', 'desc');
        if ($ids) {
            $changes->whereIn('id', $ids);
        }
        $changeTime = null;
        $changesSum = [];
        $aggregatedIds = [];
        $reason = null;
        switch ($type) {
            case 'awaiting':
            case 'aggregated':
            {
                $changes = $changes->cursor();
                foreach ($changes as $change) {
                    if (!$changeTime)
                        $changeTime = date('d.m.Y H:i', strtotime($change->updated_at));
                    $changesSum = array_merge($change->changes, $changesSum);
                    $aggregatedIds[] = $change->id;
                }
                break;
            }
            case 'accepted':
            case 'rejected':
            case 'canceled':
            {
                $change = $changes->first();
                if ($change) {
                    $aggregatedChanges = $this->getLastChanges($clientId, $userId, 'aggregated', $ids, $change->id);
                    $changesSum = $change->changes;
                    $changeTime = date('d.m.Y H:i', strtotime($change->updated_at));
                    $aggregatedIds = $aggregatedChanges->aggregatedIds;
                    $reason = $change->reason;
                }
                break;
            }
        }
        return (object)[
            'changeTime' => $changeTime,
            'changesSum' => $changesSum,
            'aggregatedIds' => $aggregatedIds,
            'reason' => $reason
        ];
    }

    /**
     * Добавление в клиента изменений
     * @param array $client
     * @param int $userId
     * @return array
     */
    protected function getClientChanges($client, $userId = null)
    {
        $changesOnModeration = $this->getLastChanges($client['id'], $userId);
        if (!$changesOnModeration->changeTime) {
            $changesOnModeration = null;
        }
        $changesRejected = $this->getLastChanges($client['id'], $userId, 'rejected');
        if (!$changesRejected->changeTime) {
            $changesRejected = null;
        }
        $changesAccepted = $this->getLastChanges($client['id'], $userId, 'accepted');
        if (!$changesAccepted->changeTime) {
            $changesAccepted = null;
        }
        if ($changesAccepted && $changesRejected) {
            if (strtotime($changesAccepted->changeTime) >= strtotime($changesRejected->changeTime)) {
                $changesRejected = null;
            } else {
                $changesAccepted = null;
            }
        }
        $client['last_changes'] = $changesOnModeration;
        $client['last_changes_rejected'] = $changesRejected;
        $client['last_changes_accepted'] = $changesAccepted;
        return $client;
    }
}
