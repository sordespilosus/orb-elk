<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AdminUsersQuery
{
    public static function filtered(array $filters = [], bool $withoutUnsubscribers = true): Builder
    {
        $query = DB::table('oauth.users as tu');

        if ($withoutUnsubscribers) {
            $query
                ->leftJoin('mailer.unsubscribed as ma', function ($join) {
                    $join->on('tu.id', '=', 'ma.id')
                        ->where('ma.active', '=', true);
                })
                ->whereNull('ma.id');
        }

        self::whereIlike($query, 'tu.surname', self::value($filters, 'second_name'));
        self::whereIlike($query, 'tu.name', self::value($filters, 'first_name'));
        self::whereIlike($query, 'tu.patroname', self::value($filters, 'patroname'));

        if (self::filled($filters, 'fio')) {
            $query->whereRaw(
                "concat_ws(' ', tu.surname, tu.name, tu.patroname) ilike ?",
                ['%' . self::value($filters, 'fio') . '%']
            );
        }

        if (self::filled($filters, 'city') && (int)self::value($filters, 'city') !== -2) {
            $query->where('tu.city', '=', self::value($filters, 'city'));
        }

        if (self::filled($filters, 'id')) {
            $query->where('tu.id', '=', self::value($filters, 'id'));
        }

        if (self::filled($filters, 'is_blocked')) {
            $query->where('tu.blocked', '=', self::toBoolean(self::value($filters, 'is_blocked')));
        }

        if (self::filled($filters, 'is_accepted')) {
            $query->where('tu.is_accepted', '=', self::toBoolean(self::value($filters, 'is_accepted')));
        }

        if (self::filled($filters, 'is_admin')) {
            if (self::toBoolean(self::value($filters, 'is_admin'))) {
                $query->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('roles.ref_user_roles as admin_filter')
                        ->whereColumn('admin_filter.user_id', 'tu.id')
                        ->where('admin_filter.role_id', '=', 1);
                });
            } else {
                $query->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('roles.ref_user_roles as admin_filter')
                        ->whereColumn('admin_filter.user_id', 'tu.id')
                        ->where('admin_filter.role_id', '=', 1);
                });
            }
        }

        if (self::filled($filters, 'gender') && self::value($filters, 'gender') !== 'none') {
            $query->where('tu.gender', '=', self::value($filters, 'gender'));
        }

        if (self::filled($filters, 'esia') && (int)self::value($filters, 'esia') !== -1) {
            if (self::toBoolean(self::value($filters, 'esia'))) {
                $query->whereNotNull('tu.esia_info');
            } else {
                $query->whereNull('tu.esia_info');
            }
        }

        if (self::filled($filters, 'age_period_from') && self::filled($filters, 'age_period_to')) {
            $query->whereRaw(
                "date_trunc('year', age(CURRENT_DATE, tu.birthday)) between make_interval(years => ?) and make_interval(years => ?)",
                [(int)self::value($filters, 'age_period_from'), (int)self::value($filters, 'age_period_to')]
            );
        } elseif (self::filled($filters, 'age_period_from')) {
            $query->whereRaw(
                "date_trunc('year', age(CURRENT_DATE, tu.birthday)) >= make_interval(years => ?)",
                [(int)self::value($filters, 'age_period_from')]
            );
        } elseif (self::filled($filters, 'age_period_to')) {
            $query->whereRaw(
                "date_trunc('year', age(CURRENT_DATE, tu.birthday)) <= make_interval(years => ?)",
                [(int)self::value($filters, 'age_period_to')]
            );
        }

        if (self::filled($filters, 'only_verified')) {
            if (self::toBoolean(self::value($filters, 'only_verified'))) {
                $query->whereNotNull('tu.email_verified_at');
            } else {
                $query->whereNull('tu.email_verified_at');
            }
        }

        return $query;
    }

    public static function selectRegistryColumns(Builder $query): Builder
    {
        return self::withAdminRole($query)->select([
            'tu.id',
            'tu.email',
            'tu.surname',
            'tu.name',
            'tu.patroname',
            'tu.phone',
            'tu.birthday',
            'tu.age_period',
            'tu.gender',
            'tu.esia_user_id',
            'tu.vk_user_id',
            'tu.fb_user_id',
            'tu.is_accepted',
            'tu.blocked',
            'tu.email_verified_at',
            'tu.fio',
            'tu.created_at',
            'tu.updated_at',
            'tu.is_deleted',
        ])
            ->selectRaw('case when admin_role.user_id is not null then 1 else 0 end as is_admin')
            ->selectRaw('count(1) over () as cnt_users');
    }

    public static function selectExportColumns(Builder $query): Builder
    {
        return self::withAdminRole($query)
            ->select([
                'tu.id',
                'tu.email',
                'tu.fio',
                'tu.gender',
                'tu.blocked',
                'tu.is_accepted',
                'tu.birthday',
                'da.area_name',
            ])
            ->selectRaw('case when admin_role.user_id is not null then 1 else 0 end as admin')
            ->selectRaw('case when tu.email_verified_at is not null then 1 else 0 end as email_verified')
            ->leftJoin('dicts.d_areas as da', 'da.area_id', '=', 'tu.city');
    }

    public static function applyOrder(Builder $query, ?string $orderBy, $isAsc, array $extraColumns = []): Builder
    {
        $columns = array_merge([
            'id' => 'tu.id',
            'oauth.users.id' => 'tu.id',
            'email' => 'tu.email',
            'oauth.users.email' => 'tu.email',
            'fio' => 'tu.fio',
            'oauth.users.fio' => 'tu.fio',
            'gender' => 'tu.gender',
            'oauth.users.gender' => 'tu.gender',
            'blocked' => 'tu.blocked',
            'oauth.users.blocked' => 'tu.blocked',
            'is_accepted' => 'tu.is_accepted',
            'oauth.users.is_accepted' => 'tu.is_accepted',
            'birthday' => 'tu.birthday',
            'oauth.users.birthday' => 'tu.birthday',
            'email_verified_at' => 'tu.email_verified_at',
            'oauth.users.email_verified_at' => 'tu.email_verified_at',
        ], $extraColumns);

        return $query->orderBy($columns[$orderBy] ?? 'tu.id', self::toBoolean($isAsc) ? 'asc' : 'desc');
    }

    private static function whereIlike(Builder $query, string $column, $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $query->where($column, 'ilike', '%' . $value . '%');
    }

    private static function withAdminRole(Builder $query): Builder
    {
        return $query->leftJoin('roles.ref_user_roles as admin_role', function ($join) {
            $join->on('admin_role.user_id', '=', 'tu.id')
                ->where('admin_role.role_id', '=', 1);
        });
    }

    private static function filled(array $filters, string $key): bool
    {
        return array_key_exists($key, $filters) && $filters[$key] !== null && $filters[$key] !== '';
    }

    private static function value(array $filters, string $key)
    {
        return $filters[$key] ?? null;
    }

    private static function toBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$value;
    }
}
