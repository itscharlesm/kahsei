<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MobileLocationController extends Controller
{
    public function regions()
    {
        $regions = DB::table('location_regions')
            ->select('reg_id', 'reg_name', 'reg_description')
            ->where('reg_active', 1)
            ->orderBy('reg_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    public function provinces($reg_id)
    {
        $provinces = DB::table('location_provinces')
            ->select('prov_id', 'reg_id', 'prov_name')
            ->where('reg_id', $reg_id)
            ->where('prov_active', 1)
            ->orderBy('prov_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $provinces,
        ]);
    }

    public function municipalities($prov_id)
    {
        $municipalities = DB::table('location_municipalities')
            ->select('mun_id', 'prov_id', 'mun_name')
            ->where('prov_id', $prov_id)
            ->where('mun_active', 1)
            ->orderBy('mun_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $municipalities,
        ]);
    }

    public function barangays($mun_id)
    {
        $barangays = DB::table('location_barangays')
            ->select('brg_id', 'mun_id', 'brg_name')
            ->where('mun_id', $mun_id)
            ->where('brg_active', 1)
            ->orderBy('brg_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $barangays,
        ]);
    }
}