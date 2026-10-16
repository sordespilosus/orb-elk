<?php


namespace App\Http\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\{Auth, DB};
use Laravel\Passport\Passport;

trait OrganizationsTrait
{
    /**
     * Данные scope organizations_user
     * @param integer $orgId
     * @return array
     */
    public function getOrganizationsUser($orgId, $revokeTokens = true)
    {
        $user = Auth::user();
        $sql = DB::table('organizations.ref_user_organization')
            ->leftJoin('organizations.organizations', 'organizations.ref_user_organization.org_id', '=', 'organizations.organizations.id')
            ->where('organizations.ref_user_organization.user_id', $user->getAttribute('id'))
            ->select('organizations.organizations.id AS id',
                'organizations.organizations.full_name AS full_name',
                'organizations.organizations.short_name AS short_name',
                'organizations.organizations.ogrn AS ogrn',
                'organizations.organizations.inn AS inn',
                'organizations.organizations.org_type AS org_type',
                'organizations.ref_user_organization.email AS email',
                'organizations.ref_user_organization.phone AS phone',
                'organizations.ref_user_organization.chief AS chief');

        if ($orgId) {
            $sql->where('organizations.ref_user_organization.org_id', $orgId);
        }
        $org = $sql->get()->all();

        if ($revokeTokens && $user->token() != null) {
            $tokens = Passport::token()
                ->whereRevoked(0)
                ->where([
                    ['client_id', $user->token()->client->id],
                    ['expires_at', '>', Carbon::now()],
                    ['user_id', $user->getAttribute('id')]
                ])
                ->get();
            foreach ($tokens as $token) {
                $token->revoke();
            }
        }

        return $org;
    }

    /**
     * Получение моделей организаций
     * @param integer $orgId
     * @param bool $isConfirmed
     * @return array
     */
    public function getOrganizationsUserWithModel($orgId = null, $isConfirmed = false)
    {
        $user = Auth::user();
        if ($orgId) {
            $orgs = [$user->organizations->find($orgId)];
        } else {
            $orgs = $user->organizations;
            if ($isConfirmed)
                $orgs = $orgs->where('org_info', '<>', null);
        }

        return $orgs;
    }

    /**
     * Получение моделей организаций
     * @param array $orgs
     * @param string $format
     * @return array
     */
    public function normalizeOrgModel($orgs, $format = 'full')
    {
        $res = [];
        $pivots = ['email', 'phone', 'chief'];
        foreach ($orgs as $orgModel) {
            $org = $orgModel->getAttributes();
            $org['org_info'] = json_decode($org['org_info'], true);
            unset($org['created_at'], $org['updated_at']);
            foreach ($pivots as $pivot) {
                $org[$pivot] = $orgModel->pivot->$pivot;
            }
            switch ($format) {
                case 'short':
                {
                    $org['need_inn'] = !isset($org['org_info']);
                    unset($org['org_info'], $org['ogrn'], $org['org_type'], $org['inn']);
                    break;
                }
                case 'middle':
                {
                    unset($org['org_info']);
                    break;
                }
            }

            $res[] = $org;
        }
        return $res;
    }
}
