<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserMail extends Model
{
    protected $table = 'mailer.user_mails';
    protected $guarded = [];
}
