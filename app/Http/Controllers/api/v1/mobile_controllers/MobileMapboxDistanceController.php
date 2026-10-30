<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileMapboxDistanceController extends Controller
{
    public static function getDrivingDistanceKm(
        $branchLongitude,
        $branchLatitude,
        $userLongitude,
        $userLatitude
    ) {
        if (
            $branchLongitude === null ||
            $branchLatitude === null ||
            $userLongitude === null ||
            $userLatitude === null
        ) {
            Log::error('Mapbox distance failed: missing coordinates');
            return null;
        }

        $branchLongitude = (float) $branchLongitude;
        $branchLatitude = (float) $branchLatitude;
        $userLongitude = (float) $userLongitude;
        $userLatitude = (float) $userLatitude;

        $mapboxToken = config('services.mapbox.token');

        if (!$mapboxToken) {
            Log::error('Mapbox distance failed: missing MAPBOX_ACCESS_TOKEN');
            return null;
        }

        $url = 'https://api.mapbox.com/directions/v5/mapbox/driving/'
            . $branchLongitude . ',' . $branchLatitude . ';'
            . $userLongitude . ',' . $userLatitude;

        try {
            $response = Http::timeout(8)->get($url, [
                'access_token' => $mapboxToken,
                'overview' => 'false',
                'geometries' => 'geojson',
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (
                !isset($data['routes']) ||
                empty($data['routes']) ||
                !isset($data['routes'][0]['distance'])
            ) {
                Log::error('Mapbox distance failed: no route distance found', [
                    'data' => $data,
                ]);

                return null;
            }

            $distanceMeters = (float) $data['routes'][0]['distance'];

            return round($distanceMeters / 1000);
        } catch (\Exception $e) {
            Log::error('Mapbox distance exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}