<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MobileServicePackageController extends Controller
{
    public function list()
    {
        $packages = DB::table('service_packages')
            ->where('svcp_active', 1)
            ->select(
                'svcp_id',
                'svcp_pest_type'
            )
            ->orderBy('svcp_pest_type', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    public function areas($branch_id)
    {
        $areas = DB::table('service_package_areas')
            ->where('branch_id', $branch_id)
            ->where('svcpa_active', 1)
            ->select(
                'svcpa_id',
                'branch_id',
                'svcpa_area',
                'svcpa_cost'
            )
            ->orderBy('svcpa_area', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $areas,
        ]);
    }
}