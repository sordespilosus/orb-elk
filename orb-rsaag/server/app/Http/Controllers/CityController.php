<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;


class CityController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function list()
    {
        return DB::select('select * from dicts.get_tree_areas(in_area_lvl => 1)');
    }
}
