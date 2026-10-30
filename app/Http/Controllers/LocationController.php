<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class LocationController extends Controller
{
    public function provinces($region)
    {
        return DB::table('location_provinces')
            ->where('reg_id', $region)
            ->where('prov_active', 1)
            ->orderBy('prov_name')
            ->get(['prov_id', 'prov_name']);
    }

    public function municipalities($province)
    {
        return DB::table('location_municipalities')
            ->where('prov_id', $province)
            ->where('mun_active', 1)
            ->orderBy('mun_name')
            ->get(['mun_id', 'mun_name']);
    }

    public function barangays($municipality)
    {
        return DB::table('location_barangays')
            ->where('mun_id', $municipality)
            ->where('brg_active', 1)
            ->orderBy('brg_name')
            ->get(['brg_id', 'brg_name']);
    }
}
