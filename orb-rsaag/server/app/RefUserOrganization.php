<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefUserOrganization extends Model
{
    /**
     * Связанная с моделью таблица.
     */
    protected $table = 'organizations.ref_user_organization';

    /**
     * связь с пользователем.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\User');
    }

    /**
     * связь с организацией.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo('App\UsersOrganization', 'org_id');
    }
}
