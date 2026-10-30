<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * edit_profile
     * 
     * @param Object from mobile
     */
    function edit_profile(Request $request): JsonResponse
    {
        // Prepare return message
        $return_message = [];

        // Fetch user ID
        $user_id = $request->userID;
        $user = User::find($user_id);

        // If user exists
        if ($user) {
            $user->usr_first_name = $request->firstName;
            $user->usr_middle_name = $request->middleName;
            $user->usr_last_name = $request->lastName;
            $user->usr_birth_date = $request->birthDate;
            $user->usr_mobile = $request->mobileNumber;

            // If user has uploaded an image
            if ($request->hasFile('image')) {

                // If user has an existing image
                if ($user->usr_image_path) {
                    Storage::delete($user->usr_image_path);
                }
                $image_path = Storage::disk('public')->put('/images/user', $request->image);

                $user->usr_image_path = $image_path;
            }
            $user->save();

            // Return user
            $return_message =  $user->only(['usr_id', 'typ_id', 'usr_email', 'usr_first_name', 'usr_middle_name', 'usr_last_name', 'usr_mobile', 'usr_birth_date', 'usr_image_path', 'usr_theme']);
        } else {
            // Return error message
            $return_message = "Not found";
        }

        // Return response as json
        return response()->json($return_message);
    }


    /**
     * check_password
     * 
     * @param Object from mobile
     */
    function check_password(Request $request): JsonResponse
    {
        $return_message = "";
        $user = User::find($request->userID);

        if ($user) {
            if (md5($request->password) === $user->usr_password) {
                $return_message = "Matched";
            } else {
                $return_message = "Invalid";
            }
        } else {
            $return_message = "User Not Found";
        }

        return response()->json($return_message);
    }

    function change_password(Request $request): JsonResponse
    {
        $user = User::find($request->userID);
        $hashed_password = md5($request->password);

        $return_message = "";

        if ($user) {
            $user->usr_password = $hashed_password;
            $user->save();

            $return_message = "Updated";
        } else {
            $return_message = "Invalid";
        }

        return response()->json($return_message);
    }
}
