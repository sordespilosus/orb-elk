<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Passport\Passport;

class OauthClient extends Model
{
    /**
     * Атрибуты, которые должны быть преобразованы к базовым типам.
     */
    protected $casts = [
        'scope' => 'array',
        'scope_required' => 'array',
    ];

    /**
     * Функция преобразователь получения status_changed_at
     * @param string $value
     * @return string
     */
    public function getStatusChangedAtAttribute($value): string
    {
        return date('Y-m-d H:i:s.u P', strtotime($value));
    }

    /**
     * Функция преобразователь установки status_changed_at
     * @param Carbon $value
     * @return void
     */
    public function setStatusChangedAtAttribute($value)
    {
        $this->attributes['status_changed_at'] = $value->format('Y-m-d H:i:s.u P');
    }

    /**
     * Есиа скоупы, клиента
     */
    public function esiaScopes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany('App\EsiaScope', 'esia.ref_esia_scopes__oauth_clients', 'oauth_client_id', 'esia_scope_id');
    }

    /**
     * Связь с пользовательскими соглашениями.
     */
    public function clientAgreements(): HasMany
    {
        return $this->hasMany('App\ClientAgreement', 'client_id');
    }

    /**
     * Запрос не удалённых клиентов
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query): Builder
    {
        return $query->where('revoked', false);
    }

    /**
     * Запрос видимых клиентов
     * @param Builder $query
     * @return Builder
     */
    public function scopeVisible($query): Builder
    {
        return $query->where('is_hidden', '<>', true);
    }

    /**
     * Запрос прошедших проверку клиентов
     * @param Builder $query
     * @param string $status in ['rejected', 'approved', 'sent']
     * @return Builder
     */
    public function scopeStatus($query, $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Связь со связью скоупа и клиента
     */
    public function refEsiaScopesOauthClient(): HasMany
    {
        return $this->hasMany('App\RefEsiaScopesOauthClient', 'oauth_client_id');
    }

    /**
     * Связь с изменениями клинетов
     */
    public function clientsChanges(): HasMany
    {
        return $this->hasMany('App\OauthClientsChanges', 'client_id');
    }

    /**
     * Связь с причинами отклонений систем
     */
    public function clientsReasonsForRejection(): HasMany
    {
        return $this->hasMany('App\ReasonsForRejection', 'client_id');
    }

    public function setShort(): void
    {
        $this->hidden = [
            'secret', 'redirect', 'personal_access_client', 'password_client', 'revoked', 'created_at', 'updated_at', 'updated_at', 'dont_remember', 'is_hidden', 'ord', 'user_id'
        ];
    }

    /**
     * Функция возвращает поля пользователя.
     *
     * @return array
     */
    public static function getFields(): array
    {
        return [
            'user_id', 'name',
            'secret', 'redirect', 'personal_access_client',
            'password_client', 'revoked', 'created_at',
            'updated_at', 'url_login', 'url_notifications',
            'logo', 'need_esia', 'url_site',
            'is_hidden', 'ord', 'description',
            'need_organization', 'scope', 'only_esia',
            'dont_remember', 'scope_required', 'esia_level',
            'status',
        ];
    }

    /**
     * Создаёт валидатор параметров внешней системы
     * @param array $data
     * @param bool $requiredId
     * @param null $onlyStatuses
     * @return \Illuminate\Validation\Validator
     */
    public static function getValidatorClients($data, $requiredId = false, $onlyStatuses = null): \Illuminate\Validation\Validator
    {
        $scope = array_keys(Passport::$scopes);
        $esiaScopes = EsiaScope::all();
        $esiaScopesKeys = array_map(function ($item) {
            return $item->name;
        }, $esiaScopes->all());
        $fields = [
            'name_client' => 'required|string|max:255',
            'redirect' => 'required|array|min:1',
            'redirect.*' => 'required|url',
            'url_login' => 'nullable|url',
            'url_notifications' => 'nullable|url',
            'url_site' => 'required|url',
            'need_esia' => 'nullable|boolean',
            'only_esia' => 'nullable|boolean',
            'dont_remember' => 'nullable|boolean',
            'logo' => 'nullable|string',
            'is_hidden' => 'required|boolean',
            'ord' => 'nullable|numeric',
            'agreement' => 'nullable|string',
            'update_agreement' => 'nullable|boolean',
            'agreement_updated_at' => ['nullable', 'regex:/^(\d{4}-\d{2}-\d{2}|\d{2}\.\d{2}\.\d{4})$/'],
            'description' => 'nullable|string',
            'need_organization' => 'nullable|boolean',
            'scope' => ['nullable', 'array'],
            'scope.*' => ['nullable', Rule::in($scope)],
            'esia_scope' => ['nullable', 'array'],
            'esia_scope.*' => ['nullable', Rule::in($esiaScopesKeys)],
            'id' => [$requiredId ? 'required' : 'nullable', 'numeric', Rule::exists('oauth_clients', 'id')],
            'scope_required' => ['nullable', 'array'],
            'scope_required.*' => ['nullable', Rule::in($scope)],
            'esia_level' => ['nullable', Rule::in(['TRUSTED', 'STANDART', 'NONE'])],
            'status' => ['nullable', Rule::in(['rejected', 'approved', 'sent'])],
            'reasons' => ['nullable', 'string'],
            'without_oauth_notify' => 'nullable|boolean',
        ];
        if ($onlyStatuses) {
            $fields['status'] = [$requiredId ? 'required' : 'nullable', Rule::in($onlyStatuses)];
        }
        return Validator::make($data, $fields);
    }

    /**
     * Функция устанавливает для пользователя экспортируемым в массив только набор переданных полей.
     * @param array $fields
     * @return void
     */
    public function setShownFields(array $fields = ['name', 'url_site', 'description']): void
    {
        $hiddenFields = array_diff($this->getFields(), $fields);
        $this->hidden = $hiddenFields;
    }
}
