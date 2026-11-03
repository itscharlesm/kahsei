<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class ProfilingController extends Controller
{
    // START USERS
    public function users_active(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('users')
            ->leftJoin('user_roles', 'users.usr_id', '=', 'user_roles.usr_id')
            ->leftJoin('roles', 'user_roles.rol_id', '=', 'roles.rol_id')
            ->where('users.usr_id', '!=', '1')
            ->where('users.utyp_id', '=', '1')
            ->where('users.usr_active', '=', '1')
            ->select(
                'users.usr_id',
                'users.usr_uuid',
                'users.usr_last_name',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_email',
                'users.usr_mobile',
                'users.usr_active',
                DB::raw('GROUP_CONCAT(CASE WHEN user_roles.url_active = 1 THEN roles.rol_name END ORDER BY roles.rol_name SEPARATOR ", ") as roles')
            )
            ->groupBy(
                'users.usr_id',
                'users.usr_uuid',
                'users.usr_last_name',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_email',
                'users.usr_mobile',
                'users.usr_active'
            )
            ->orderBy('users.usr_last_name')
            ->orderBy('users.usr_first_name');

        // Search filter
        if (!empty($search)) {
            $terms = preg_split('/[\s,]+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);

            $query->where(function ($outer) use ($terms) {
                foreach ($terms as $term) {
                    $outer->where(function ($q) use ($term) {
                        $q->where('users.usr_last_name', 'LIKE', "%$term%")
                            ->orWhere('users.usr_first_name', 'LIKE', "%$term%")
                            ->orWhere('users.usr_middle_name', 'LIKE', "%$term%")
                            ->orWhere('users.usr_email', 'LIKE', "%$term%")
                            ->orWhere('users.usr_mobile', 'LIKE', "%$term%");
                    });
                }
            });
        }

        $users = $query->paginate(500);

        $roles = DB::table('roles')
            ->select('rol_id', 'rol_name', 'rol_description')
            ->where('rol_id', '!=', 1)
            ->where('rol_active', 1);

        if (session('SUPERADMIN') == '1') {
            // Super Admin can see all roles
        } elseif (session('ADMIN') == '1') {
            // Admin can see all roles except Super Admin
            $roles->where('rol_id', '!=', 1);
        } elseif (session('MANAGER') == '1') {
            // Manager can see Manager and lower roles
            $roles->whereNotIn('rol_id', [1, 2]);
        } else {
            // Other users can only see roles lower than Manager
            $roles->whereNotIn('rol_id', [1, 2, 3]);
        }

        $roles = $roles->get();

        $regions = DB::table('location_regions')
            ->where('reg_active', 1)
            ->orderBy('reg_name')
            ->get(['reg_id', 'reg_name']);

        return view('admin.profiling.users.active', compact('users', 'search', 'roles', 'regions'));
    }

    public function users_deleted(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('users')
            ->leftJoin('user_roles', 'users.usr_id', '=', 'user_roles.usr_id')
            ->leftJoin('roles', 'user_roles.rol_id', '=', 'roles.rol_id')
            ->where('users.usr_id', '!=', '1')
            ->where('users.utyp_id', '=', '1')
            ->where('users.usr_active', '=', '0')
            ->select(
                'users.usr_id',
                'users.usr_uuid',
                'users.usr_last_name',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_email',
                'users.usr_mobile',
                'users.usr_active',
                DB::raw('GROUP_CONCAT(CASE WHEN user_roles.url_active = 1 THEN roles.rol_name END ORDER BY roles.rol_name SEPARATOR ", ") as roles')
            )
            ->groupBy(
                'users.usr_id',
                'users.usr_uuid',
                'users.usr_last_name',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_email',
                'users.usr_mobile',
                'users.usr_active'
            )
            ->orderBy('users.usr_last_name')
            ->orderBy('users.usr_first_name');

        // Search filter
        if (!empty($search)) {
            $terms = preg_split('/[\s,]+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);

            $query->where(function ($outer) use ($terms) {
                foreach ($terms as $term) {
                    $outer->where(function ($q) use ($term) {
                        $q->where('users.usr_last_name', 'LIKE', "%$term%")
                            ->orWhere('users.usr_first_name', 'LIKE', "%$term%")
                            ->orWhere('users.usr_middle_name', 'LIKE', "%$term%")
                            ->orWhere('users.usr_email', 'LIKE', "%$term%")
                            ->orWhere('users.usr_mobile', 'LIKE', "%$term%");
                    });
                }
            });
        }

        $users = $query->paginate(500);

        $roles = DB::table('roles')
            ->select('rol_id', 'rol_name', 'rol_description')
            ->where('rol_active', 1)
            ->where('rol_id', '!=', 1)
            ->get();

        $regions = DB::table('location_regions')
            ->where('reg_active', 1)
            ->orderBy('reg_name')
            ->get(['reg_id', 'reg_name']);

        return view('admin.profiling.users.deleted', compact('users', 'search', 'roles', 'regions'));
    }

    public function users_add(Request $request)
    {
        $request->validate([
            'usr_first_name' => 'required|string|max:255',
            'usr_middle_name' => 'nullable|string|max:255',
            'usr_last_name' => 'required|string|max:255',
            'usr_email' => 'required|email|max:255|unique:users,usr_email',
            'usr_mobile' => 'nullable|string|max:20',

            // address validation
            'street' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
        ]);

        $code = '123456';

        DB::beginTransaction();

        // Insert user
        $usr_id = DB::table('users')->insertGetId([
            'usr_uuid' => generateuuid(),
            'utyp_id' => 1,
            'usr_first_name' => strtoupper($request->usr_first_name),
            'usr_middle_name' => $request->usr_middle_name ? strtoupper($request->usr_middle_name) : null,
            'usr_last_name' => strtoupper($request->usr_last_name),
            'usr_email' => $request->usr_email,
            'usr_mobile' => $request->usr_mobile ?: null,
            'usr_password' => md5($code),
            'usr_code' => $code,
            'usr_date_created' => Carbon::now(),
            'usr_created_by' => session('usr_id'),
            'usr_active' => 1
        ]);

        // Insert address (if any field is filled)
        if ($request->street || $request->barangay || $request->municipality || $request->province || $request->region) {
            DB::table('user_addresses')->insert([
                'uadd_uuid' => generateuuid(),
                'usr_id' => $usr_id,
                'add_id' => 1,
                'uadd_street' => normalizeAddressStreet($request->street),
                'uadd_barangay' => strtoupper($request->barangay),
                'uadd_city' => strtoupper($request->municipality),
                'uadd_province' => strtoupper($request->province),
                'uadd_region' => strtoupper($request->region),
                'uadd_active' => 1,
            ]);
        }

        DB::commit();

        return back()->with('successMessage', 'The user has been created and the password has been set to 123456.');
    }

    public function users_update_user(Request $request, $usr_id)
    {
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,rol_id',
        ]);

        $selectedRoles = $request->roles ?? [];

        // Get the user's first and last name
        $user = DB::table('users')
            ->where('usr_id', $usr_id)
            ->select('usr_first_name', 'usr_last_name')
            ->first();

        if (!$user) {
            alert()->error('Error', 'User not found.');
            return redirect()->back();
        }

        // Get all current roles of the user
        $existingRoles = DB::table('user_roles')
            ->where('usr_id', $usr_id)
            ->pluck('rol_id')
            ->toArray();

        // Fetch old active roles (before update)
        $oldRoles = DB::table('roles')
            ->whereIn('rol_id', DB::table('user_roles')
                ->where('usr_id', $usr_id)
                ->where('url_active', 1)
                ->pluck('rol_id'))
            ->pluck('rol_name')
            ->toArray();

        // Step 1: Update or deactivate existing roles
        foreach ($existingRoles as $rol_id) {
            $isActive = in_array($rol_id, $selectedRoles) ? 1 : 0;

            DB::table('user_roles')
                ->where('usr_id', $usr_id)
                ->where('rol_id', $rol_id)
                ->update(['url_active' => $isActive]);
        }

        // Step 2: Insert new roles (those in selectedRoles but not in existingRoles)
        $newRolesToInsert = array_diff($selectedRoles, $existingRoles);
        foreach ($newRolesToInsert as $rol_id) {
            DB::table('user_roles')->insert([
                'usr_id' => $usr_id,
                'rol_id' => $rol_id,
                'url_active' => 1,
            ]);
        }

        // Fetch new active roles (after update)
        $newRoles = DB::table('roles')
            ->whereIn('rol_id', DB::table('user_roles')
                ->where('usr_id', $usr_id)
                ->where('url_active', 1)
                ->pluck('rol_id'))
            ->pluck('rol_name')
            ->toArray();

        // Log user activity
        logUserActivity(
            'Manage Users',
            'Updated user ' . $user->usr_last_name . ', ' . $user->usr_first_name .
            ' - Roles FROM [' . implode(', ', $oldRoles) . '] TO [' . implode(', ', $newRoles) . ']'
        );

        alert()->success('Success', 'User updated successfully.');
        return redirect()->back();
    }

    public function users_reset_password(Request $request, $usr_id)
    {
        $user = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$user) {
            alert()->error('User not found.');
            return redirect()->back();
        }

        DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->update([
                'usr_password' => md5('123456'),
                'usr_code' => '123456',
                'usr_date_modified' => Carbon::now(),
                'usr_modified_by' => session('usr_id')
            ]);

        logUserActivity('Manage Users', 'Reset password for user ' . $user->usr_last_name . ', ' . $user->usr_first_name);

        session()->flash('successMessage', 'Password has been reset to 123456.');
        return redirect()->back();
    }

    public function users_delete(Request $request, $usr_id)
    {
        $user = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$user) {
            alert()->error('User not found.');
            return redirect()->back();
        }

        DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->update([
                'usr_date_modified' => Carbon::now(),
                'usr_modified_by' => session('usr_id'),
                'usr_active' => 0
            ]);

        logUserActivity('Manage Users', 'Deleted user ' . $user->usr_last_name . ', ' . $user->usr_first_name);

        session()->flash('successMessage', 'User has been deleted.');
        return redirect()->back();
    }

    public function users_restore(Request $request, $usr_id)
    {
        $user = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$user) {
            alert()->error('User not found.');
            return redirect()->back();
        }

        DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->update([
                'usr_date_modified' => Carbon::now(),
                'usr_modified_by' => session('usr_id'),
                'usr_active' => 1
            ]);

        logUserActivity('Manage Users', 'Restored user ' . $user->usr_last_name . ', ' . $user->usr_first_name);

        session()->flash('successMessage', 'User has been restored.');
        return redirect()->back();
    }
    // END USERS
}