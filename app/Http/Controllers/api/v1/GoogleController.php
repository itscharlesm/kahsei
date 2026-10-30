<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GoogleController extends Controller
{
    /**
     * check_account_match
     * 
     * after signing in to google, check if google email address
     * has a match on the database and if it has a match, it will
     * login that account instead else, it will create a new user
     * 
     * @param Object from mobile
     */
    function check_account_match(Request $request): JsonResponse
    {
        // Check if user email exists and account is still active
        $user = User::whereUsrEmail($request->email)->whereUsrActive(1)->first();

        if (!$user) {
            // If email does not exist, create a new User instance
            $uuid = generateuuid();
            $google_profile_photo = $request->photo;

            if ($google_profile_photo) {
                $file_name = 'images/user/' . Str::random(40) . '.jpg';
                Storage::disk('public')->put($file_name, file_get_contents($google_profile_photo));
            }

            $user = User::create([
                'usr_uuid' => $uuid,
                'typ_id' => 1,
                'usr_email' => $request->email,
                // Temporary value sa password kay UUID
                'usr_password' => md5($uuid),
                'usr_last_name' => $request->familyName,
                'usr_first_name' => $request->givenName,
                'usr_image_path' => $file_name ?? null,
                'usr_is_verified' => 1,
                'usr_date_verified' => Carbon::now()
            ]);
        }
        $user->usr_invalid_login_count = 0;
        $user->save();
        // Get necessary user fields, generate token and attach to return_message
        $token = $user->createToken($request->deviceName)->plainTextToken;
        $return_message = [
            'user' => $user->only([
                'usr_id', 'typ_id', 'usr_email', 'usr_first_name',
                'usr_middle_name', 'usr_last_name', 'usr_mobile',
                'usr_birth_date', 'usr_image_path', 'usr_theme'
            ]),
            'token' => $token
        ];
        resetUserLoginCounter($user->usr_email);

        // Return response as JSON
        return response()->json($return_message);
    }
}
