<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IWalkClient extends Model
{
    protected $table = 'i_walk.d_clients';

    protected $fillable = ['client_id'];
}
