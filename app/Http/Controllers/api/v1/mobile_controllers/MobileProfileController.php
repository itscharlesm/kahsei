<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\File;

class MobileProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        $receivedEmail = trim($request->email ?? '');

        $user = User::whereRaw('LOWER(usr_email) = ?', [
            strtolower($receivedEmail)
        ])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'debug' => [
                    'received_email' => $receivedEmail,
                    'all_request_data' => $request->all(),
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'debug' => [
                'received_email' => $receivedEmail,
                'matched_user_id' => $user->usr_id,
                'matched_email' => $user->usr_email,
            ],
            'data' => [
                'usr_first_name' => $user->usr_first_name,
                'usr_middle_name' => $user->usr_middle_name,
                'usr_last_name' => $user->usr_last_name,
                'usr_mobile' => $user->usr_mobile,
                'usr_birth_date' => $user->usr_birth_date,
                'usr_email' => $user->usr_email,
                'usr_image_path' => $user->usr_image_path,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        $user = User::where('usr_email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->usr_first_name = strtoupper($request->first_name);
        $user->usr_middle_name = $request->middle_name
            ? strtoupper($request->middle_name)
            : null;
        $user->usr_last_name = strtoupper($request->last_name);
        $user->usr_birth_date = $request->birth_date;

        if ($request->remove_image == '1') {
            if ($user->usr_image_path) {
                $oldPath = public_path($user->usr_image_path);

                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $user->usr_image_path = null;
        }

        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');

            $folderPath = public_path('images/users');

            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            $fileName = 'user_' . $user->usr_id . '_' . time() . '.' . $image->getClientOriginalExtension();

            $image->move($folderPath, $fileName);

            $user->usr_image_path = 'images/users/' . $fileName;
        }

        $user->usr_date_modified = now();
        $user->usr_modified_by = $user->usr_id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'image_path' => $user->usr_image_path,
        ]);
    }
}