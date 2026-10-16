<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\{Builder, Model};

class AccessKey extends Model
{
    protected $table = 'oauth.access_keys';
    protected $guarded = [];
    protected $fillable = ['user_id', 'access_key'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('orderCreated', function (Builder $builder) {
            $builder->orderBy('created_at');
        });
    }

    public function scopeActive(Builder $query)
    {
        $now = Carbon::now();
        $now->addMinutes(env('ACCESS_TOKEN_LIFETIME_MINUTES', 30) * -1);
        return $query->whereDate('created_at', '>=', $now->toDateTimeString());
    }

    public function scopeByAccessKey(Builder $query, $id, $token)
    {
        return $query->where([['user_id', $id], ['access_key', $token]]);
    }
}
