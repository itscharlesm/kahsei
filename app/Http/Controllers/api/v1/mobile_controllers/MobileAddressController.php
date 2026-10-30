<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileAddressController extends Controller
{
    // ============================================================
    // STORE ADDRESS
    // ============================================================

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'add_id' => 'required|integer',
            'uadd_street' => 'required|string',
            'uadd_barangay' => 'nullable|string',
            'uadd_city' => 'required|string',
            'uadd_province' => 'nullable|string',
            'uadd_region' => 'nullable|string',
            'uadd_longitude' => 'nullable|numeric',
            'uadd_latitude' => 'nullable|numeric',
        ]);

        $user = DB::table('users')
            ->where('usr_email', $request->email)
            ->where('usr_active', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        DB::table('user_addresses')->insert([
            'usr_id' => $user->usr_id,
            'add_id' => $request->add_id,

            'uadd_street' => normalizeAddressStreet($request->uadd_street),
            'uadd_barangay' => normalizeAddressStreet($request->uadd_barangay),
            'uadd_city' => normalizeAddressStreet($request->uadd_city),
            'uadd_province' => normalizeAddressStreet($request->uadd_province),
            'uadd_region' => normalizeAddressStreet($request->uadd_region),

            'uadd_longitude' => $request->uadd_longitude,
            'uadd_latitude' => $request->uadd_latitude,

            'uadd_date_created' => now(),
            'uadd_created_by' => $user->usr_id,

            // 1 = active
            // 0 = inactive / removed
            'uadd_active' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address saved successfully.',
        ], 201);
    }

    // ============================================================
    // USED ADDRESS TYPES
    // ============================================================

    public function usedTypes(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = DB::table('users')
            ->where('usr_email', $request->email)
            ->where('usr_active', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $usedTypes = DB::table('user_addresses')
            ->where('usr_id', $user->usr_id)

            // Only currently active addresses count.
            ->where('uadd_active', 1)

            ->pluck('add_id')
            ->map(
                fn ($id) => (int) $id
            )
            ->values();

        return response()->json([
            'success' => true,
            'data' => $usedTypes,
        ]);
    }

    // ============================================================
    // LIST ADDRESSES
    // ============================================================

    public function list(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = DB::table('users')
            ->where('usr_email', $request->email)
            ->where('usr_active', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'data' => [],
            ], 404);
        }

        $addresses = DB::table('user_addresses')
            ->where('usr_id', $user->usr_id)

            // Only show active addresses.
            ->where('uadd_active', 1)

            ->select(
                'uadd_id',
                'usr_id',
                'add_id',
                'uadd_street',
                'uadd_barangay',
                'uadd_city',
                'uadd_province',
                'uadd_region',
                'uadd_longitude',
                'uadd_latitude',
                'uadd_active'
            )

            ->orderBy(
                'uadd_date_created',
                'desc'
            )

            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    // ============================================================
    // UPDATE ADDRESS
    // ============================================================

    public function update(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'uadd_id' => 'required|integer',

            'uadd_street' => 'required|string',
            'uadd_barangay' => 'required|string',
            'uadd_city' => 'required|string',
            'uadd_province' => 'required|string',
            'uadd_region' => 'required|string',

            'uadd_longitude' => 'nullable|numeric',
            'uadd_latitude' => 'nullable|numeric',
        ]);

        $user = DB::table('users')
            ->where('usr_email', $request->email)
            ->where('usr_active', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $address = DB::table('user_addresses')
            ->where('uadd_id', $request->uadd_id)
            ->where('usr_id', $user->usr_id)

            // Do not allow editing an inactive/removed address.
            ->where('uadd_active', 1)

            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], 404);
        }

        $updateData = [
            'uadd_street' => normalizeAddressStreet($request->uadd_street),
            'uadd_barangay' => normalizeAddressStreet($request->uadd_barangay),
            'uadd_city' => normalizeAddressStreet($request->uadd_city),
            'uadd_province' => normalizeAddressStreet($request->uadd_province),
            'uadd_region' => normalizeAddressStreet($request->uadd_region),

            'uadd_date_modified' => now(),
            'uadd_modified_by' => $user->usr_id,
        ];

        // Only update coordinates when supplied.
        if ($request->filled('uadd_longitude')) {
            $updateData['uadd_longitude'] =
                $request->uadd_longitude;
        }

        if ($request->filled('uadd_latitude')) {
            $updateData['uadd_latitude'] =
                $request->uadd_latitude;
        }

        DB::table('user_addresses')
            ->where('uadd_id', $request->uadd_id)
            ->where('usr_id', $user->usr_id)
            ->where('uadd_active', 1)
            ->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
        ]);
    }

    // ============================================================
    // REMOVE ADDRESS
    // SOFT DELETE ONLY
    // ============================================================

    public function delete(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'uadd_id' => 'required|integer',
        ]);

        $user = DB::table('users')
            ->where('usr_email', $request->email)
            ->where('usr_active', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $address = DB::table('user_addresses')
            ->where('uadd_id', $request->uadd_id)
            ->where('usr_id', $user->usr_id)

            // Only active addresses can be removed.
            ->where('uadd_active', 1)

            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Address not found or already removed.',
            ], 404);
        }

        // ========================================================
        // IMPORTANT:
        // DO NOT physically delete the record.
        //
        // Simply deactivate it.
        //
        // 1 = ACTIVE
        // 0 = INACTIVE / REMOVED
        // ========================================================

        DB::table('user_addresses')
            ->where('uadd_id', $request->uadd_id)
            ->where('usr_id', $user->usr_id)
            ->where('uadd_active', 1)
            ->update([
                'uadd_active' => 0,
                'uadd_date_modified' => now(),
                'uadd_modified_by' => $user->usr_id,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Address removed successfully.',
        ], 200);
    }

    // ============================================================
    // LEGACY SET PRIMARY
    // ============================================================
    //
    // Primary/default address is no longer used by the mobile app.
    //
    // We are intentionally NOT modifying uadd_active here because
    // uadd_active now strictly means:
    //
    //     1 = ACTIVE
    //     0 = INACTIVE / REMOVED
    //
    // Keep this method only if an existing route still references it.
    // It should not be called by the current Flutter address page.
    // ============================================================

    public function setPrimary(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' =>
                'Default address selection is no longer available.',
        ], 410);
    }
}