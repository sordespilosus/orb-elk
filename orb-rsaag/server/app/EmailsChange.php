<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailsChange extends Model
{
    protected $table = 'emails_changes';
    protected $guarded = [];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\User');
    }
}
