<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UsersOrganization extends Model
{
    /**
     * Связанная с моделью таблица.
     */
    protected $table = 'organizations.organizations';

    /**
     * Атрибуты, которые должны быть преобразованы к базовым типам.
     */
    protected $casts = [
        'org_info' => 'object',
    ];

    protected $fillable = ['inn', 'org_info'];

    /**
     * Связь со связью организации и пользователя.
     */
    public function userOrganizations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('App\RefUserOrganization', 'org_id');
    }

    /**
     * Связь с пользователями.
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany('App\User', 'organizations.ref_user_organization', 'org_id', 'user_id')->withTimestamps()->withPivot('email', 'phone', 'chief');
    }

}
