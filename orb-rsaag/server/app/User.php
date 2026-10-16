<?php

namespace App;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\{Artisan, DB};
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Атрибуты, которые должны быть преобразованы к базовым типам.
     */
    protected $casts = [
        'esia_info' => 'object',
        'passport' => 'object',
        'latest_esia_scope' => 'array',
        //'latest_auth_at' => 'timestamp'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'surname', 'patroname',
        'phone', 'city', 'birthday', 'gender',
        'esia_user_id', 'password', 'activation_hash', 'vk_user_id',
        'email_verified_at', 'login', 'fio', 'fb_user_id',
        'snils', 'passport', 'is_old', 'esia_info',
        'is_accepted', 'age_period',
        'vk_updated_at', 'fb_updated_at', 'esia_updated_at',
        'international_passport', 'inn', 'pension_certificate',
        'driver_license', 'latest_esia_scope',
        'latest_auth_method', 'latest_auth_at',
        'registration_method'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'activation_hash', 'esia_user_id', 'esia_info',
        'vk_user_id', 'email_verified_at', 'fb_user_id', 'is_old',
        'latest_auth_method', 'latest_auth_at',
    ];

    public function emailsChanges(): HasMany
    {
        return $this->hasMany('App\EmailsChange');
    }

    public function accessKeys(): HasMany
    {
        return $this->hasMany('App\AccessKey');
    }

    /**
     * Связь с клиентскими соглашениями.
     */
    public function clientAgreements(): BelongsToMany
    {
        return $this->belongsToMany('App\User', 'public.ref_user_client_agreement', 'user_id', 'client_agreement');
    }

    /**
     * Связь с организациями.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany('App\UsersOrganization', 'organizations.ref_user_organization', 'user_id', 'org_id')->withTimestamps()->withPivot('email', 'phone', 'chief');
    }

    public function userOrganizations(): HasMany
    {
        return $this->hasMany('App\RefUserOrganization');
    }

    /**
     * Связь с изменениями клинетов
     */
    public function clientsChanges(): HasMany
    {
        return $this->hasMany('App\OauthClientsChanges', 'user_id');
    }

    /**
     * Связь с причинами отклонений систем
     */
    public function clientsReasonsForRejection(): HasMany
    {
        return $this->hasMany('App\ReasonsForRejection', 'user_id');
    }

    public function authLogs(): HasMany
    {
        return $this->hasMany('App\AuthLog');
    }

    /**
     * Отправка уведомления о сбросе пароля.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $mailId = DB::table('mailer.user_mails')->insertGetId([
            'email' => $this->getAttribute('email'),
            'type' => 'reset_password',
            'params' => json_encode(['token' => $token]),
        ]);
        $exitCode = Artisan::call('send_emails', [
            '--id' => $mailId
        ]);
    }

    /**
     * Функция возвращает поля пользователя.
     *
     * @return array
     */
    public static function getFields(): array
    {
        return [
            'name', 'email', 'surname',
            'patroname', 'phone', 'city',
            'birthday', 'gender', 'esia_user_id',
            'password', 'activation_hash', 'vk_user_id',
            'email_verified_at', 'login', 'fio',
            'fb_user_id', 'snils', 'passport',
            'is_old', 'esia_info', 'is_accepted',
            'age_period', 'vk_updated_at', 'fb_updated_at',
            'esia_updated_at', 'latest_esia_scope',
            'international_passport', 'inn', 'pension_certificate',
            'driver_license', 'latest_auth_method', 'latest_auth_at',
            'registration_method'
        ];
    }

    /**
     * Функция устанавливает для пользователя экспортируемым в массив только набор переданных полей.
     * @param array $fields
     * @return void
     */
    public function setShownFields(array $fields = ['email', 'fio'])
    {
        $hiddenFields = array_diff($this->getFields(), $fields);
        $this->hidden = $hiddenFields;
    }

    public function getEsiaLevel()
    {
        return isset($this->esia_info) && isset($this->esia_info->trusted) ? $this->esia_info->trusted : null;
    }

    /**
     * Функция возвращает поля пользователя.
     * @param string $value
     * @return string
     */
    public function getLatestAuthAtAttribute($value): string
    {
        return date('Y-m-d H:i:s.u P', strtotime($value));
    }

    /**
     * Функция возвращает поля пользователя.
     * @param Carbon $value
     * @return void
     */
    public function setLatestAuthAtAttribute($value)
    {
        $this->attributes['latest_auth_at'] = $value->format('Y-m-d H:i:s.u P');
    }

    /**
     * Проверка наличия данных по скоупам у пользователя
     * @param array|null $scopes
     * @return array
     */
    public function checkScopeData($scopes)
    {
        $getEsiaField = function ($field, $isRow = false) {
            if ($isRow) {
                return (
                    isset($this->esia_info->rowEsiaData) && isset($this->esia_info->rowEsiaData->$field)
                ) ? $this->esia_info->rowEsiaData->$field : null;
            } else {
                return (
                    isset($this->esia_info) && isset($this->esia_info->$field)
                ) ? $this->esia_info->$field : null;
            }
        };
        $res = [];
        if ($scopes) {
            foreach ($scopes as $scope) {
                $has = true;
                switch ($scope) {
                    case 'email':
                    {
                        $email = strripos($this->email, '3cR2UVKx6PQj7bJM0Rx2uehR2GHSSd19bAxk8F2EC2Qf7ySwjSxbsMcZwQsYFkfy75MAFhjr1vpmgwzIuDnMpOJdxu8xGCmKhR2@LOGIN') === false ? $this->email : null;
                        if (!isset($email)) {
                            $has = false;
                        }
                        break;
                    }
                    case 'phone':
                    {
                        if (!isset($this->phone)) {
                            $has = false;
                        }
                        break;
                    }
                    case 'snils':
                    case 'passport':
                    {
                        $data = $getEsiaField($scope);
                        if (!isset($data)) {
                            $has = false;
                        }
                        break;
                    }
                    case 'esia_address':
                    {
                        $data = (bool)$getEsiaField('address', true);
                        if (!isset($data)) {
                            $has = false;
                        }
                        break;
                    }
                    default:
                    {
                        if (!isset($this->$scope)) {
                            $has = false;
                        }
                        break;
                    }
                }
                if (!$has) {
                    $res[] = $scope;
                }
            }
        }
        return $res;
    }

    /**
     * Устанавливает последний метод авторизации и проставляет время авторизации
     * @param string $method
     * @return void
     */
    public function setLatestAuthMethod($method)
    {
        $this->latest_auth_at = Carbon::now();
        $this->latest_auth_method = $method;
        $this->save();
    }

    /**
     * Проверка является ли пользователь доступным (активированным, не заблокированным, не удалённым)
     * @param bool $needActivate
     * @return void
     * @throws
     */
    public function checkAvailable($needActivate = true)
    {
        if ($this->email_verified_at == null && $needActivate) {
            throw new Exception('', 11);
        }
        if ($this->blocked) {
            throw new Exception('', 16);
        }
        if ($this->is_deleted) {
            throw new Exception('', 17);
        }
    }


    public function iwalk_client(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(IWalkClient::class, 'client_id', 'id');
    }
}
