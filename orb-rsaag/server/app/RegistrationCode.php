<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistrationCode extends Model
{
    protected $table = 'oauth.d_registration_codes';

    public $timestamps = false;

    protected $fillable = ['code', 'is_used', 'expired_at', 'user_email'];

}
