<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AuthLog extends Model
{
    /**
     * Связанная с моделью таблица.
     */
    protected $table = 'logs.auth_logs';
    public $timestamps = false;
    protected $fillable = ['user_id', 'auth_method', 'client_id'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\User');
    }
}
