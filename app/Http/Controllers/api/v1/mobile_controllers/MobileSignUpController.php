<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MobileSignUpController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'first_name' =>
                    'required|string|max:255',

                'last_name' =>
                    'required|string|max:255',

                'email' =>
                    'required|email|unique:users,usr_email',

                'mobile' =>
                    'required|string|max:15|unique:users,usr_mobile',

                'password' =>
                    ['required', 'string', 'min:8', 'max:64', 'confirmed', 'regex:/^(?=.*[a-zA-Z])(?=.*\d).{8,64}$/'],

                'middle_name' =>
                    'nullable|string|max:255',

                'birth_date' =>
                    'nullable|date',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::table('users')->insert([
            'usr_uuid' =>
                generateuuid(),

            'branch_id' =>
                2,

            'utyp_id' =>
                3,

            'usr_first_name' =>
                strtoupper(
                    $request->first_name
                ),

            'usr_middle_name' =>
                $request->middle_name
                    ? strtoupper(
                        $request->middle_name
                    )
                    : null,

            'usr_last_name' =>
                strtoupper(
                    $request->last_name
                ),

            'usr_email' =>
                strtolower(
                    trim($request->email)
                ),

            'usr_mobile' =>
                $request->mobile,

            // ========================================================
            // PASSWORD
            // Keep compatible with the existing web/admin system.
            // ========================================================

            'usr_password' =>
                md5(
                    $request->password
                ),

            'usr_birth_date' =>
                $request->birth_date,

            'usr_date_created' =>
                now(),
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'User registered successfully',
        ], 201);
    }
}
