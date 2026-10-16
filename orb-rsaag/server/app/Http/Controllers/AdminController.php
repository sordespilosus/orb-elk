<?php

namespace App\Http\Controllers;

use App\{
    OauthClient,
    OauthClientsChanges,
    ReasonsForRejection,
    User
};
use App\Extensions\Helpers;
use App\Http\Requests\TabRequest;
use App\Http\Requests\Admin\{
    DelClientRequest,
    GetLogsRequest,
    GetQueueRequest,
    GetUnsubscribedRequest,
    GetUsersRequest,
    SendMailingRequest,
    SetDeleteRoleRequest,
    SetEditRoleRequest,
    SetNewRoleRequest,
    SetUserBlockingRequest,
    UpdateUserRequest
};
use App\Http\Traits\ClientsTrait;
use App\Jobs\SendEmailJob;
use App\Services\AdminUsersQuery;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Validator};
use Illuminate\Validation\Rule;
use Laravel\Passport\{ClientRepository, Passport};

/**
 * @OA\Info(title="AdminController", version="1.0.0")
 */
class AdminController extends Controller
{
    use ClientsTrait;

    protected $clients;

    public function __construct(ClientRepository $clients)
    {
        $this->clients = $clients;
    }

    public function constructAdminPanel()
    {
        return view('admin.admin');
    }

    /**
     * @OA\Get(path="/admin/api/clients", tags={"Clients"}, summary="Функция отдаёт внешние системы",
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="clients", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="user_id", type="integer"),
     *                         @OA\Property(property="name", type="string", example="ORB-RSAAG Personal Access Client"),
     *                         @OA\Property(property="secret", type="string", example="accessToken"),
     *                         @OA\Property(property="redirect", type="array", items={"rediectUrl"}),
     *                         @OA\Property(property="personal_access_client", type="boolean", example="true"),
     *                         @OA\Property(property="password_client", type="boolean", example="false"),
     *                         @OA\Property(property="revoked", type="boolean", example="false"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="url_login", type="string", example="http://example.com"),
     *                         @OA\Property(property="url_notifications", type="string", example="http://example.com"),
     *                         @OA\Property(property="url_site", type="string", example="http://example.com"),
     *                         @OA\Property(property="logo", type="string", example="data:image/x-icon;base64,AAAB"),
     *                         @OA\Property(property="need_esia", type="boolean", example="false"),
     *                         @OA\Property(property="is_hidden", type="boolean", example="false"),
     *                         @OA\Property(property="ord", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Тестовое описание"),
     *                         @OA\Property(property="need_organization", type="boolean", example="false"),
     *                         @OA\Property(property="scope", type="object",
     *                             @OA\Property(property="rsaag_id", type="boolean", example="true"),
     *                             @OA\Property(property="personal_data", type="boolean", example="false"),
     *                             @OA\Property(property="esia_data", type="boolean", example="false"),
     *                             @OA\Property(property="email", type="boolean", example="false"),
     *                             @OA\Property(property="phone", type="boolean", example="false"),
     *                             @OA\Property(property="esia_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="fb_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="vk_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="passport", type="boolean", example="false"),
     *                             @OA\Property(property="esia_address", type="boolean", example="false"),
     *                             @OA\Property(property="snils", type="boolean", example="false"),
     *                             @OA\Property(property="organization_id", type="boolean", example="false"),
     *                             @OA\Property(property="auth_method", type="boolean", example="false")
     *                         ),
     *                         @OA\Property(property="only_esia", type="boolean", example="false"),
     *                         @OA\Property(property="dont_remember", type="boolean", example="false"),
     *                         @OA\Property(property="scope_required", type="object",
     *                             @OA\Property(property="rsaag_id", type="boolean", example="true"),
     *                             @OA\Property(property="personal_data", type="boolean", example="false"),
     *                             @OA\Property(property="esia_data", type="boolean", example="false"),
     *                             @OA\Property(property="email", type="boolean", example="false"),
     *                             @OA\Property(property="phone", type="boolean", example="false"),
     *                             @OA\Property(property="esia_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="fb_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="vk_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="passport", type="boolean", example="false"),
     *                             @OA\Property(property="esia_address", type="boolean", example="false"),
     *                             @OA\Property(property="snils", type="boolean", example="false"),
     *                             @OA\Property(property="organization_id", type="boolean", example="false"),
     *                             @OA\Property(property="auth_method", type="boolean", example="false")
     *                         ),
     *                         @OA\Property(property="esia_level", type="string", example="TRUSTED"),
     *                         @OA\Property(property="status", type="string", example="sent"),
     *                         @OA\Property(property="status_changed_at", type="string", format="date-time"),
     *                         @OA\Property(property="without_oauth_notify", type="boolean", example="false"),
     *                         @OA\Property(property="auth_methods", type="array", items={"login"}),
     *                         @OA\Property(property="only_login", type="boolean", example="false"),
     *                         @OA\Property(property="reasons_for_rejections", type="array", items={"login"}),
     *                         @OA\Property(property="update_agreement", type="boolean", example="false"),
     *                         @OA\Property(property="client_hits_esia_scope", type="object",
     *                             @OA\Property(property="ext_org_info", type="boolean", example="false"),
     *                             @OA\Property(property="default_data", type="boolean", example="false"),
     *                             @OA\Property(property="temporary_residence_doc", type="boolean", example="false"),
     *                             @OA\Property(property="drivers_licence_doc", type="boolean", example="false"),
     *                             @OA\Property(property="residence_doc", type="boolean", example="false"),
     *                             @OA\Property(property="birth_cert_doc", type="boolean", example="false"),
     *                             @OA\Property(property="vehicles", type="boolean", example="false"),
     *                             @OA\Property(property="kids", type="boolean", example="false")
     *                         ),
     *                         @OA\Property(property="agreement", type="string", example="<h1>TEST</h1>"),
     *                     )
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
     * Функция отдаёт внешние системы
     * @param Request $request
     * @return array
     */
    public function getClients(Request $request)
    {

        $clients = OauthClient::active()->orderBy('id')->get()->all();
        $scope = Passport::$scopes;
        $esiaScopes = $this->getEsiaScopes();
        foreach ($clients as &$client) {
            $client = $this->normalizeClientAttrs($client, true, true, true);
        }

        return ['status' => true, 'data' => ['clients' => $clients, 'scope' => $scope, 'esiaScopes' => $esiaScopes]];
    }

    /**
     * @OA\Get(path="/admin/api/clients_moderation", tags={"Clients"}, summary="Получение страницы систем на модерации",
     *     @OA\Parameter(name="tab", in="query", description="Вкладка для отображения", required=false,
     *         @OA\Schema(type="string", enum={"clients", "changes"})
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", description="Статус ответа"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="scope", type="object", description="Области доступа",
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
     *                 ),
     *                 @OA\Property(property="newClients", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="user_id", type="integer"),
     *                         @OA\Property(property="name", type="string", example="Новый клиент"),
     *                         @OA\Property(property="redirect", type="array", items={"https://example.com"}),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="url_login", type="string", example="https://example.com/login"),
     *                         @OA\Property(property="url_notifications", type="string", example="https://example.com"),
     *                         @OA\Property(property="url_site", type="string", example="https://example.com"),
     *                         @OA\Property(property="logo", type="string", example="https://example.com/logo.png"),
     *                         @OA\Property(property="need_esia", type="boolean"),
     *                         @OA\Property(property="is_hidden", type="boolean"),
     *                         @OA\Property(property="ord", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Описание клиента"),
     *                         @OA\Property(property="need_organization", type="boolean"),
     *                         @OA\Property(property="scope", type="object",
     *                             @OA\Property(property="rsaag_id", type="boolean", example="false"),
     *                             @OA\Property(property="personal_data", type="boolean", example="false"),
     *                             @OA\Property(property="esia_data", type="boolean", example="false"),
     *                             @OA\Property(property="email", type="boolean", example="true"),
     *                             @OA\Property(property="phone", type="boolean", example="false"),
     *                             @OA\Property(property="esia_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="fb_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="vk_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="passport", type="boolean", example="false"),
     *                             @OA\Property(property="esia_address", type="boolean", example="false"),
     *                             @OA\Property(property="snils", type="boolean", example="false"),
     *                             @OA\Property(property="organizations_user", type="boolean", example="false"),
     *                             @OA\Property(property="auth_method", type="boolean", example="false")
     *                         ),
     *                         @OA\Property(property="only_esia", type="boolean", example="false"),
     *                         @OA\Property(property="dont_remember", type="boolean", example="false"),
     *                         @OA\Property(property="scope_required", type="object",
     *                             @OA\Property(property="rsaag_id", type="boolean", example="false"),
     *                             @OA\Property(property="personal_data", type="boolean", example="false"),
     *                             @OA\Property(property="esia_data", type="boolean", example="false"),
     *                             @OA\Property(property="email", type="boolean", example="true"),
     *                             @OA\Property(property="phone", type="boolean", example="false"),
     *                             @OA\Property(property="esia_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="fb_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="vk_user_id", type="boolean", example="false"),
     *                             @OA\Property(property="passport", type="boolean", example="false"),
     *                             @OA\Property(property="esia_address", type="boolean", example="false"),
     *                             @OA\Property(property="snils", type="boolean", example="false"),
     *                             @OA\Property(property="organizations_user", type="boolean", example="false"),
     *                             @OA\Property(property="auth_method", type="boolean", example="false")
     *                         ),
     *                         @OA\Property(property="esia_level", type="string", example="TRUSTED"),
     *                         @OA\Property(property="status", type="string", example="sent"),
     *                         @OA\Property(property="status_changed_at", type="string", format="date-time"),
     *                         @OA\Property(property="without_oauth_notify", type="boolean", example="false"),
     *                         @OA\Property(property="auth_methods", type="array", items={"password"}),
     *                         @OA\Property(property="only_login", type="boolean", example="false"),
     *                         @OA\Property(property="reasons_for_rejections", type="array", items={"password"}),
     *                         @OA\Property(property="update_agreement", type="boolean", example="false"),
     *                         @OA\Property(property="client_hits_esia_scope", type="object",
     *                             @OA\Property(property="ext_org_info", type="boolean", example="false"),
     *                             @OA\Property(property="default_data", type="boolean", example="false"),
     *                             @OA\Property(property="temporary_residence_doc", type="boolean", example="false"),
     *                             @OA\Property(property="drivers_licence_doc", type="boolean", example="false"),
     *                             @OA\Property(property="residence_doc", type="boolean", example="false"),
     *                             @OA\Property(property="birth_cert_doc", type="boolean", example="false"),
     *                             @OA\Property(property="vehicles", type="boolean", example="false"),
     *                             @OA\Property(property="kids", type="boolean", example="false"),
     *                         ),
     *                         @OA\Property(property="agreement", type="string", example="<h1>СОГЛАШЕНИЕ</h1>"),
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     * Получение страницы чичтем на модерации
     * @param Request $request
     * @return array
     */
    public function getPageClientsModeration(TabRequest $request)
    {
        $tab = $request->get('tab');

        $res = [
            'status' => true,
            'data' => [
                'scope' => Passport::$scopes,
                'esiaScopes' => $this->getEsiaScopes(),
            ],
        ];

        if (!$tab || $tab === 'clients') {
            $res['data']['newClients'] = $this->getClientsModeration();
        }
        if (!$tab || $tab === 'changes') {
            $res['data']['changesClients'] = $this->getClientsChangesModeration();
        }

        return $res;
    }

    /**
     * Функция отдаёт внешние системы находящиеся на модерации
     * @return array
     */
    private function getClientsModeration()
    {
        $clients = OauthClient::active()->status('sent')->orderBy('updated_at', 'desc')->get()->all();

        foreach ($clients as &$client) {
            $client = $this->normalizeClientAttrs($client, true, true, true, ['secret', 'personal_access_client', 'password_client', 'revoked']);
        }

        return $clients;
    }

    /**
     * Функция отдаёт внешние системы для которых необходима модерация изменений
     * @return array
     */
    private function getClientsChangesModeration()
    {
        $clientsDB = OauthClient::active()->status('approved')->get()->all();
        $clients = [];
        foreach ($clientsDB as $client) {
            $client = $this->normalizeClientAttrs($client, true, true, true, ['secret', 'personal_access_client', 'password_client', 'revoked']);
            $client = $this->getClientChanges($client);

            if ($client['last_changes']) {
                $clients[] = $client;
            }

        }
        usort($clients, function ($a, $b) {
            $aTime = strtotime($a['last_changes']->changeTime);
            $bTime = strtotime($b['last_changes']->changeTime);
            if ($aTime == $bTime) {
                return 0;
            } elseif ($aTime > $bTime) {
                return 1;
            } else {
                return -1;
            }
        });
        return $clients;
    }

    /**
     * @OA\Get(path="/admin/api/users", tags={"Users"}, summary="Функция отдаёт пользователей",
     *     @OA\Parameter(name="page", in="query", description="Номер страницы", required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(name="per_page", in="query", description="Количество пользователей на странице", required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(name="order_by", in="query", description="Поле для сортировки", required=false,
     *         @OA\Schema(type="string", enum={"id", "email", "fio", "blocked", "email_verified_at"}, default="id")
     *     ),
     *     @OA\Parameter(name="is_asc", in="query", description="Направление сортировки", required=false,
     *         @OA\Schema(type="integer", enum={0, 1}, default=1)
     *     ),
     *     @OA\Parameter(name="fio", in="query", description="ФИО пользователя", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="second_name", in="query", description="Фамилия пользователя", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="first_name", in="query", description="Имя пользователя", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="patroname", in="query", description="Отчество пользователя", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="city", in="query", description="Город пользователя", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="id", in="query", description="ID пользователя", required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(name="is_blocked", in="query", description="Блокировка пользователя", required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(name="is_accepted", in="query", description="Подтверждение пользователя", required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(name="gender", in="query", description="Пол пользователя", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="esia", in="query", description="Наличие ЕСИА", required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(name="age_period_from", in="query", description="Возрастной период от", required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(name="age_period_to", in="query", description="Возрастной период до", required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(name="only_verified", in="query", description="Только подтвержденные пользователи",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="countUsers", type="integer", example=22873),
     *                 @OA\Property(property="users", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="email", type="string", example="test@test.ru"),
     *                         @OA\Property(property="surname", type="string", example="Тестов"),
     *                         @OA\Property(property="name", type="string", example="Тест"),
     *                         @OA\Property(property="patroname", type="string", example="Тестович"),
     *                         @OA\Property(property="phone", type="string", example="89312313"),
     *                         @OA\Property(property="birthday", type="string", format="date", example="2000-10-12"),
     *                         @OA\Property(property="age_period", type="string", example="2"),
     *                         @OA\Property(property="gender", type="string", example="male"),
     *                         @OA\Property(property="esia_user_id", type="integer", example=12313123),
     *                         @OA\Property(property="vk_user_id", type="integer", example=12313123),
     *                         @OA\Property(property="fb_user_id", type="integer", example=12313123),
     *                         @OA\Property(property="is_accepted", type="boolean", example="true"),
     *                         @OA\Property(property="is_blocked", type="boolean", example="true"),
     *                         @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                         @OA\Property(property="fio", type="string", example="Тестов Тест Тестович"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="is_deleted", type="boolean", example="true"),
     *                         @OA\Property(property="cnt_users", type="integer", example="22873")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="status", type="boolean", example="true")
     *         )
     *     )
     * )
     *
     * Функция отдаёт пользователей
     * @param Request $request
     * @return array
     */
    public function getUsers(GetUsersRequest $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $orderBy = $request->get('order_by', 'id');
        $isAsc = $request->get('is_asc', true);

        $offset = ($page - 1) * $perPage;
        $usersQuery = AdminUsersQuery::filtered($request->all(), true);
        $users = AdminUsersQuery::applyOrder(
            AdminUsersQuery::selectRegistryColumns($usersQuery),
            $orderBy,
            $isAsc
        )
            ->limit((int)$perPage)
            ->offset((int)$offset)
            ->get();
        $users->transform(function ($user) {
            $user->is_admin = (bool)$user->is_admin;
            return $user;
        });
        $countUsers = $users[0]->cnt_users ?? 0;

        return [
            'data' => ['countUsers' => $countUsers, 'users' => $users],
            'status' => true,
        ];
    }

    /**
     * Функция отдаёт данные пользователя для редактирования
     * @param integer $id
     * @return array
     */
    public function getUser($id)
    {
        $validator = Validator::make([
            'user_id' => $id,
        ], [
            'user_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return [
                'errors' => $validator->errors()->messages(),
                'status' => false,
            ];
        }

        $user = DB::table('oauth.users')->where('id', $id)->first();

        if (!$user) {
            return [
                'errors' => true,
                'status' => false,
            ];
        }

        $isAdmin = DB::table('roles.ref_user_roles')
            ->where('user_id', $id)
            ->where('role_id', 1)
            ->exists();

        $user->is_admin = $isAdmin;
        $user->age = $user->birthday ? date_diff(date_create($user->birthday), date_create('today'))->y : null;

        return [
            'data' => $user,
            'status' => true,
        ];
    }

    /**
     * Функция обновляет данные пользователя из админки
     * @param UpdateUserRequest $request
     * @return array
     */
    public function updateUser(UpdateUserRequest $request)
    {
        $userId = $request->get('user_id');
        $email = $request->get('email');
        $blocked = (bool)$request->get('blocked');
        $isAdmin = (bool)$request->get('is_admin');
        $isDeleted = (bool)$request->get('is_deleted');
        $emailVerified = (bool)$request->get('email_verified');

        $user = DB::table('oauth.users')->where('id', $userId)->first();

        if (!$user) {
            return [
                'errors' => true,
                'status' => false,
            ];
        }

        $emailExists = DB::table('oauth.users')
            ->where('email', $email)
            ->where('id', '<>', $userId)
            ->exists();

        if ($emailExists) {
            return [
                'errors' => ['email' => ['Пользователь с таким логином уже существует']],
                'status' => false,
            ];
        }

        DB::transaction(function () use ($userId, $email, $blocked, $isAdmin, $isDeleted, $emailVerified, $user) {
            DB::table('oauth.users')
                ->where('id', $userId)
                ->update([
                    'email' => $email,
                    'blocked' => $blocked,
                    'is_deleted' => $isDeleted,
                    'email_verified_at' => $emailVerified ? ($user->email_verified_at ?? Carbon::now()) : null,
                    'updated_at' => Carbon::now(),
                ]);

            $adminRoleExists = DB::table('roles.ref_user_roles')
                ->where('user_id', $userId)
                ->where('role_id', 1)
                ->exists();

            if ($isAdmin && !$adminRoleExists) {
                DB::table('roles.ref_user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => 1,
                ]);
            }

            if (!$isAdmin && $adminRoleExists) {
                DB::table('roles.ref_user_roles')
                    ->where('user_id', $userId)
                    ->where('role_id', 1)
                    ->delete();
            }
        });

        return $this->getUser($userId);
    }

    /**
     * Функция изменяет параметр пользователя
     * @param integer $user
     * @param string $column
     * @param mixed $value
     * @return boolean
     */
    private function setUserProp($user, $column, $value)
    {
        $result = DB::table('users')->where('id', $user)->update([$column => $value]);
        return $result;
    }

    // TODO getQueue

    /**
     * @OA\Get(path="/admin/api/mailing/get_queue", tags={"Mailing"}, summary="Получение очереди",
     *     @OA\Parameter(name="page", in="query", description="Номер страницы", required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(name="per_page", in="query", description="Количество записей на странице", required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(name="order_by", in="query", description="Поле для сортировки", required=false,
     *         @OA\Schema(type="string", enum={"id", "email", "status"}, default="id")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ")
     *  )
     * @param Request $request
     * @return array
     */
    public function getQueue(GetQueueRequest $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $orderBy = $request->get('order_by', 'id');

        $offset = ($page - 1) * $perPage;

        $query = "select row_number() over () as id, query.params ->> 'subject' as email, json_agg(query.*) as contents, count(1) over() as count_queue from (select um.id, um.email, um.type, um.params, um.status from mailer.user_mails um where um.type = 'mailing' order by um." . $orderBy . " desc) query group by query.params ->> 'subject', query.params->>'unique_code' limit :in_per_page offset :in_offset";

        $binds = [
            'in_offset' => $offset,
            'in_per_page' => $perPage,
        ];

        $queue = DB::select($query, $binds);
        $countQueue = null;

        foreach ($queue as $user) {
            if ($countQueue == null) {
                $countQueue = $user->count_queue;
            }

            $user->contents = json_decode($user->contents);
            $user->status = null;
            unset($user->count_queue);
        }

        return [
            'data' => ['countQueue' => $countQueue, 'queue' => $queue],
            'status' => true,
        ];
    }

    /**
     * @OA\Post(path="/admin/api/mailing/unsubscribed_user", tags={"Mailing"}, summary="Добавление пользователей в список отписавшихся",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"user_mails"},
     *                 @OA\Property(property="user_mails", type="array",
     *                     @OA\Items(type="string", example="user@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="object",
     *                 example={"status_id": 1, "status": "Успешно добавлено в исключения"}
     *             ),
     *             @OA\Property(property="result2", type="object",
     *                 example={"status_id": 0, "status": "Ошибка при добавлении в исключения"}
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return false[]|null[]
     */
    public function unsubscribedUser(Request $request)
    {
        $userMails = $request->only('user_mails') ?? null;

        if ($userMails == null) {
            return ["status" => false];
        }

        try {
            $res = DB::selectOne('SELECT * FROM mailer.add_unsubscribed_user(in_user_mails => :user_mails)', [
                'user_mails' => json_encode($userMails),
            ]);
        } catch (\Exception $exception) {
            $res = null;
        }

        return ["result" => $res];
    }

    /**
     * @OA\Post(path="/admin/api/mailing/update_unsubscribed", tags={"Mailing"},
     *     summary="Обновление списка отписавшихся",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"id"},
     *                 @OA\Property(property="id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *  )
     *
     * @param Request $request
     * @return false[]|true[]
     */
    public function updateUnsubscribed(Request $request)
    {
        $id = $request->get('id', null);

        if ($id == null) {
            return ["status" => false];
        }

        $query = "delete from mailer.unsubscribed unsub where unsub.id = :user_id returning unsub.id";

        $res = DB::select($query, [':user_id' => $id]);

        if (!$res) {
            return ["status" => false];
        }

        return ["status" => true];
    }

    /**
     * @OA\Get(path="/admin/api/mailing/get_unsubscribed", tags={"Mailing"},
     *     summary="Получение списка отписавшихся пользователей",
     *     @OA\Parameter(name="page", in="query", description="Номер страницы", required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(name="per_page", in="query", description="Количество записей на странице", required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(name="order_by", in="query", description="Поле для сортировки", required=false,
     *         @OA\Schema(type="string", enum={"id", "email", "status"}, default="id")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ")
     * )
     * @param Request $request
     * @return array
     */
    public function getUnsubscribed(GetUnsubscribedRequest $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $orderBy = $request->get('order_by', 'id');

        $offset = ($page - 1) * $perPage;

        $query = "select unsub.id as id, usr.email as email, unsub.active as status, to_char(unsub.created_at, 'dd.mm.yyyy HH:MI') as created_at, count(1) over() as count_users from mailer.unsubscribed unsub inner join oauth.users usr on usr.id = unsub.id order by unsub." . $orderBy . " desc limit :in_per_page offset :in_offset";

        $binds = [
            'in_offset' => $offset,
            'in_per_page' => $perPage,
        ];

        $users = DB::select($query, $binds);
        $countUsers = null;

        foreach ($users as $user) {
            if ($countUsers == null) {
                $countUsers = $user->count_users;
            }

            unset($user->count_users);
        }

        return [
            'data' => ['countUsers' => $countUsers, 'users' => $users],
            'status' => true,
        ];
    }

    /**
     * @OA\Post(path="/admin/api/mailing/mailing_module", tags={"Mailing"}, summary="Отправка рассылки",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"subject", "description"},
     *                 @OA\Property(property="subject", type="string", example="Тема письма"),
     *                 @OA\Property(property="description", type="string", example="Текст письма")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", example="Добавлено в очередь")
     *         )
     *     ),
     *  )
     *
     * @param Request $request
     * @return array
     */
    public function sendMailing(SendMailingRequest $request)
    {
        $users = $this->getUsers($request);
        $email_valid = null;
        $subject = $request->get('subject');
        $description = $request->get('description');

        foreach ($users['data']['users'] as $user) {
            if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $email_valid[] = ($user->email);
            }
        }

        $status = null;

        if (is_null($email_valid)) {
            return ['status_id' => 0, 'status' => 'Все выбранные пользователи отписались от рассылки'];
        }

        //  Группируем письма в чанки размером 200
        $chunks = array_chunk($email_valid, 200);

        //  Случайный код, идентифицирующий данную рассылку
        $unique_code = implode('', Helpers::randNumbers(6, 0, 9));

        foreach ($chunks as $index => $chunk) {
            //  Преобразование в читаемый для SQL формат
            $encode = json_encode($chunk);
            $new_encode = str_replace('[', '{', $encode);
            $last_encode = str_replace(']', '}', $new_encode);

            $mail_params = [
                'subject' => $subject,
                'unique_code' => $unique_code,
                'chunk_id' => $index,
            ];

            //  Создание записей статуса отправления писем (используется в задаче SendEmailJob)
            $status = DB::select('select * from mailer.add_queue_mail(in_email => :in_email, in_type => :in_type, in_params => :in_params)', [
                ':in_email' => $last_encode,
                ':in_type' => 'mailing',
                ':in_params' => json_encode($mail_params, JSON_PRETTY_PRINT),
            ])[0] ?? null;

            //  Создаёт задачу SendEmailJob и добавляет её в очередь email_chunk
            dispatch(new SendEmailJob($chunk, $subject, $description, $mail_params))->onQueue('email_chunk');
        }

        return ['status_id' => 1, 'status' => 'Добавлено в очередь'];
    }

    /**
     * @OA\Post(path="/admin/api/users/blocking", tags={"Users"},
     *     summary="Функция блокирует или разблокирует пользователя",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"user_id", "value"},
     *                 @OA\Property(property="user_id", type="integer", description="Имя пользователя", example=1),
     *                 @OA\Property(property="value", type="boolean",
     *                     description="Значение блокировки (true - заблокировать, false - разблокировать)",
     *                     example=true
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="blocked", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     * Функция блокирует или разблокирует пользователя
     * @param Request $request
     * @return array
     */
    public function setUserBlocking(SetUserBlockingRequest $request)
    {
        $user = $request->get('user_id');
        $value = (int)$request->get('value', 0);

        return [
            'data' => $this->setUserProp($user, 'blocked', $value),
            'status' => true,
        ];
    }

    /**
     * @OA\Get(path="/admin/api/users/{id}/roles", tags={"Users"}, summary="Функция отдаёт роли пользователя",
     *     @OA\Parameter(name="id", in="path", description="ID пользователя", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", default="true"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="public_name", type="string", example="Администратор"),
     *                     @OA\Property(property="name", type="string", example="admin"),
     *                 )
     *             ),
     *         )
     *     ),
     *     @OA\Response(response=500, description="Пользователь не найден")
     * )
     *
     * Функция отдаёт роли пользователя
     * @param integer $id
     * @return array
     */
    public function getUserRoles($id)
    {
        $validator = Validator::make([
            'user_id' => $id,
        ], [
            'user_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $result = [
                'errors' => $validator->errors()->messages(),
                'status' => false,
            ];
            return $result;
        }

        return [
            'data' => DB::table('roles.ref_user_roles')
                ->where('user_id', $id)
                ->leftJoin('roles.roles', 'roles.ref_user_roles.role_id', '=', 'roles.roles.id')
                ->select('roles.id', 'public_name', 'name')
                ->get(),
            'status' => true,
        ];
    }

    /**
     * Функция устанавливает роли для пользователя
     * @param integer $id
     * @param Request $request
     * @return array
     */
    public function setUserRole($id, Request $request)
    {
        $userId = $id;
        $roleId = $request->get('role_id');

        $validator = Validator::make([
            'user_id' => $userId,
            'role_id' => $roleId,
        ], [
            'user_id' => 'required|numeric',
            'role_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $result = [
                'errors' => $validator->errors()->messages(),
                'status' => false,
            ];
            return $result;
        }

        return [
            'data' => DB::table('roles.ref_user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]),
            'status' => true,
        ];
    }

    /**
     * @OA\Post(path="/admin/api/users/{id}/roles", tags={"Users"}, summary="Функция изменяет роли для пользователя",
     *     @OA\Parameter(name="id", in="path", description="ID пользователя", required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"roles"},
     *                 @OA\Property(property="roles", type="array",
     *                     @OA\Items(type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="boolean", example=true)
     *         )
     *     )
     * )
     *
     *
     * Функция изменяет роли для пользователя
     * @param integer $id
     * @param Request $request
     * @return array
     */
    public function changeUserRole($id, Request $request)
    {
        $userId = $id;
        $roles = $request->get('roles');
        if (gettype($roles) === 'string') {
            $roles = json_decode($roles);
        }

        $validator = Validator::make([
            'user_id' => $userId,
            'roles' => $roles,
        ], [
            'user_id' => 'required|numeric',
            'roles.*' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $result = [
                'errors' => $validator->errors()->messages(),
                'status' => false,
            ];
            return $result;
        }

        DB::table('roles.ref_user_roles')->where([
            'user_id' => $userId,
        ])->delete();

        $insert = [];
        foreach ($roles as $role) {
            $insert[] = [
                'user_id' => $userId,
                'role_id' => $role,
            ];
        }

        $res = DB::table('roles.ref_user_roles')->insert($insert);

        return $result = [
            'data' => (boolean)$res,
            'status' => true,
        ];
    }

    /**
     * @OA\Post(path="/admin/api/users/{id}/roles/unset", tags={"Users"}, summary="Удаление роли пользователя",
     *     @OA\Parameter(name="id", in="path", description="ID пользователя", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"role_id"},
     *                 @OA\Property(property="role_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="integer")
     *         )
     *     )
     * )
     *
     * Функция Удаляет роли пользователя
     * @param integer $id
     * @param Request $request
     * @return array
     */
    public function unsetUserRole($id, Request $request)
    {
        $userId = $id;
        $roleId = $request->get('role_id');

        $validator = Validator::make([
            'user_id' => $userId,
            'role_id' => $roleId,
        ], [
            'user_id' => 'required|numeric',
            'role_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $result = [
                'errors' => $validator->errors()->messages(),
                'status' => false,
            ];
            return $result;
        }
        return $result = [
            'data' => DB::table('roles.ref_user_roles')->where([
                'user_id' => $userId,
                'role_id' => $roleId,
            ])->delete(),
            'status' => true,
        ];
    }

    /**
     * @OA\Post(path="/admin/api/clients/edit", tags={"Clients"}, summary="Обновление клиента",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"id", "name", "redirect"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Новый клиент"),
     *                 @OA\Property(property="redirect", type="array",
     *                     @OA\Items(type="string", example="https://example.com")
     *                 ),
     *                 @OA\Property(property="url_login", type="string", example="https://example.com/login"),
     *                 @OA\Property(property="url_notifications", type="string",
     *                     example="https://example.com/notifications"
     *                 ),
     *                 @OA\Property(property="need_esia", type="boolean", example=true),
     *                 @OA\Property(property="only_esia", type="boolean", example=false),
     *                 @OA\Property(property="dont_remember", type="boolean", example=false),
     *                 @OA\Property(property="logo", type="string", example="https://example.com/logo.png"),
     *                 @OA\Property(property="url_site", type="string", example="https://example.com"),
     *                 @OA\Property(property="is_hidden", type="boolean", example=false),
     *                 @OA\Property(property="description", type="string", example="Описание клиента"),
     *                 @OA\Property(property="need_organization", type="boolean", example=false),
     *                 @OA\Property(property="scope", type="string", example="email"),
     *                 @OA\Property(property="scope_required", type="string", example="email"),
     *                 @OA\Property(property="esia_level", type="string", enum={"TRUSTED", "STANDART", "NONE"}),
     *                 @OA\Property(property="ord", type="integer", example=1),
     *                 @OA\Property(property="without_oauth_notify", type="boolean", example=false),
     *                 @OA\Property(property="auth_methods", type="array",
     *                     @OA\Items(type="string", example="password")
     *                 ),
     *                 @OA\Property(property="only_login", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Новый клиент"),
     *                 @OA\Property(property="redirect", type="string", example="https://example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Пользователь с таким ID не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="errors", type="boolean", example="true"),
     *         )
     *     )
     *  )
     *
     * Изменение внешней системы
     * @param Request $request
     * @return array
     */
    public function updateClient(Request $request)
    {
        return $this->createOrUpdateClient($request, false, true);
    }

    /**
     * @OA\Post(path="/admin/api/clients/new", tags={"Clients"}, summary="Добавление нового клиента",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"name", "redirect"},
     *                 @OA\Property(property="name", type="string", example="Новый клиент"),
     *                 @OA\Property(property="redirect", type="array",
     *                     @OA\Items(type="string", example="https://example.com")
     *                 ),
     *                 @OA\Property(property="url_login", type="string", example="https://example.com/login"),
     *                 @OA\Property(property="url_notifications", type="string",
     *                     example="https://example.com/notifications"
     *                 ),
     *                 @OA\Property(property="need_esia", type="boolean", example=true),
     *                 @OA\Property(property="only_esia", type="boolean", example=false),
     *                 @OA\Property(property="dont_remember", type="boolean", example=false),
     *                 @OA\Property(property="logo", type="string", example="https://example.com/logo.png"),
     *                 @OA\Property(property="url_site", type="string", example="https://example.com"),
     *                 @OA\Property(property="is_hidden", type="boolean", example=false),
     *                 @OA\Property(property="description", type="string", example="Описание клиента"),
     *                 @OA\Property(property="need_organization", type="boolean", example=false),
     *                 @OA\Property(property="scope", type="string", example="['email']"),
     *                 @OA\Property(property="scope_required", type="string", example="['email']"),
     *                 @OA\Property(property="esia_level", type="string", enum={"TRUSTED", "STANDART", "NONE"}),
     *                 @OA\Property(property="ord", type="integer", example=1),
     *                 @OA\Property(property="without_oauth_notify", type="boolean", example=false),
     *                 @OA\Property(property="auth_methods", type="string", example="['password']"),
     *                 @OA\Property(property="only_login", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Новый клиент"),
     *                 @OA\Property(property="redirect", type="string", example="https://example.com"),
     *                 @OA\Property(property="url_login", type="string", example="https://example.com"),
     *                 @OA\Property(property="url_notifications", type="string", example="https://example.com"),
     *                 @OA\Property(property="url_site", type="string", example="https://example.com"),
     *                 @OA\Property(property="need_esia", type="boolean"),
     *                 @OA\Property(property="only_esia", type="boolean"),
     *                 @OA\Property(property="dont_remember", type="boolean"),
     *                 @OA\Property(property="logo", type="string", example="https://example.com/logo.png"),
     *                 @OA\Property(property="is_hidden", type="boolean"),
     *                 @OA\Property(property="description", type="string", example="тестовое описание клиента"),
     *                 @OA\Property(property="need_organization", type="boolean"),
     *                 @OA\Property(property="scope", type="string", example="['email']"),
     *                 @OA\Property(property="scope_required", type="string", example="['email']"),
     *                 @OA\Property(property="esia_level", type="string", example="TRUSTED"),
     *                 @OA\Property(property="ord", type="integer", example=1),
     *                 @OA\Property(property="without_oauth_notify", type="boolean"),
     *                 @OA\Property(property="auth_methods", type="string", example="['password']"),
     *                 @OA\Property(property="only_login", type="boolean", example=false),
     *                 @OA\Property(property="personal_access_client", type="boolean", example=false),
     *                 @OA\Property(property="password_client", type="boolean", example=false),
     *                 @OA\Property(property="revoked", type="boolean", example=false),
     *                 @OA\Property(property="user_id", type="integer", example=228573),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     *
     * Добавление новой внешней системы
     * @param Request $request
     * @return array
     */
    public function addClient(Request $request)
    {
        return $this->createOrUpdateClient($request, true, true);
    }

    /**
     * @OA\Post(path="/admin/api/clients_moderation/set_status", tags={"ClientsModeration"},
     *     summary="Проставление статуса валидации",
     *     @OA\Parameter(name="client_id", in="query", required=true,
     *         @OA\Schema(type="integer", description="ID клиента")
     *     ),
     *     @OA\Parameter(name="status", in="query", required=true,
     *         @OA\Schema(type="string", enum={"rejected", "approved"}, default="rejected",
     *             description="Статус валидации"
     *         )
     *     ),
     *     @OA\Parameter(name="reasons", in="query",
     *         @OA\Schema(type="string", nullable=true, description="Причина отказа (требуется, если статус 'rejected')")
     *     ),
     *     @OA\Parameter(name="ord", in="query",
     *         @OA\Schema(type="integer", nullable=true, minimum=1)
     *     ),
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean")
     *         )
     *     ),
     * )
     *
     *
     * Проставление статуса валидации
     * @param Request $request
     * @return array
     */
    public function setStatusClients(Request $request)
    {
        $clientId = $request->get('client_id');
        $status = $request->get('status', 'rejected');
        $reasons = $request->get('reasons');
        $ord = $request->get('ord');

        $validator = Validator::make([
            'client_id' => $clientId,
            'status' => $status,
            'ord' => $ord,
            'reasons' => $reasons,
        ], [
            'client_id' => ['required', 'numeric', Rule::exists('oauth_clients', 'id')],
            'status' => ['required', Rule::in(['rejected', 'approved'])],
            'reasons' => 'nullable|string',
            'ord' => 'nullable|integer|min:1',
        ]);
        $validator->sometimes('reasons', 'required|string', function ($input) {
            return $input->status === 'rejected';
        });

        if ($validator->fails()) {
            return ['status' => false, 'errors' => $validator->errors()->messages()];
        }

        $client = OauthClient::find($clientId);
        $client->status = $status;
        if (isset($ord)) {
            $client->ord = $ord;
        }
        $client->status_changed_at = Carbon::now();
        $res = $client->save();
        $user = Auth::user();
        $reasonsObject = ReasonsForRejection::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'reasons' => $reasons,
            'status' => $status,
        ]);
        $owner = User::find($client->user_id);
        Helpers::sendEmail($owner->email, 'changeClientStatus', [
            'status' => $status,
            'clientName' => $client->name,
            'reasons' => $reasons,
        ]);
        return ['status' => (bool)$res];
    }

    /**
     * @OA\Post(path="/admin/api/clients_changes/set_status", tags={"ClientsChanges"},
     *     summary="Принятие или отклонение изменений",
     *     description="Метод для изменения статуса изменений клиента. Данные должны быть отправлены в формате JSON.",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="client_id", type="integer", description="ID клиента"),
     *             @OA\Property(property="status", type="string", enum={"rejected", "accepted"},
     *                  description="Новый статус"
     *             ),
     *             @OA\Property(property="changes_ids", type="array",
     *                 description="Массив ID изменений, которые необходимо обновить.",
     *                 @OA\Items(type="integer", description="Array of change IDs")
     *             ),
     *             @OA\Property(property="reasons", type="string", nullable=true,
     *                 description="Причины отклонения (обязательно, если статус 'rejected')"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешная операция",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", description="True, если операция была успешной")
     *         )
     *     ),
     * )
     *
     *
     * Принятие или отклонение изменений
     * @param Request $request
     * @return array
     */
    public function setChangesStatus(Request $request)
    {
        $clientId = $request->get('client_id');
        $status = $request->get('status');
        $changesIds = $request->get('changes_ids');
        $reasons = $request->get('reasons');

        $validator = Validator::make([
            'client_id' => $clientId,
            'status' => $status,
            'changes_ids' => $changesIds,
            'reasons' => $reasons,
        ], [
            'client_id' => ['required', 'numeric', Rule::exists('oauth_clients', 'id')],
            'status' => ['required', Rule::in(['rejected', 'accepted'])],
            'changes_ids' => 'required|array',
            'changes_ids.*' => ['required', 'integer', Rule::exists('auth.clients.oauth_clients_changes', 'id')],
            'reasons' => 'nullable|string',
        ]);
        $validator->sometimes('reasons', 'required|string', function ($input) {
            return $input->status === 'rejected';
        });

        if ($validator->fails()) {
            return ['status' => false, 'errors' => $validator->errors()->messages()];
        }

        $changes = $this->getLastChanges($clientId, null, 'awaiting', $changesIds);

        $change = OauthClientsChanges::create([
            'client_id' => $clientId,
            'user_id' => Auth::id(),
            'changes' => $changes->changesSum,
            'accepted_status' => $status,
            'reason' => $reasons,
            'moderator_id' => Auth::id(),
        ]);

        if ($change) {
            $date = new DateTime();
            $resUpdate = OauthClientsChanges::whereIn('id', $changesIds)->update([
                'moderator_id' => Auth::id(),
                'accepted_status' => 'aggregated',
                'summary_id' => $change->id,
                'updated_at' => $date->format('Y-m-d H:i:s.u P'), //TODO Модель не извлекается преобразования параметров может не быть
            ]);

            if ($status === 'accepted') {
                $resCreate = $this->createOrUpdateClient($request, false, true, true, array_merge((array)$changes->changesSum, ['id' => $clientId]));
            }

            $client = OauthClient::find($clientId);
            $owner = User::find($client->user_id);
            Helpers::sendEmail($owner->email, 'changeChangesStatus', [
                'status' => $status,
                'clientName' => $client->name,
                'reasons' => $reasons,
            ]);
        }
        return ['status' => isset($change)];
    }

    /**
     * @OA\Post(path="/admin/api/clients/delete", tags={"Clients"}, summary="Удаление клиента",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"client_id"},
     *                 @OA\Property(property="client_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Клиент")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Клиент не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="errors", type="boolean", example=true)
     *         )
     *     )
     * )
     *
     * Удаление внешней системы
     * @param Request $request
     * @return array
     */
    public function delClient(DelClientRequest $request)
    {
        $client = $this->clients->find($request->input('client_id'));

        if ($client) {
            $this->clients->delete($client);
            return ['status' => true, 'data' => $client];
        }

        return ['status' => false, 'errors' => true];
    }

    /**
     * @OA\Get(path="/admin/api/roles", tags={"Roles"}, summary="Функция отдаёт роли системы",
     *     @OA\Parameter(name="order_by", in="query", description="Поле для сортировки", required=false,
     *         @OA\Schema(type="string", enum={"id", "name", "public_name"}, default="id")
     *     ),
     *     @OA\Parameter(name="is_asc", in="query", description="Направление сортировки", required=false,
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="public_name", type="string", example="Администратор"),
     *                     @OA\Property(property="name", type="string", example="admin")
     *                 )
     *             )
     *         )
     *     )
     * )
     * Функция отдаёт роли системы
     * @param Request $request
     * @return array
     */
    public function getRoles(Request $request)
    {
        $filters = $request->all();

        if (isset($filters['is_asc']) && isset($filters['order_by'])) {
            $orderBy = $filters['order_by'];
            $isAsc = $filters['is_asc'];
        } else {
            $orderBy = 'id';
            $isAsc = true;
        }

        $data = DB::table('roles.roles')
            ->select('id', 'public_name', 'name')
            ->orderBy($orderBy, $isAsc ? 'asc' : 'desc')
            ->get();

        return [
            'data' => $data,
            'status' => true,
        ];
    }

    /**
     * @OA\Post(path="/admin/api/roles/new", tags={"Roles"}, summary="Создание новой роли",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"role_name", "role_tech_name"},
     *                 @OA\Property(property="role_name", type="string", example="Новая роль"),
     *                 @OA\Property(property="role_tech_name", type="string", example="new_role")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="role_name", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="0", type="string", example="Имя роли должно быть строкой")
     *                     )
     *                 ),
     *                 @OA\Property(property="role_tech_name", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="0", type="string",
     *                             example="Техническое имя роли должно быть строкой и содержать только латинские буквы, цифры и подчеркивания"
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="status", type="boolean", example=false)
     *         )
     *     )
     * )
     *
     *
     * Создание новой роли в системе
     * @param Request $request
     * @return array
     */
    public function setNewRole(SetNewRoleRequest $request)
    {
        $roleName = (string)$request->get('role_name');
        $roleEnglishName = (string)$request->get('role_tech_name');

        return [
            'data' => DB::table('roles.roles')->insert([
                'name' => $roleEnglishName,
                'public_name' => $roleName,
            ]),
            'status' => true,
        ];
    }

    /**
     * @OA\Post(path="/admin/api/roles/edit", tags={"Roles"}, summary="Редактирование роли",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"role_id", "role_name", "role_tech_name"},
     *                 @OA\Property(property="role_id", type="integer", example=1),
     *                 @OA\Property(property="role_name", type="string", example="Новая роль"),
     *                 @OA\Property(property="role_tech_name", type="string", example="new_role")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="role_id", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="0", type="string", example="ID роли должен быть целым числом")
     *                     )
     *                 ),
     *                 @OA\Property(property="role_name", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="0", type="string", example="Имя роли должно быть строкой")
     *                     )
     *                 ),
     *                 @OA\Property(property="role_tech_name", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="0", type="string",
     *                             example="Техническое имя роли должно быть строкой и содержать только латинские буквы, цифры и подчеркивания"
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="status", type="boolean", example=false)
     *         )
     *     )
     * )
     *
     * Изменение системной роли
     * @param Request $request
     * @return array
     */
    public function setEditRole(SetEditRoleRequest $request)
    {
        $roleId = (int)$request->get('role_id');
        $roleName = (string)$request->get('role_name');
        $roleEnglishName = (string)$request->get('role_tech_name');

        return [
            'data' => DB::table('roles.roles')->where('id', $roleId)
                ->update([
                    'name' => $roleEnglishName,
                    'public_name' => $roleName,
                ]),
            'status' => true,
        ];
    }

    /**
     * @OA\Post(path="/admin/api/roles/delete", tags={"Roles"}, summary="Удаление роли",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(required={"role_id"},
     *                 @OA\Property(property="role_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="role_id", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="0", type="string", example="ID роли должен быть целым числом")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="status", type="boolean", example=false)
     *         )
     *     )
     * )
     *
     * Удаление системной роли
     * @param Request $request
     * @return array
     */
    public function setDeleteRole(SetDeleteRoleRequest $request)
    {
        $roleId = $request->input('role_id');

        return [
            'data' => DB::table('roles.roles')->where('id', $roleId)->delete(),
            'status' => true,
        ];
    }

    /**
     * @OA\Get(path="/admin/api/logs", tags={"Admin"}, summary="Получение логов",
     *     @OA\Parameter(name="page", in="query", description="Номер страницы", required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(name="per_page", in="query", description="Количество записей на странице", required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(name="order_by", in="query", description="Поле для сортировки", required=false,
     *         @OA\Schema(type="string", enum={"id", "user_id", "date_created", "method", "url"}, default="date_created")
     *     ),
     *     @OA\Parameter(name="is_asc", in="query", description="Направление сортировки", required=false,
     *         @OA\Schema(type="string", enum={"true", "false"}, default="false")
     *     ),
     *     @OA\Parameter(name="max_id", in="query", description="Максимальный ID", required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(name="min_id", in="query", description="Минимальный ID", required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(name="max_date", in="query", description="Максимальная дата", required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(name="min_date", in="query", description="Минимальная дата", required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(name="method_get", in="query", description="GET запросы", required=false,
     *         @OA\Schema(type="string", enum={"true", "false"})
     *     ),
     *     @OA\Parameter(name="method_post", in="query", description="POST запросы", required=false,
     *         @OA\Schema(type="string", enum={"true", "false"})
     *     ),
     *     @OA\Parameter(name="method_others", in="query", description="Другие запросы", required=false,
     *         @OA\Schema(type="string", enum={"true", "false"})
     *     ),
     *     @OA\Parameter(name="ip", in="query", description="IP-адрес", required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", default="true"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="countLogs", type="integer", default="5000"),
     *                 @OA\Property(property="logs", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=123123),
     *                         @OA\Property(property="user_id", type="integer", example=13),
     *                         @OA\Property(property="ip", type="string", example="127.0.0.1"),
     *                         @OA\Property(property="url", type="string", example="docs/api"),
     *                         @OA\Property(property="get", oneOf={
     *                             @OA\Property(type="array", items=@OA\Items()),
     *                             @OA\Property(type="object", properties={
     *                                 @OA\Property(property="op", type="string", example="get"),
     *                                 @OA\Property(property="id", type="string", example="X2131238Eede23ufhj"),
     *                             })
     *                         }),
     *                         @OA\Property(property="post", oneOf={
     *                             @OA\Property(type="array", items=@OA\Items()),
     *                             @OA\Property(type="object", properties={
     *                                 @OA\Property(property="op", type="string", example="get"),
     *                                 @OA\Property(property="id", type="string", example="X2131238Eede23ufhj"),
     *                             })
     *                         }),
     *                         @OA\Property(property="date_created", type="string", format="date-time",
     *                             example="2024-11-25 09:34:51.896629+05"
     *                         ),
     *                         @OA\Property(property="method", type="string", example="GET"),
     *                         @OA\Property(property="header", type="object",
     *                             @OA\Property(property="host", type="array", items={
     *                                 @OA\Items(type="string", example="127.0.0.1")
     *                             }),
     *                             @OA\Property(property="connection", type="array", items={
     *                                 @OA\Items(type="string", example="keep-alive")
     *                             }),
     *                             @OA\Property(property="sec-ch-ua-platform", type="array", items={
     *                                 @OA\Items(type="string", example="Windows")
     *                             }),
     *                             @OA\Property(property="user-agent", type="array", items={
     *                                 @OA\Items(type="string", example="Mozilla/5.0 (Windows NT 10.0; Win64; x64)")
     *                             }),
     *                             @OA\Property(property="sec-ch-ua", type="array", items={
     *                                 @OA\Items(type="string", example="Chromium")
     *                             }),
     *                             @OA\Property(property="sec-ch-ua-mobile", type="array", items={
     *                                 @OA\Items(type="string", example="?0")
     *                             }),
     *                             @OA\Property(property="accept", type="array", items={
     *                                 @OA\Items(type="string", example="text/css")
     *                             }),
     *                             @OA\Property(property="sec-fetch-site", type="array", items={
     *                                 @OA\Items(type="string", example="same-origin")
     *                             }),
     *                             @OA\Property(property="sec-fetch-mode", type="array", items={
     *                                 @OA\Items(type="string", example="no-cors")
     *                             }),
     *                             @OA\Property(property="referer", type="array", items={
     *                                 @OA\Items(type="string", example="https://127.0.0.1:8000")
     *                             }),
     *                             @OA\Property(property="accept-encoding", type="array", items={
     *                                 @OA\Items(type="string", example="gzip, deflate, br, zstd")
     *                             }),
     *                             @OA\Property(property="accept-language", type="array", items={
     *                                 @OA\Items(type="string", example="ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7")
     *                             }),
     *                             @OA\Property(property="cookie", type="array", items={
     *                                 @OA\Items(type="string", example="_ym_uid=1; _ym_d=7; _ym_isad=1; laravel_token=testToken; XSRF-TOKEN=testXSRF; orb_rsaag_session=rsaagSessionToken")
     *                             }),
     *                         ),
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     * Функция отдаёт логи системы
     * @param Request $request
     * @return array
     */
    public function getLogs(GetLogsRequest $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $orderBy = $request->get('order_by', 'date_created');
        $isAsc = $request->get('is_asc', 'false');

        $filters = $request->only([
            'max_id',
            'min_id',
            'max_date',
            'min_date',
            'method_get',
            'method_post',
            'method_others',
            'ip'
        ]);

        $offset = ($page - 1) * $perPage;

        $sql_filters = [];
        if (isset($filters['max_id'])) {
            $sql_filters[] = ['id', '<=', $filters['max_id']];
        }

        if (isset($filters['min_id'])) {
            $sql_filters[] = ['id', '>=', $filters['min_id']];
        }

        if (isset($filters['max_date'])) {
            $sql_filters[] = ['date_created', '<=', $filters['max_date']];
        }

        if (isset($filters['min_date'])) {
            $sql_filters[] = ['date_created', '>=', $filters['min_date']];
        }

        if (isset($filters['ip'])) {
            $sql_filters[] = ['ip', 'like', '%' . $filters['ip'] . '%'];
        }

        $sql = DB::connection('editor')->table('logs.log_route')
            ->orderBy($orderBy, $isAsc == 'true' ? 'asc' : 'desc');
        $countSql = DB::connection('editor')->table('logs.log_route');

        if ($orderBy != 'id') {
            $sql->orderBy('id', $isAsc == 'true' ? 'asc' : 'desc');
        }

        if (count($sql_filters) > 0) {
            $sql->where($sql_filters);
            $countSql->where($sql_filters);
        }

        if (isset($filters['method_get']) && $filters['method_get'] == 'true') {
            $sql->orWhere('method', '=', 'GET');
            $countSql->orWhere('method', '=', 'GET');
        }
        if (isset($filters['method_post']) && $filters['method_post'] == 'true') {
            $sql->orWhere('method', '=', 'POST');
            $countSql->orWhere('method', '=', 'POST');
        }
        if (isset($filters['method_others']) && $filters['method_others'] == 'true') {
            $sql->orWhere([['method', '<>', 'GET'], ['method', '<>', 'POST']]);
            $countSql->orWhere([['method', '<>', 'GET'], ['method', '<>', 'POST']]);
        }

        $logs = $sql->limit($perPage)->offset($offset)->get()->all();
        foreach ($logs as &$log) {
            $log->get = json_decode($log->get);
            $log->post = json_decode($log->post);
            $log->header = json_decode($log->header);
        }
        $countLogs = $countSql->count();

        return [
            'data' => ['countLogs' => $countLogs, 'logs' => $logs],
            'status' => true,
        ];
    }

    /**
     * Функция отдаёт пользовательское соглашение
     * @param Request $request
     * @return array
     */
    public function getAgreement(Request $request)
    {
        $agreement = DB::table('public.user_agreement')
            ->select('agreement')
            ->orderBy('date_publ', 'desc')
            ->first();
        return ['status' => true, 'data' => $agreement->agreement];
    }

    /**
     * @OA\Post(path="/admin/api/set_agreement", tags={"Admin"}, summary="Создание соглашения пользователя",
     *     description="Оно одно на всех там таблица из 2 столбцов дата создания и текст",
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="text_agreement", type="string", description="Текст соглашения")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешная операция",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="true")
     *         )
     *     ),
     * )
     *
     * Изменение пользовательского соглашения
     * @param Request $request
     * @return array
     */
    public function setAgreement(Request $request)
    {
        $text = $request->post('text_agreement');
        $res = DB::table('public.user_agreement')
            ->insert([
                'date_publ' => date('Y-m-d'),
                'agreement' => $text
            ]);
        return ['status' => $res];
    }

    /**
     * @OA\Post(path="/admin/api/users/{id}/restore", tags={"Users"}, summary="Восстановление пользователя",
     *     @OA\Parameter(name="id", in="path", description="ID пользователя", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="integer", default=0),
     *             @OA\Property(property="status", type="boolean", default="true")
     *         )
     *     )
     * )
     *
     * Востановление удалённого пользователя
     * @param Integer $id
     * @param Request $request
     * @return array
     */
    public function restoreUser($id, Request $request)
    {
        $validator = Validator::make(['user_id' => $id], ['user_id' => 'required|numeric']);

        if ($validator->fails()) {
            return [
                'errors' => $validator->errors()->messages(),
                'status' => false,
            ];
        }

        return [
            'data' => $this->setUserProp($id, 'is_deleted', false),
            'status' => true,
        ];
    }

    /**
     * @OA\Get(path="/admin/api/get_description", tags={"Admin"}, summary="Получение описания системы",
     *     description="Оно одно на всех там таблица из 4 столбцов key, id, value, updated_at и всего одна запись",
     *     @OA\Response(response=200, description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="data", type="string", example="Сервис предназначен для авторизации"),
     *         )
     *     )
     * )
     * Получение описания системы
     * @param Request $request
     * @return array
     */
    public function getSystemDescription(Request $request)
    {
        $description = DB::table('public.system_attrs')->where('key', 'description')->first();
        if ($description) {
            return ['status' => true, 'data' => $description->value];
        } else {
            return ['status' => true, 'data' => null];
        }
    }

    /**
     * @OA\Post(path="/admin/api/set_description", tags={"Admin"}, summary="Установка описания системы",
     *     description="Оно одно на всех там таблица из 4 столбцов key, id, value, updated_at и всего одна запись",
     *     @OA\RequestBody(required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="description", type="string", example="Описание системы.")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Успешно сохранено или обновлено",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     * )
     *
     * Изменение описания системы
     * @param Request $request
     * @return array
     */
    public function setSystemDescription(Request $request)
    {
        $text = $request->post('description');
        $validator = Validator::make([
            'description' => $text,
        ], [
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return [
                'errors' => $validator->errors()->messages(),
                'status' => false,
            ];
        }

        $description = DB::table('public.system_attrs')->where('key', 'description')->first();
        if ($description) {
            $status = (boolean)DB::table('public.system_attrs')->where('key', 'description')->update(['value' => $text, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            $status = DB::table('public.system_attrs')->insert(['key' => 'description', 'value' => $text]);
        }
        return ['status' => $status];
    }

    public function loginLikeUser(Request $request)
    {
        $id = $request->get('id');
        $validator = Validator::make([
            'id' => $id,
        ], [
            'id' => ['required', 'integer', Rule::exists('users')],
        ]);

        if ($validator->fails()) {
            abort(404);
        }

        $res = Auth::loginUsingId($id);

        if (!$res) {
            abort(404);
        }

        return redirect()->route('home');
    }
}
