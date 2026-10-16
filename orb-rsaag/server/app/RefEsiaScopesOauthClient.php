<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefEsiaScopesOauthClient extends Model
{
    /**
     * Связанная с моделью таблица.
     */
    protected $table = 'esia.ref_esia_scopes__oauth_clients';

    /**
     * Атрибуты, которые должны быть преобразованы к датам.
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Связь с клиентом
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo('App\OauthClient', 'oauth_client_id');
    }

    /**
     * Связь со скоупом
     */
    public function scope(): BelongsTo
    {
        return $this->belongsTo('App\EsiaScope', 'esia_scope_id');
    }
}
