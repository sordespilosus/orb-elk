<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EsiaScope extends Model
{
    /**
     * Связанная с моделью таблица.
     */
    protected $table = 'esia.esia_scopes';
    /**
     * Атрибуты, которые должны быть преобразованы к базовым типам.
     */
    protected $casts = [
        'scopes' => 'array',
        'fields' => 'array',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы к датам.
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Клиенты, использующие скоуп
     */
    public function oauthClients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany('App\OauthClient', 'esia.ref_esia_scopes__oauth_clients', 'esia_scope_id', 'oauth_client_id');
    }

    /**
     * Связь со связью скоупа и клиента
     */
    public function refEsiaScopesOauthClient(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('App\RefEsiaScopesOauthClient', 'esia_scope_id');
    }
}
