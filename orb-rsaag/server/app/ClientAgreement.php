<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientAgreement extends Model
{
    /**
     * Связанная с моделью таблица.
     */
    protected $table = 'public.client_agreement';

    protected $fillable = ['client_id', 'agreement', 'created_at'];

    public $timestamps = false;

    /**
     * связь с клиентом.
     */
    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\OauthClient', 'client_id');
    }

    /**
     * связь со связью пользователя и соглашения.
     */
    public function refUserClientAgreements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('App\RefUserClientAgreement', 'client_agreement');
    }

    /**
     * Связь с пользователями.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany('App\User', 'public.ref_user_client_agreement', 'client_agreement');
    }
}
