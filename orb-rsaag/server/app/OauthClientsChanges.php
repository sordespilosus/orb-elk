<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthClientsChanges extends Model
{
    /**
     * Связанная с моделью таблица.
     */
    protected $table = 'clients.oauth_clients_changes';

    /**
     * Атрибуты, которые должны быть преобразованы к базовым типам.
     */
    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Формат хранения отметок времени модели.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u P';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'client_id', 'user_id', 'changes', 'accepted_status', 'reason', 'moderator_id', 'summary_id', 'updated_at'
    ];

    /**
     * Связь с системой клиентом
     */
    public function oauthClient(): BelongsTo
    {
        return $this->belongsTo('App\OauthClient', 'client_id');
    }

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
