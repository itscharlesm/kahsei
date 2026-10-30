<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmationCodeMail;
use App\Mail\DemoMail;
use App\Mail\ResetPasswordCodeMail;
use App\Mail\VerifyEmailMail;
use App\Models\ConfirmationCode;
use App\Models\ResetPasswordCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
         /**
     * login
     * 
     * @param string email
     * @param string password
     * @param string device_name
     * 
     */
    function login(Request $request): JsonResponse
    {
        $email = $request->email;
        $password = $request->password;
        $device_name = $request->deviceName;
        $hashedPassword = md5($password);

        $return_message = [];
        $user = User::whereUsrEmail($email)->whereUsrActive(1)->first();

        if ($user) {
            if ($user->usr_invalid_login_count > 4) {
                $return_message = ['status' => 'Blocked'];
            } else {
                if ($user->usr_password === $hashedPassword) {

                    $user->usr_invalid_login_count = 0;
                    $user->save();

                    // Generate token
                    $token = $user->createToken($device_name)->plainTextToken;
                    
                    $return_message = [
                        'user' => $user->only([
                            'usr_id', 'typ_id', 'usr_email',
                            'usr_first_name', 'usr_middle_name', 'usr_last_name',
                            'usr_mobile', 'usr_birth_date', 'usr_image_path', 'usr_theme'
                        ]),
                        'token' => $token
                    ];
                } else {
                    $user->increment('usr_invalid_login_count');
                    $user->save();

                    // dd($invalid_login_count);
                    $return_message = [
                        'status' => 'Invalid',
                        'errorCount' => $user->usr_invalid_login_count
                    ];
                }
            }
        } else {
            $return_message = ['status' => 'Not Found'];
        }

        return response()->json($return_message);
    }

    /**
     * get_user_data
     * 
     * @param string token
     * 
     */
    function get_user_data(Request $request): JsonResponse
    {
        // $tableToken = PersonalAccessToken::findToken($token);
        // $user = $tableToken->tokenable()->first();
        // dd($request->bearerToken());

        // Get authenticated user by token
        $user = auth('sanctum')->user();
        $return_message = [];

        if ($user) {
            if ($user->usr_invalid_login_count > 4) {
                $return_message = ['status' => 'Blocked'];
            } else {
                $return_message = [
                    'user' => $user->only(['usr_id', 'typ_id', 'usr_email', 'usr_first_name', 'usr_middle_name', 'usr_last_name', 'usr_mobile', 'usr_birth_date', 'usr_image_path', 'usr_theme']),
                ];
            }
        } else {
            $return_message = ['status' => 'Not Found'];
        }

        // dd($user);
        return response()->json($return_message);
    }

    /**
     * is_email_available
     * 
     * @param string email
     *  
     */
    function is_email_available(Request $request, $email): JsonResponse
    {
        // Get email from request
        // $email = $request->email;

        // Initialize return message
        $return_message = [];

        // Check if a user with an email of $email and active = 1 exists
        $user = User::whereUsrEmail($email)->whereUsrActive(1)->first();
        // $user = DB::table('users')->where('usr_email', $email)->first();

        if ($request->password !== $request->confirmPassword) {
            return response()->json(['message' => 'Passwords does not match.']);
        }
        // dd($user);

        // Prepare availability status
        if ($user) {
            // Error 409 email is in use already
            // $return_message = ['status' => 410];
            $return_message = ['status' => 'Unavailable'];
        } else {
            // Prepare confirmation code if user is available
            $confirmation_code = generateDigitCode();
            // $confirmation_code = 666666;

            // Temporary value for confirmation while mail is not yet setup
            $emailData = [
                'confirmation_code' => $confirmation_code,
                'title' => 'test@test.com'
            ];

            // Insert to database if email is available
            ConfirmationCode::create([
                'cc_code' => $confirmation_code,
                'cc_email' => $email,
                'cc_expired_at' => Carbon::now()->addMinutes(5)
            ]);

            // Temporary mail to mailtrap using test email
            // Mail::to('james.apoloz27@gmail.com')->send(new VerifyEmailMail($emailData));
            Mail::to($email)->send(new VerifyEmailMail($emailData));

            // TEST
            // $verification_url = env('APP_URL') . '/user/verify/' . $confirmation_code;
            // $emailSubject = 'GRIND user verification';
            // $emailContent = 'To complete your GRIND registration, please go to the following link: ' . $verification_url . '.';
            // $emailTo = 'james.apoloz27@gmail.com';

            // sendEmail($emailSubject,$emailContent,$emailTo);

            // Inform user that email is available
            // $return_message = ['status' => 200];
            $return_message = ['status' => 'Available'];
        }

        // Return response as json
        return response()->json($return_message);
    }

    /**
     * is_confirmation_code_valid
     * 
     * @param string confirmation_code
     */
    function is_confirmation_code_valid(Request $request): JsonResponse
    {
        $email = $request->email;
        $code = $request->code;

        // Initialize return message
        $return_message = [];

        // Get the latest row with email = $email and active = 1
        $latest_code = ConfirmationCode::whereCcEmail($email)->whereCcActive(1)->latest()->first();

        // Get the current timestamp
        $current_time = Carbon::now();

        if ($latest_code) {
            // Compare if code === $code
            if ($code === $latest_code->cc_code) {
                // If code is same then compare current time to the latest_code expiration time
                if ($current_time->gt($latest_code->cc_expired_at)) {
                    // $return_message = ['status' => 410];

                    // Return expired if expired
                    $return_message = ['status' => 'Expired'];
                } else {
                    // If code matches and is not yet expired, set active to 0
                    $latest_code->cc_active = 0;
                    $latest_code->save();

                    // Return available if not expired 
                    $return_message = ['status' => 'Confirmed'];
                }
            } else {
                // Return invalid if it does not match
                $return_message = ['status' => 'Invalid'];
            }
        } else {
            // Return none if it does not exist
            $return_message = ['status' => 'None'];
        }

        // Return response as json
        return response()->json($return_message);
    }

    /**
     * register
     * 
     * @param Object from mobile
     */
    function register(Request $request): JsonResponse
    {
        // Get all requests
        $email = $request->email;
        $password = $request->password;
        $device_name = $request->deviceName;
        $first_name = $request->firstName;
        $middle_name = $request->middleName;
        $last_name = $request->lastName;
        $mobile_number = $request->mobileNumber;
        $birth_date = $request->birthDate;
        // $sector = $request->sector;

        // Create path for image
        $request->image ?
            $image_path = Storage::disk('public')->put('/images/user', $request->image)
            : $image_path = null;

        // Insert user data to database
        $user = User::create([
            'usr_uuid' => generateuuid(),
            'typ_id' => 1,
            'usr_email' => $email,
            // 'usr_password' => Hash::make($password),
            'usr_password' => md5($password),
            'usr_last_name' => $last_name,
            'usr_first_name' => $first_name,
            'usr_middle_name' => $middle_name,
            'usr_mobile' => $mobile_number,
            'usr_birth_date' => $birth_date,
            'usr_image_path' => $image_path,
            'usr_is_verified' => 1,
            'usr_date_verified' => Carbon::now(),
        ]);
        // Generate token
        $token = $user->createToken($device_name)->plainTextToken;

        // Set return message
        $return_message = [
            'user' => $user->only([
                'usr_id', 'typ_id', 'usr_email',
                'usr_first_name', 'usr_middle_name', 'usr_last_name',
                'usr_mobile', 'usr_birth_date', 'usr_image_path', 'usr_theme'
            ]),
            'token' => $token
        ];

        // Return response as json
        return response()->json($return_message);
    }

    /**
     * send_password_reset_code
     * 
     * @param Object from mobile
     */
    function send_password_reset_code($email): JsonResponse
    {
        // Get email
        // $email = $request->email;

        // Check if a user with an email of $email and active = 1 exists
        $user = User::whereUsrEmail($email)->whereUsrActive(1)->first();

        // If user exists
        if ($user) {
            // Initialize reset code
            $reset_code = generateDigitCode();
            // $reset_code = 666666;

            // Temporary value for confirmation while mail is not yet setup
            $emailData = [
                'reset_code' => $reset_code,
                'title' => 'test@test.com'
            ];

            // Insert reset code to database
            ResetPasswordCode::create([
                'rp_code' => $reset_code,
                'rp_email' => $email,
                'rp_expired_at' => Carbon::now()->addMinutes(5)
            ]);

            // Temporary mail to mailtrap using test email
            // Mail::to('james.apoloz27@gmail.com')->send(new ResetPasswordCodeMail($emailData));
            Mail::to($email)->send(new ResetPasswordCodeMail($emailData));

            // Inform user that reset code has been sent
            $return_message = ['status' => 'Sent'];
        } else {

            // Inform user that this email does not exist
            $return_message = ['status' => 'Not Found'];
        }

        // Return response as json
        return response()->json($return_message);
    }

    /**
     * is_reset_password_code_valid
     * 
     * @param String reset_password_code
     */
    function is_password_reset_code_valid(Request $request): JsonResponse
    {
        // Get email and reset code
        $email = $request->email;
        $code = $request->code;

        // Initialize return message
        $return_message = [];

        // Get the latest reset code with email = $email and active = 1
        $latest_code = ResetPasswordCode::whereRpEmail($email)->whereRpActive(1)->latest()->first();

        // Get current timestamp
        $current_time = Carbon::now();

        if ($latest_code) {
            // Compare if code is equal to $latest_code
            if ($code === $latest_code->rp_code) {
                // check if code is not expired
                if ($current_time->gt($latest_code->rp_expired_at)) {
                    // Return expired message
                    $return_message = ['status' => 'Expired'];
                } else {
                    // Return success message
                    $return_message = ['status' => 'Matched'];
                }
            } else {
                // If code does not match
                $return_message = ['status' => 'Invalid'];
            }
        } else {
            $return_message = ['status' => 'None'];
        }

        // Return response as json
        return response()->json($return_message);
    }

    /**
     * create_new_password
     * 
     * @param String password
     */
    function create_new_password(Request $request): JsonResponse
    {
        // Get new password
        $email = $request->email;
        $password = $request->password;

        // get the user with an email of $email and active = 1 exists
        $user = User::whereUsrEmail($email)->whereUsrActive(1)->first();

        if ($user) {
            // If user exists, change password
            // $user->usr_password = Hash::make($password);
            $user->usr_password = md5($password);
            $user->usr_invalid_login_count = 0;
            $user->save();
            $return_message = ['status' => 'Success'];
        } else {
            // Ambot???
            $return_message = ['status' => 'Error'];
        }

        // Return response as json
        return response()->json($return_message);
    }

    /**
     * logout
     * 
     * @param Object user
     */
    function logout(Request $request): JsonResponse
    {
        // Delete current device's user token from database
        $request->user()->currentAccessToken()->delete();

        // Return response as json
        return response()->json(['success' => 'Logged Out']);
    }

    /**
     * delete_account
     * 
     * needed by google play store
     * 
     * @param Object user
     */
    function delete_account(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        $user = auth('sanctum')->user();

        if ($user) {
            $user->usr_active = 1;
            $user->save();
        }
        // $user = User::find($request->userID);
        // $user->delete();

        return response()->json(['status' => 'Deleted']);
    }

    ////////////////////
    //                //
    //  Test Routes   //
    //                //
    ////////////////////
    function sendMail(Request $request)
    {
        // $code = generateDigitCode();

        // // return view('emails.confirmation_code_mail', ['code' => $code]);
        // Mail::to('james.apoloz27@gmail.com')->send(new ConfirmationCodeMail($code));
        // dd($latest_valid_code);
    }
}
