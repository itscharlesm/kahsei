<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class UserController extends Controller
{
    public function account(Request $request)
    {
        $user = DB::table('users')
            ->where('usr_id', session('usr_id'))
            ->first();

        $regions = DB::table('location_regions')
            ->where('reg_active', 1)
            ->orderBy('reg_name')
            ->get(['reg_id', 'reg_name']);

        $addresses = DB::table('user_addresses as ua')
            ->leftJoin('addresses as a', 'ua.add_id', '=', 'a.add_id')
            ->where('ua.usr_id', session('usr_id'))
            ->select('ua.*', 'a.add_name')
            ->get();

        $address_labels = DB::table('addresses')
            ->orderBy('add_name')
            ->get(['add_id', 'add_name']);

        return view('account', compact('user', 'regions', 'addresses', 'address_labels'));
    }

    public function account_update(Request $request)
    {
        $updateData = [
            'usr_first_name' => strtoupper($request->usr_first_name),
            'usr_middle_name' => strtoupper($request->usr_middle_name),
            'usr_last_name' => strtoupper($request->usr_last_name),
            'usr_birth_date' => $request->usr_birth_date,
        ];

        // AVATAR UPLOAD
        if ($request->hasFile('avatar')) {

            $request->validate([
                'avatar' => 'mimes:jpeg,jpg,png,webp|max:8192'
            ]);

            $file = $request->file('avatar');
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $path = public_path('images/users/' . $fileName);
            $ext = strtolower($file->getClientOriginalExtension());

            if (in_array($ext, ['jpg', 'jpeg'])) {
                $source = imagecreatefromjpeg($file->getPathname());
                imagejpeg($source, $path, 75);
                imagedestroy($source);

            } elseif ($ext === 'png') {
                $source = imagecreatefrompng($file->getPathname());

                if ($source && imageistruecolor($source) === false) {
                    imagepalettetotruecolor($source);
                }

                if ($source) {
                    imagepng($source, $path, 7);
                    imagedestroy($source);
                }

            } elseif ($ext === 'webp') {
                $source = imagecreatefromwebp($file->getPathname());
                imagewebp($source, $path, 75);
                imagedestroy($source);
            }

            $updateData['usr_image_path'] = $fileName;
        }

        DB::table('users')
            ->where('usr_id', session('usr_id'))
            ->update($updateData);

        return back()->with('successMessage', 'Account updated successfully.');
    }

    public function account_address_add(Request $request)
    {
        $request->validate([
            'add_id' => 'nullable|integer|exists:addresses,add_id',
            'street' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
        ]);

        DB::table('user_addresses')->insert([
            'uadd_uuid' => generateuuid(),
            'usr_id' => session('usr_id'),
            'add_id' => $request->add_id ?: null,
            'uadd_street' => normalizeAddressStreet($request->street),
            'uadd_barangay' => strtoupper($request->barangay),
            'uadd_city' => strtoupper($request->municipality),
            'uadd_province' => strtoupper($request->province),
            'uadd_region' => strtoupper($request->region),
            'uadd_latitude' => $request->latitude,
            'uadd_longitude' => $request->longitude,
            'uadd_active' => 1,
        ]);

        return back()->with('successMessage', 'Address added successfully.');
    }

    public function account_address_edit(Request $request)
    {
        $request->validate([
            'uadd_id' => 'required|integer',
            'street' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
        ]);

        $updateData = [
            'uadd_street' => normalizeAddressStreet($request->street),
            'uadd_active' => $request->has('uadd_active') ? 1 : 0,
        ];

        if ($request->region)
            $updateData['uadd_region'] = strtoupper($request->region);
        if ($request->province)
            $updateData['uadd_province'] = strtoupper($request->province);
        if ($request->municipality)
            $updateData['uadd_city'] = strtoupper($request->municipality);
        if ($request->barangay)
            $updateData['uadd_barangay'] = strtoupper($request->barangay);

        // Only overwrite coordinates if the user actually repinned
        if ($request->filled('latitude'))
            $updateData['uadd_latitude'] = $request->latitude;
        if ($request->filled('longitude'))
            $updateData['uadd_longitude'] = $request->longitude;

        DB::table('user_addresses')
            ->where('uadd_id', $request->uadd_id)
            ->where('usr_id', session('usr_id'))
            ->update($updateData);

        return back()->with('successMessage', 'Address updated successfully.');
    }

    public function account_update_password(Request $request)
    {
        $current_password = $request->current_password;
        $new_password1 = $request->new_password1;
        $new_password2 = $request->new_password2;

        $user = DB::table('users')
            ->where('usr_id', '=', session('usr_id'))
            ->first();

        if (md5($current_password) == $user->usr_password) {
            if ($new_password1 == $new_password2) {

                DB::table('users')
                    ->where('usr_id', '=', session('usr_id'))
                    ->update([
                        'usr_password' => md5($new_password1)
                    ]);

                alert()->success('Success', 'User password has been changed.');
            } else {
                alert()->warning('Warning', 'Password did not matched.');
            }
        } else {
            alert()->warning('Warning', 'Incorrect user password.');
        }
        return redirect()->action([AdminController::class, 'home']);
    }
}