<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use App\Services\MobileTokenStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileAuthController extends Controller
{
    public function login(Request $request, MobileTokenStore $tokens)
    {
        // ============================================================
        // VALIDATION
        // ============================================================

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = trim($request->email);
        $password = $request->password;

        // ============================================================
        // FIND ACTIVE USER
        // ============================================================

        $user = DB::table('users')
            ->where('usr_email', $email)
            ->where('usr_active', 1)
            ->first();

        // ============================================================
        // INVALID USER
        // ============================================================

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email or password is incorrect.',
            ], 401);
        }

        // ============================================================
        // PASSWORD CHECK
        // Keep compatible with the existing web/admin login.
        // ============================================================

        $isValidPassword =
            $user->usr_password === md5($password);

        if (!$isValidPassword) {
            return response()->json([
                'success' => false,
                'message' => 'Email or password is incorrect.',
            ], 401);
        }

        // ============================================================
        // LOGIN SUCCESS
        // ============================================================

        $token = $tokens->issue((int) $user->usr_id);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',

            'user' => [
                'usr_id' =>
                    $user->usr_id,

                'usr_email' =>
                    $user->usr_email,

                'usr_first_name' =>
                    $user->usr_first_name,

                'usr_last_name' =>
                    $user->usr_last_name,

                'utyp_id' =>
                    (int) $user->utyp_id,
            ],

            'token' => $token,
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->usr_active) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'usr_id' =>
                    $user->usr_id,

                'usr_email' =>
                    $user->usr_email,

                'usr_first_name' =>
                    $user->usr_first_name,

                'usr_last_name' =>
                    $user->usr_last_name,

                'utyp_id' =>
                    (int) $user->utyp_id,
            ],
        ], 200);
    }

    public function logout(Request $request, MobileTokenStore $tokens)
    {
        $tokens->revoke($request->bearerToken());

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        // ============================================================
        // VALIDATION
        // ============================================================

        $request->validate([
            'identifier' =>
                'required|string|max:255',
        ]);

        $identifier =
            trim($request->identifier);

        // ============================================================
        // NORMALIZE MOBILE NUMBER
        // +639XXXXXXXXX -> 9XXXXXXXXX
        // 09XXXXXXXXX   -> 9XXXXXXXXX
        // ============================================================

        $normalizedMobile =
            preg_replace(
                '/^(\+?63|0)/',
                '',
                $identifier
            );

        // ============================================================
        // FIND ACTIVE USER
        // ============================================================

        $user = DB::table('users')
            ->where('usr_active', 1)
            ->where(function ($query) use (
                $identifier,
                $normalizedMobile
            ) {
                $query
                    ->where(
                        'usr_email',
                        $identifier
                    )
                    ->orWhere(
                        'usr_mobile',
                        $identifier
                    )
                    ->orWhere(
                        'usr_mobile',
                        $normalizedMobile
                    );
            })
            ->first();

        // ============================================================
        // USER NOT FOUND
        // ============================================================

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No active account was found with that email or mobile number.',
            ], 404);
        }

        // ============================================================
        // RESET PASSWORD
        // ============================================================

        DB::table('users')
            ->where(
                'usr_id',
                $user->usr_id
            )
            ->update([
                'usr_password' =>
                    md5('123456'),
            ]);

        // ============================================================
        // SUCCESS
        // ============================================================

        return response()->json([
            'success' => true,
            'message' =>
                'Password reset successful.',
            'temporary_password' =>
                '123456',
        ], 200);
    }

    public function changePassword(Request $request)
    {
        // ============================================================
        // VALIDATION
        // Match the mobile signup password rules.
        // ============================================================

        $request->validate([
            'email' =>
                'required|email',

            'current_password' =>
                'required|string',

            'new_password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d).{8,64}$/',
            ],

            'new_password_confirmation' =>
                'required|string',
        ]);

        $email =
            trim($request->email);

        $currentPassword =
            $request->current_password;

        $newPassword =
            $request->new_password;

        $confirmation =
            $request->new_password_confirmation;

        // ============================================================
        // FIND ACTIVE USER
        // ============================================================

        $user = DB::table('users')
            ->where(
                'usr_email',
                $email
            )
            ->where(
                'usr_active',
                1
            )
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Account not found.',
            ], 404);
        }

        // ============================================================
        // VERIFY CURRENT PASSWORD
        // ============================================================

        if (
            $user->usr_password !==
            md5($currentPassword)
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Your current password is incorrect.',
                'field' =>
                    'current_password',
            ], 422);
        }

        // ============================================================
        // NEW PASSWORD MUST BE DIFFERENT
        // ============================================================

        if (
            md5($newPassword) ===
            $user->usr_password
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Your new password must be different from your current password.',
                'field' =>
                    'new_password',
            ], 422);
        }

        // ============================================================
        // CONFIRM PASSWORD
        // ============================================================

        if (
            $newPassword !==
            $confirmation
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'New password and confirmation do not match.',
                'field' =>
                    'new_password_confirmation',
            ], 422);
        }

        // ============================================================
        // UPDATE PASSWORD
        // MD5 is intentionally used for compatibility with
        // the current web login system.
        // ============================================================

        DB::table('users')
            ->where(
                'usr_id',
                $user->usr_id
            )
            ->update([
                'usr_password' =>
                    md5($newPassword),
            ]);

        // ============================================================
        // SUCCESS
        // ============================================================

        return response()->json([
            'success' => true,
            'message' =>
                'Password changed successfully.',
        ], 200);
    }
}
