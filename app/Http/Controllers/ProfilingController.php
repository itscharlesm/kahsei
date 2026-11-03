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

        $sessionBranchId = session('branch_id');

        // Base query
        $query = DB::table('users')
            ->leftJoin('user_roles', 'users.usr_id', '=', 'user_roles.usr_id')
            ->leftJoin('roles', 'user_roles.rol_id', '=', 'roles.rol_id')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.branch_id')
            ->where('users.usr_id', '!=', '1')
            ->where('users.utyp_id', '=', '1')
            ->where('users.usr_active', '=', '1');

        // Branch filter (unless super admin)
        if ($sessionBranchId != 1) {
            $query->where('users.branch_id', $sessionBranchId);
        }

        $query->select(
            'users.usr_id',
            'users.usr_uuid',
            'users.branch_id',
            'branches.branch_name',
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
                'users.branch_id',
                'branches.branch_name',
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
                            ->orWhere('users.usr_mobile', 'LIKE', "%$term%")
                            ->orWhere('branches.branch_name', 'LIKE', "%$term%");
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
            // No additional role restriction

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

        // Branch dropdown: branch_id = 1 ("ALL") can see every branch;
        // everyone else only sees their own branch
        if ($sessionBranchId == 1) {
            $branches = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_active', 1)
                ->get();
        } else {
            $branches = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_active', 1)
                ->where('branch_id', $sessionBranchId)
                ->get();
        }

        return view('profiling.users.active', compact('users', 'search', 'roles', 'regions', 'branches'));
    }

    public function users_deleted(Request $request)
    {
        $search = $request->search ?? '';

        $sessionBranchId = session('branch_id');

        // Base query
        $query = DB::table('users')
            ->leftJoin('user_roles', 'users.usr_id', '=', 'user_roles.usr_id')
            ->leftJoin('roles', 'user_roles.rol_id', '=', 'roles.rol_id')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.branch_id')
            ->where('users.usr_id', '!=', '1')
            ->where('users.utyp_id', '=', '1')
            ->where('users.usr_active', '=', '0');

        // Branch filter (unless super admin)
        if ($sessionBranchId && $sessionBranchId != 1) {
            $query->where('users.branch_id', $sessionBranchId);
        }

        $query->select(
            'users.usr_id',
            'users.usr_uuid',
            'users.branch_id',
            'branches.branch_name',
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
                'users.branch_id',
                'branches.branch_name',
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
                            ->orWhere('users.usr_mobile', 'LIKE', "%$term%")
                            ->orWhere('branches.branch_name', 'LIKE', "%$term%");
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

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->where('branch_active', 1)
            ->get();

        return view('profiling.users.deleted', compact('users', 'search', 'roles', 'regions', 'branches'));
    }

    public function users_add(Request $request)
    {
        $request->validate([
            'usr_first_name' => 'required|string|max:255',
            'usr_middle_name' => 'nullable|string|max:255',
            'usr_last_name' => 'required|string|max:255',
            'usr_email' => 'required|email|max:255|unique:users,usr_email',
            'usr_mobile' => 'nullable|string|max:20',
            'branch_id' => 'required|string|max:255',

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
            'branch_id' => $request->branch_id,
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
            'branch_id' => 'required|exists:branches,branch_id',
        ]);

        $selectedRoles = $request->roles ?? [];
        $sessionBranchId = session('branch_id');

        // Get the user's first and last name, and current branch
        $user = DB::table('users')
            ->where('usr_id', $usr_id)
            ->select('usr_first_name', 'usr_last_name', 'branch_id')
            ->first();

        if (!$user) {
            alert()->error('Error', 'User not found.');
            return redirect()->back();
        }

        // Enforce branch visibility: non branch-1 sessions can only assign their own branch
        $newBranchId = $request->branch_id;
        if ($sessionBranchId != 1 && $newBranchId != $sessionBranchId) {
            alert()->error('Error', 'You are not allowed to assign that branch.');
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

        // Step 3: Update branch
        $oldBranchId = $user->branch_id;
        if ($newBranchId != $oldBranchId) {
            DB::table('users')
                ->where('usr_id', $usr_id)
                ->update(['branch_id' => $newBranchId]);
        }

        // Convert roles array to comma-separated string
        $oldRolesString = implode(', ', $oldRoles);
        $newRolesString = implode(', ', $newRoles);

        // Log user activity
        logUserActivity(
            'Manage Users',
            'Updated user ' . $user->usr_last_name . ', ' . $user->usr_first_name .
            ' - Roles FROM [' . $oldRolesString . '] TO [' . $newRolesString . ']' .
            ($newBranchId != $oldBranchId ? ' - Branch FROM [' . $oldBranchId . '] TO [' . $newBranchId . ']' : '')
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

    // START TECHINICIANS
    public function technicians_active(Request $request)
    {
        $search = $request->search ?? '';

        $sessionBranchId = session('branch_id');

        // Base query
        $query = DB::table('users')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.branch_id')
            ->leftJoin('user_availabilities', 'users.usr_id', '=', 'user_availabilities.usr_id')
            ->where('users.utyp_id', '=', '2')
            ->where('users.usr_active', '=', '1');

        // Branch filter (unless super admin)
        if ($sessionBranchId != 1) {
            $query->where('users.branch_id', $sessionBranchId);
        }

        $query->select(
            'users.usr_id',
            'users.usr_uuid',
            'users.branch_id',
            'branches.branch_name',
            'users.usr_last_name',
            'users.usr_first_name',
            'users.usr_middle_name',
            'users.usr_email',
            'users.usr_mobile',
            'users.usr_active',
            DB::raw('GROUP_CONCAT(CASE WHEN user_availabilities.uavail_active = 1 THEN user_availabilities.uavail_name END ORDER BY FIELD(user_availabilities.uavail_name, "MONDAY","TUESDAY","WEDNESDAY","THURSDAY","FRIDAY","SATURDAY","SUNDAY") SEPARATOR ", ") as availabilities')
        )
            ->groupBy(
                'users.usr_id',
                'users.usr_uuid',
                'users.branch_id',
                'branches.branch_name',
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
                            ->orWhere('users.usr_mobile', 'LIKE', "%$term%")
                            ->orWhere('branches.branch_name', 'LIKE', "%$term%");
                    });
                }
            });
        }

        $technicians = $query->paginate(500);

        $regions = DB::table('location_regions')
            ->where('reg_active', 1)
            ->orderBy('reg_name')
            ->get(['reg_id', 'reg_name']);

        // Branch dropdown: branch_id = 1 ("ALL") can see every branch;
        // everyone else only sees their own branch
        if ($sessionBranchId == 1) {
            $branches = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_active', 1)
                ->get();
        } else {
            $branches = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_active', 1)
                ->where('branch_id', $sessionBranchId)
                ->get();
        }

        return view('profiling.technicians.active', compact('technicians', 'search', 'regions', 'branches'));
    }

    public function technicians_deleted(Request $request)
    {
        $search = $request->search ?? '';

        $sessionBranchId = session('branch_id');

        // Base query
        $query = DB::table('users')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.branch_id')
            ->leftJoin('user_availabilities', 'users.usr_id', '=', 'user_availabilities.usr_id')
            ->where('users.utyp_id', '=', '2')
            ->where('users.usr_active', '=', '0');

        // Branch filter (unless super admin)
        if ($sessionBranchId && $sessionBranchId != 1) {
            $query->where('users.branch_id', $sessionBranchId);
        }

        $query->select(
            'users.usr_id',
            'users.usr_uuid',
            'users.branch_id',
            'branches.branch_name',
            'users.usr_last_name',
            'users.usr_first_name',
            'users.usr_middle_name',
            'users.usr_email',
            'users.usr_mobile',
            'users.usr_active',
            DB::raw('GROUP_CONCAT(CASE WHEN user_availabilities.uavail_active = 1 THEN user_availabilities.uavail_name END ORDER BY FIELD(user_availabilities.uavail_name, "MONDAY","TUESDAY","WEDNESDAY","THURSDAY","FRIDAY","SATURDAY","SUNDAY") SEPARATOR ", ") as availabilities')
        )
            ->groupBy(
                'users.usr_id',
                'users.usr_uuid',
                'users.branch_id',
                'branches.branch_name',
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
                            ->orWhere('users.usr_mobile', 'LIKE', "%$term%")
                            ->orWhere('branches.branch_name', 'LIKE', "%$term%");
                    });
                }
            });
        }

        $technicians = $query->paginate(500);

        $regions = DB::table('location_regions')
            ->where('reg_active', 1)
            ->orderBy('reg_name')
            ->get(['reg_id', 'reg_name']);

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->where('branch_active', 1)
            ->get();

        return view('profiling.technicians.deleted', compact('technicians', 'search', 'regions', 'branches'));
    }

    public function technicians_add(Request $request)
    {
        $request->validate([
            'usr_first_name' => 'required|string|max:255',
            'usr_middle_name' => 'nullable|string|max:255',
            'usr_last_name' => 'required|string|max:255',
            'usr_email' => 'required|email|max:255|unique:users,usr_email',
            'usr_mobile' => 'nullable|string|max:20',
            'branch_id' => 'required|string|max:255',

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
            'utyp_id' => 2,
            'usr_first_name' => strtoupper($request->usr_first_name),
            'usr_middle_name' => $request->usr_middle_name ? strtoupper($request->usr_middle_name) : null,
            'usr_last_name' => strtoupper($request->usr_last_name),
            'usr_email' => $request->usr_email,
            'usr_mobile' => $request->usr_mobile ?: null,
            'branch_id' => $request->branch_id,
            'usr_password' => md5($code),
            'usr_code' => $code,
            'usr_date_created' => Carbon::now(),
            'usr_created_by' => session('usr_id'),
            'usr_active' => 1
        ]);

        // Insert address (if any field is filled)
        if ($request->street || $request->barangay || $request->municipality || $request->province || $request->region) {
            DB::table('user_addresses')->insert([
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

        // Insert availability
        $days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

        $selected = $request->availability ?? [];

        foreach ($days as $day) {
            DB::table('user_availabilities')->insert([
                'uavail_uuid' => generateuuid(),
                'usr_id' => $usr_id,
                'uavail_name' => $day,
                'uavail_date_created' => Carbon::now(),
                'uavail_created_by' => session('usr_id'),
                'uavail_active' => in_array($day, $selected) ? 1 : 0,
            ]);
        }

        DB::commit();

        return back()->with('successMessage', 'The Technicians has been created and the password has been set to 123456.');
    }

    public function technicians_update_technician(Request $request, $usr_id)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,branch_id',
        ]);

        $sessionBranchId = session('branch_id');
        $newBranchId = $request->branch_id;

        // Technicians can never be assigned to the "ALL" branch
        if ($newBranchId == 1) {
            return back()->with('errorMessage', 'Technicians cannot be assigned to the ALL branch.');
        }

        // Enforce branch visibility: non branch-1 sessions can only assign their own branch
        if ($sessionBranchId != 1 && $newBranchId != $sessionBranchId) {
            return back()->with('errorMessage', 'You are not allowed to assign that branch.');
        }

        $technician = DB::table('users')
            ->where('usr_id', $usr_id)
            ->select('branch_id')
            ->first();

        if (!$technician) {
            return back()->with('errorMessage', 'Technician not found.');
        }

        // Update branch
        $oldBranchId = $technician->branch_id;
        if ($newBranchId != $oldBranchId) {
            DB::table('users')
                ->where('usr_id', $usr_id)
                ->update(['branch_id' => $newBranchId]);
        }

        // Update availability
        $days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

        $selected = $request->availability ?? [];

        foreach ($days as $day) {
            DB::table('user_availabilities')
                ->where('usr_id', $usr_id)
                ->where('uavail_name', $day)
                ->update([
                    'uavail_date_modified' => Carbon::now(),
                    'uavail_modified_by' => session('usr_id'),
                    'uavail_active' => in_array($day, $selected) ? 1 : 0,
                ]);
        }

        return back()->with('successMessage', 'Technician updated successfully.');
    }

    public function technicians_reset_password(Request $request, $usr_id)
    {
        $technician = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$technician) {
            alert()->error('Technician not found.');
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

        logUserActivity('Manage Technicians', 'Reset password for technician ' . $technician->usr_last_name . ', ' . $technician->usr_first_name);

        session()->flash('successMessage', 'Password has been reset to 123456.');
        return redirect()->back();
    }

    public function technicians_delete(Request $request, $usr_id)
    {
        $technician = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$technician) {
            alert()->error('Technician not found.');
            return redirect()->back();
        }

        DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->update([
                'usr_date_modified' => Carbon::now(),
                'usr_modified_by' => session('usr_id'),
                'usr_active' => 0
            ]);

        logUserActivity('Manage Technicians', 'Deleted technician ' . $technician->usr_last_name . ', ' . $technician->usr_first_name);

        session()->flash('successMessage', 'Technician has been deleted.');
        return redirect()->back();
    }

    public function technicians_restore(Request $request, $usr_id)
    {
        $technician = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$technician) {
            alert()->error('Technician not found.');
            return redirect()->back();
        }

        DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->update([
                'usr_active' => 1
            ]);

        logUserActivity('Manage Technicians', 'Restored technician ' . $technician->usr_last_name . ', ' . $technician->usr_first_name);

        session()->flash('successMessage', 'Technician has been restored.');
        return redirect()->back();
    }
    // END TECHINICIANS

    // START CLIENTS
    public function clients_active(Request $request)
    {
        $search = $request->search ?? '';

        $sessionBranchId = session('branch_id');

        // Base query
        $query = DB::table('users')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.branch_id')
            ->where('users.utyp_id', '=', '3')
            ->where('users.usr_active', '=', '1');

        // Branch dropdown: branch_id = 1 ("ALL") can see every branch;
        // everyone else only sees their own branch
        if ($sessionBranchId == 1) {
            $branches = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_active', 1)
                ->get();
        } else {
            $branches = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_active', 1)
                ->where('branch_id', $sessionBranchId)
                ->get();
        }

        $query->select(
            'users.usr_id',
            'users.usr_uuid',
            'users.branch_id',
            'branches.branch_name',
            'users.usr_last_name',
            'users.usr_first_name',
            'users.usr_middle_name',
            'users.usr_email',
            'users.usr_mobile',
            'users.usr_birth_date',
            'users.usr_active',
        )
            ->groupBy(
                'users.usr_id',
                'users.usr_uuid',
                'users.branch_id',
                'branches.branch_name',
                'users.usr_last_name',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_email',
                'users.usr_mobile',
                'users.usr_birth_date',
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
                            ->orWhere('users.usr_mobile', 'LIKE', "%$term%")
                            ->orWhere('branches.branch_name', 'LIKE', "%$term%");
                    });
                }
            });
        }

        $clients = $query->paginate(500);

        // Get all addresses for listed clients
        $clientIds = collect($clients->items())->pluck('usr_id');

        $addresses = DB::table('user_addresses')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->whereIn('user_addresses.usr_id', $clientIds)
            ->select(
                'user_addresses.*',
                'addresses.add_name'
            )
            ->get()
            ->groupBy('usr_id'); // group per client

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->where('branch_active', 1)
            ->get();

        $regions = DB::table('location_regions')
            ->where('reg_active', 1)
            ->orderBy('reg_name')
            ->get(['reg_id', 'reg_name']);

        $address_labels = DB::table('addresses')
            ->orderBy('add_name')
            ->get(['add_id', 'add_name']);

        return view('profiling.clients.active', compact('clients', 'search', 'branches', 'addresses', 'regions', 'address_labels'));
    }

    public function clients_deleted(Request $request)
    {
        $search = $request->search ?? '';

        $sessionBranchId = session('branch_id');

        // Base query
        $query = DB::table('users')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.branch_id')
            ->where('users.utyp_id', '=', '3')
            ->where('users.usr_active', '=', '0');

        // Branch filter (unless super admin)
        if ($sessionBranchId != 1) {
            $query->where('users.branch_id', $sessionBranchId);
        }

        $query->select(
            'users.usr_id',
            'users.usr_uuid',
            'branches.branch_name',
            'users.usr_last_name',
            'users.usr_first_name',
            'users.usr_middle_name',
            'users.usr_email',
            'users.usr_mobile',
            'users.usr_birth_date',
            'users.usr_active',
        )
            ->groupBy(
                'users.usr_id',
                'users.usr_uuid',
                'branches.branch_name',
                'users.usr_last_name',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_email',
                'users.usr_mobile',
                'users.usr_birth_date',
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
                            ->orWhere('users.usr_mobile', 'LIKE', "%$term%")
                            ->orWhere('branches.branch_name', 'LIKE', "%$term%");
                    });
                }
            });
        }

        $clients = $query->paginate(500);

        // Get all addresses for listed clients
        $clientIds = collect($clients->items())->pluck('usr_id');

        $addresses = DB::table('user_addresses')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->whereIn('user_addresses.usr_id', $clientIds)
            ->select(
                'user_addresses.*',
                'addresses.add_name'
            )
            ->get()
            ->groupBy('usr_id'); // group per client

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->where('branch_active', 1)
            ->get();

        return view('profiling.clients.deleted', compact('clients', 'search', 'branches', 'addresses'));
    }

    public function clients_contracts($usr_id)
    {
        // All of this client's service orders
        $clientServiceIds = DB::table('services')
            ->where('usr_id', $usr_id)
            ->where('svc_active', 1)
            ->pluck('svc_id');

        // Contracts initiated from one of the client's SAs, or linked to one of them
        $contractIds = DB::table('service_contracts')
            ->whereIn('svc_id', $clientServiceIds)
            ->pluck('svcc_id')
            ->merge(
                DB::table('services')
                    ->where('usr_id', $usr_id)
                    ->where('svc_active', 1)
                    ->whereNotNull('svcc_id')
                    ->pluck('svcc_id')
            )
            ->unique()
            ->values();

        $contracts = DB::table('service_contracts')
            ->whereIn('svcc_id', $contractIds)
            ->where('svcc_active', 1)
            ->orderByDesc('svcc_date_created')
            ->get();

        $result = [];

        foreach ($contracts as $contract) {
            // Generated job orders + the SA the contract was initiated from
            $jobOrders = DB::table('services')
                ->where(function ($q) use ($contract) {
                    $q->where('svcc_id', $contract->svcc_id)
                        ->orWhere('svc_id', $contract->svc_id);
                })
                ->where('svc_active', 1)
                ->orderBy('svc_id')
                ->select('svc_id', 'svc_sa_number', 'svc_status')
                ->get()
                ->map(function ($s) use ($contract) {
                    return [
                        'svc_id' => $s->svc_id,
                        'sa_number' => 'SA-' . str_pad($s->svc_sa_number, 6, '0', STR_PAD_LEFT),
                        'status' => $s->svc_status,
                        'is_initiated' => $s->svc_id == $contract->svc_id,
                    ];
                })
                ->values();

            $parts = explode('.', number_format((float) $contract->svcc_total_years, 2, '.', ''));
            $yrs = (int) $parts[0];
            $mos = (int) $parts[1];
            $duration = '';
            if ($yrs > 0) {
                $duration .= $yrs . ' Year' . ($yrs > 1 ? 's' : '');
            }
            if ($mos > 0) {
                $duration .= ($duration ? ' and ' : '') . $mos . ' Month' . ($mos > 1 ? 's' : '');
            }
            if (!$duration) {
                $duration = '0 Months';
            }

            $result[] = [
                'svcc_id' => $contract->svcc_id,
                'type' => $contract->svcc_type,
                'duration' => $duration,
                'total_services' => $contract->svcc_total_services,
                'file' => $contract->svcc_contract ? asset('images/contracts/' . $contract->svcc_contract) : null,
                'job_orders' => $jobOrders,
            ];
        }

        return response()->json($result);
    }

    public function clients_add(Request $request)
    {
        $sessionBranchId = session('branch_id');

        $request->validate([
            'usr_first_name' => 'required|string|max:255',
            'usr_middle_name' => 'nullable|string|max:255',
            'usr_last_name' => 'required|string|max:255',
            'usr_email' => 'required|email|max:255|unique:users,usr_email',
            'usr_mobile' => 'nullable|string|max:20',
            'branch_id' => 'required|exists:branches,branch_id',
        ]);

        // Enforce branch visibility: non branch-1 sessions can only assign their own branch
        if ($sessionBranchId != 1 && $request->branch_id != $sessionBranchId) {
            return back()->with('errorMessage', 'You are not allowed to assign that branch.');
        }

        $code = '123456';

        DB::table('users')->insertGetId([
            'usr_uuid' => generateuuid(),
            'utyp_id' => 3,
            'usr_first_name' => strtoupper($request->usr_first_name),
            'usr_middle_name' => $request->usr_middle_name ? strtoupper($request->usr_middle_name) : null,
            'usr_last_name' => strtoupper($request->usr_last_name),
            'usr_email' => $request->usr_email,
            'usr_mobile' => $request->usr_mobile ?: null,
            'branch_id' => $request->branch_id,
            'usr_password' => md5($code),
            'usr_code' => $code,
            'usr_date_created' => Carbon::now(),
            'usr_created_by' => session('usr_id'),
            'usr_active' => 1
        ]);

        return back()->with('successMessage', 'The client has been created and the password has been set to 123456.');
    }

    public function clients_import(Request $request)
    {
        $sessionBranchId = session('branch_id');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');

        if (!$handle) {
            return back()->with('errorMessage', 'Unable to read the uploaded file.');
        }

        $rowNumber = 0;
        $imported = 0;
        $addressesCreated = 0;
        $skipped = [];

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Skip header row
                if ($rowNumber === 1 && strtolower(trim($row[0] ?? '')) === 'first name') {
                    continue;
                }

                // Skip completely blank rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $firstName = trim($row[0] ?? '');
                $middleName = trim($row[1] ?? '');
                $lastName = trim($row[2] ?? '');
                $email = trim($row[3] ?? '');
                $mobile = trim($row[4] ?? '');
                $latitude = trim($row[5] ?? '');
                $longitude = trim($row[6] ?? '');

                if (!$firstName || !$lastName || !$email) {
                    $skipped[] = "Row $rowNumber: missing required field(s).";
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped[] = "Row $rowNumber: invalid email ($email).";
                    continue;
                }

                if (DB::table('users')->where('usr_email', $email)->exists()) {
                    $skipped[] = "Row $rowNumber: email already exists ($email).";
                    continue;
                }

                // Clean mobile: digits only, drop leading 0 if 11 digits, cap at 10 (same rule as the Add form)
                $mobile = preg_replace('/\D/', '', $mobile);
                if (strlen($mobile) == 11) {
                    $mobile = substr($mobile, 1);
                }
                $mobile = substr($mobile, 0, 10);

                $code = '123456';

                $userId = DB::table('users')->insertGetId([
                    'usr_uuid' => generateuuid(),
                    'utyp_id' => 3,
                    'usr_first_name' => strtoupper($firstName),
                    'usr_middle_name' => $middleName ? strtoupper($middleName) : null,
                    'usr_last_name' => strtoupper($lastName),
                    'usr_email' => $email,
                    'usr_mobile' => $mobile ?: null,
                    'branch_id' => $sessionBranchId == 1 ? 2 : $sessionBranchId,
                    'usr_password' => md5($code),
                    'usr_code' => $code,
                    'usr_date_created' => Carbon::now(),
                    'usr_created_by' => session('usr_id'),
                    'usr_active' => 1
                ], 'usr_id');

                // Create an address row only when BOTH latitude and longitude are present and valid
                if ($latitude !== '' && $longitude !== '' && is_numeric($latitude) && is_numeric($longitude)) {
                    DB::table('user_addresses')->insert([
                        'uadd_uuid' => generateuuid(),
                        'usr_id' => $userId,
                        'add_id' => 1,
                        'uadd_latitude' => $latitude,
                        'uadd_longitude' => $longitude,
                        'uadd_active' => 1,
                    ]);

                    $addressesCreated++;
                }

                $imported++;
            }

            fclose($handle);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('errorMessage', 'Import failed: ' . $e->getMessage());
        }

        $message = "$imported client(s) imported successfully ($addressesCreated with location).";
        if (!empty($skipped)) {
            $message .= ' ' . count($skipped) . ' row(s) skipped — ' . implode(' | ', array_slice($skipped, 0, 10));
        }

        return back()->with('successMessage', $message);
    }

    public function clients_update_branch(Request $request, $usr_id)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,branch_id',
        ]);

        $sessionBranchId = session('branch_id');
        $newBranchId = $request->branch_id;

        // Enforce branch visibility: non branch-1 sessions can only assign their own branch
        if ($sessionBranchId != 1 && $newBranchId != $sessionBranchId) {
            return back()->with('errorMessage', 'You are not allowed to assign that branch.');
        }

        $client = DB::table('users')
            ->where('usr_id', $usr_id)
            ->select('usr_first_name', 'usr_last_name', 'branch_id')
            ->first();

        if (!$client) {
            return back()->with('errorMessage', 'Client not found.');
        }

        $oldBranchId = $client->branch_id;

        if ($newBranchId != $oldBranchId) {
            DB::table('users')
                ->where('usr_id', $usr_id)
                ->update([
                    'branch_id' => $newBranchId,
                    'usr_date_modified' => Carbon::now(),
                    'usr_modified_by' => session('usr_id'),
                ]);

            logUserActivity(
                'Manage Clients',
                'Updated branch for client ' . $client->usr_last_name . ', ' . $client->usr_first_name .
                ' FROM [' . $oldBranchId . '] TO [' . $newBranchId . ']'
            );
        }

        return back()->with('successMessage', 'Client branch updated successfully.');
    }

    public function clients_address_add(Request $request, $usr_id)
    {
        $add_id = $request->add_id;

        // "+ Create new label" was picked: reuse the label if it exists, otherwise create it
        if ($add_id === '__new__') {
            $name = strtoupper(trim((string) $request->new_add_name));

            if ($name === '') {
                session()->flash('errorMessage', 'Please enter a label name.');
                return redirect()->back();
            }

            $existing = DB::table('addresses')
                ->where('add_name', $name)
                ->first();

            if ($existing) {
                $add_id = $existing->add_id;
            } else {
                $add_id = DB::table('addresses')->insertGetId([
                    'add_uuid' => generateuuid(),
                    'add_name' => $name,
                    'add_date_created' => Carbon::now(),
                    'add_created_by' => session('usr_id'),
                    'add_active' => 1,
                ]);

                logUserActivity('Manage Addresses', 'Added new address ' . $name);
            }
        }

        if (empty($add_id)) {
            session()->flash('errorMessage', 'Please select or enter an address label.');
            return redirect()->back();
        }

        DB::table('user_addresses')->insert([
            'usr_id' => $usr_id,
            'add_id' => $add_id,
            'uadd_street' => $request->street,
            'uadd_region' => $request->region,
            'uadd_province' => $request->province,
            'uadd_city' => $request->municipality,
            'uadd_barangay' => $request->barangay,
            'uadd_latitude' => $request->latitude,
            'uadd_longitude' => $request->longitude,
            'uadd_active' => 1,
        ]);

        session()->flash('successMessage', 'Address has been added.');
        return redirect()->back();
    }

    public function clients_address_edit(Request $request, $usr_id)
    {
        $add_id = $request->add_id;

        // "+ Create new label" was picked: reuse the label if it exists, otherwise create it
        if ($add_id === '__new__') {
            $name = strtoupper(trim((string) $request->new_add_name));

            if ($name === '') {
                session()->flash('errorMessage', 'Please enter a label name.');
                return redirect()->back();
            }

            $existing = DB::table('addresses')
                ->where('add_name', $name)
                ->first();

            if ($existing) {
                $add_id = $existing->add_id;
            } else {
                $add_id = DB::table('addresses')->insertGetId([
                    'add_uuid' => generateuuid(),
                    'add_name' => $name,
                    'add_date_created' => Carbon::now(),
                    'add_created_by' => session('usr_id'),
                    'add_active' => 1,
                ]);

                logUserActivity('Manage Addresses', 'Added new address ' . $name);
            }
        }

        $data = [
            'uadd_street' => $request->street,
            'uadd_latitude' => $request->latitude,
            'uadd_longitude' => $request->longitude,
            'uadd_active' => $request->has('uadd_active') ? 1 : 0,
        ];

        // empty label = KEEP CURRENT
        if (!empty($add_id)) {
            $data['add_id'] = $add_id;
        }

        // empty location hidden inputs = KEEP CURRENT
        if (!empty($request->region)) {
            $data['uadd_region'] = $request->region;
            $data['uadd_province'] = $request->province;
            $data['uadd_city'] = $request->municipality;
            $data['uadd_barangay'] = $request->barangay;
        }

        DB::table('user_addresses')
            ->where('uadd_id', $request->uadd_id)
            ->where('usr_id', $usr_id)
            ->update($data);

        session()->flash('successMessage', 'Address has been updated.');
        return redirect()->back();
    }

    public function clients_reset_password(Request $request, $usr_id)
    {
        $client = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$client) {
            alert()->error('Client not found.');
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

        logUserActivity('Manage Clients', 'Reset password for client ' . $client->usr_last_name . ', ' . $client->usr_first_name);

        session()->flash('successMessage', 'Password has been reset to 123456.');
        return redirect()->back();
    }

    public function clients_delete(Request $request, $usr_id)
    {
        $client = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$client) {
            alert()->error('Client not found.');
            return redirect()->back();
        }

        DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->update([
                'usr_date_modified' => Carbon::now(),
                'usr_modified_by' => session('usr_id'),
                'usr_active' => 0
            ]);

        logUserActivity('Manage Clients', 'Deleted client ' . $client->usr_last_name . ', ' . $client->usr_first_name);

        session()->flash('successMessage', 'Client has been Deleted.');
        return redirect()->back();
    }

    public function clients_restore(Request $request, $usr_id)
    {
        $client = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if (!$client) {
            alert()->error('Client not found.');
            return redirect()->back();
        }

        DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->update([
                'usr_date_modified' => Carbon::now(),
                'usr_modified_by' => session('usr_id'),
                'usr_active' => 1
            ]);

        logUserActivity('Manage Clients', 'Restored client ' . $client->usr_last_name . ', ' . $client->usr_first_name);

        session()->flash('successMessage', 'Client has been restored.');
        return redirect()->back();
    }
    // END CLIENTS
}