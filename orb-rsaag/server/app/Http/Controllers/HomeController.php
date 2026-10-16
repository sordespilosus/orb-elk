<?php

namespace App\Http\Controllers;

use App\Http\Requests\{
    GetClientAgreementRequest,
    GetUserNotificationsRequest,
    RedirectToContourRequest
};
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\View\View;
use Laravel\Passport\Passport;

class HomeController extends Controller
{
    /**
     * Функция отдаёт список внешних систем для главной страницы.
     *
     */
    public function index()
    {
        $user = Auth::user();
        $clients = $this->getClients($user->id);

        $isEsiaUser = $user->getAttribute('esia_user_id') ? 'true' : 'false';
        $needEsiaFields = $user->getAttribute('esia_info') ? 'false' : 'true';

        /*$userRefs = DB::table('organizations.ref_user_organization')->where('user_id', $user->getAttribute('id'))
            ->get()->all();*/
        $userOrgs = DB::table('organizations.ref_user_organization')->where('user_id', $user->getAttribute('id'))
            ->leftJoin('organizations.organizations', 'organizations.ref_user_organization.org_id', '=', 'organizations.organizations.id')
            ->select('organizations.organizations.id AS id',
                'organizations.organizations.full_name AS full_name',
                'organizations.organizations.short_name AS short_name')
            //'organizations.organizations.inn AS inn')
            ->where('organizations.organizations.inn', '<>', null)
            ->get()->all();

        if ($userOrgs) {
            $isOrgUser = 'true';
        } else {
            $isOrgUser = 'false';
        }

        /*$arrOrgId = [];
        foreach ($userRefs as $ref){
            $arrOrgId[] = $ref->org_id;
        }
        $userOrgs = DB::table('organizations.organizations')->whereIn('id', $arrOrgId)->select('id', 'full_name', 'short_name', 'inn')->get()->all();*/

        return view('home', [
            'clients' => $clients,
            'isUserEsia' => $isEsiaUser,
            'needEsiaFields' => $needEsiaFields,
            'userId' => $user->getAttribute('id'),
            'isOrgUser' => $isOrgUser,
            'userOrgs' => $userOrgs,
        ]);
    }

    /**
     * Функция отдаёт пользовательское соглашение
     *
     * @return array|Factory|Application|View
     */
    public function agreement()
    {
        $description = DB::table('public.system_attrs')
            ->where('key', 'description')
            ->first();

        $agreement = DB::table('public.user_agreement')
            ->select('agreement')
            ->orderBy('date_publ', 'desc')
            ->first();

        return view('agreement', [
            'agreement' => $agreement->agreement,
            'description' => $description ? $description->value : ''
        ]);
    }

    /**
     * Функция возвращает пользовательское соглашение внешней системы
     * @param Request $request
     * @return array
     */
    public function getClientAgreement(GetClientAgreementRequest $request)
    {
        $clientId = $request->get('client_id');

        $agreementRow = DB::table('public.client_agreement')
            ->where('client_id', $clientId)
            ->orderBy('id', 'desc')
            ->first();

        if ($agreementRow) {
            return ['status' => true, 'data' => $agreementRow->agreement];
        } else {
            return ['status' => false, 'errors' => 'Соглашение не найдено'];
        }
    }

    public function redirectTo(RedirectToContourRequest $request, $contour)
    {
        $redirectUrl = $request->get('redirect_url');
        if (strpos
            (
                $redirectUrl,
                env('URL_CONTOUR_' . strtoupper($contour), 'https://cabinet-test.permkrai.ru')
            ) === false) {
            abort(404);
        }
        return redirect($redirectUrl);
    }

    /**
     * Получение нотификаций пользователя
     * @param Request $request
     * @return array
     */
    public function getUserNotices(GetUserNotificationsRequest $request)
    {
        $clientId = $request->get('client_id');
        $page = $request->get('page');
        $perPage = $request->get('per_page', 25);

        $notices = DB::select('SELECT * FROM notices.get_notice_rsaag(
                                            in_user_id => :user_id,
                                            in_system_id => :client_id,
                                            in_offset => :row_start,
                                            in_limit => :cnt_rows
                                        )', [
            'user_id' => Auth::id(),
            'client_id' => $clientId,
            'row_start' => ($page - 1) * $perPage,
            'cnt_rows' => $perPage
        ]);

        $totalPages = count($notices) ? ceil(end($notices)->notice_cnt / $perPage) : 1;
        $newNoticeCnt = count($notices) ? end($notices)->new_notice_cnt : 0;

        $notices = array_map(function ($item) {
            $item->notice_date = date('d.m.Y H:i', strtotime($item->notice_date));
            return $item;
        }, $notices);

        return ['status' => true, 'data' => ['notices' => $notices, 'totalPages' => $totalPages, 'newNoticeCnt' => $newNoticeCnt]];
    }

    /**
     * Внешние системы
     * @param int|null $userId
     * @return array
     */
    private function getClients(int $userId = null): array
    {
        $clients = DB::select('select * from oauth.get_oauth_clients(in_user_id => :user_id)', ['user_id' => $userId]);

        return array_map(function ($client) {
            $scope = Passport::$scopes;
            if (isset($client->scope_required))
                $client->scope_required = json_decode($client->scope_required);

            if ($client->scope) {
                $client->scope = json_decode($client->scope);
                $newScopes = [];
                foreach ($client->scope as $value) {
                    if (isset($scope[$value]))
                        $newScopes[$value] = $scope[$value];
                }
                $client->scope = (object)$newScopes;
            }
            if (isset($client->esia_scope)) {
                $client->esia_scope = json_decode($client->esia_scope);
                $newEsiaScopes = [];
                foreach ($client->esia_scope as $esiaScope) {
                    $newEsiaScopes[$esiaScope->id] = $esiaScope->description;
                }
                $client->esia_scope = $newEsiaScopes;
            }
            return $client;
        }, $clients);
    }

    /**
     * Получение нотификаций пользователя
     * @param Request $request
     * @return array
     */
    public function getClientsAjax(Request $request)
    {
        $clients = $this->getClients(Auth::id());
        return ['status' => true, 'data' => ['clients' => $clients]];
    }
}
