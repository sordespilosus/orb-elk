<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RefUserClientAgreement extends Model
{
    /**
     * Связанная с моделью таблица.
     */
    protected $table = 'public.ref_user_client_agreement';

    /**
     * связь с клиентом.
     */
    public function agreement(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\OauthClient', 'client_agreement');
    }
}
