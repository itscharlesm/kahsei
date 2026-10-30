<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class ManagementController extends Controller
{
    // START ADDRESSES
    public function addresses_active(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('addresses')
            ->leftJoin('users as creator', 'addresses.add_created_by', '=', 'creator.usr_id')
            ->leftJoin('users as modifier', 'addresses.add_modified_by', '=', 'modifier.usr_id')
            ->where('addresses.add_active', '=', '1');

        $query->select(
            'addresses.add_id',
            'addresses.add_name',
            'addresses.add_date_created',
            'addresses.add_date_modified',
            'addresses.add_active',

            // Created by
            'creator.usr_first_name as created_first_name',
            'creator.usr_last_name as created_last_name',

            // Modified by
            'modifier.usr_first_name as modified_first_name',
            'modifier.usr_last_name as modified_last_name'
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('addresses.add_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_last_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('addresses.add_name', 'asc');

        $addresses = $query->paginate(500);

        return view('management.addresses.active', compact('addresses', 'search'));
    }

    public function addresses_deleted(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('addresses')
            ->leftJoin('users as creator', 'addresses.add_created_by', '=', 'creator.usr_id')
            ->leftJoin('users as modifier', 'addresses.add_modified_by', '=', 'modifier.usr_id')
            ->where('addresses.add_active', '=', '0');

        $query->select(
            'addresses.add_id',
            'addresses.add_name',
            'addresses.add_date_created',
            'addresses.add_date_modified',
            'addresses.add_active',

            // Created by
            'creator.usr_first_name as created_first_name',
            'creator.usr_last_name as created_last_name',

            // Modified by
            'modifier.usr_first_name as modified_first_name',
            'modifier.usr_last_name as modified_last_name'
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('addresses.add_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_last_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('addresses.add_name', 'asc');

        $addresses = $query->paginate(500);

        return view('management.addresses.deleted', compact('addresses', 'search'));
    }

    public function addresses_add(Request $request)
    {
        $request->validate([
            'add_name' => 'required|string|max:255|unique:addresses,add_name',
        ]);

        DB::table('addresses')->insert([
            'add_uuid' => generateuuid(),
            'add_name' => strtoupper($request->add_name),
            'add_date_created' => Carbon::now(),
            'add_created_by' => session('usr_id'),
            'add_active' => 1,
        ]);

        logUserActivity('Manage Addresses', 'Added new address ' . $request->add_name);

        session()->flash('successMessage', 'Address has been added.');
        return redirect()->back();
    }

    public function addresses_update(Request $request, $add_id)
    {
        // Validate request
        $request->validate([
            'add_name' => 'required|string|max:255',
        ]);

        DB::table('addresses')
            ->where('add_id', $add_id)
            ->update([
                'add_name' => strtoupper($request->add_name),
                'add_date_modified' => Carbon::now(),
                'add_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Addresses', 'Updated address ' . $request->add_name);

        session()->flash('successMessage', 'Address has been updated.');
        return redirect()->back();
    }

    public function addresses_delete(Request $request, $add_id)
    {
        $address = DB::table('addresses')
            ->where('add_id', '=', $add_id)
            ->first();

        if (!$address) {
            alert()->error('Client not found.');
            return redirect()->back();
        }

        DB::table('addresses')
            ->where('add_id', '=', $add_id)
            ->update([
                'add_date_modified' => Carbon::now(),
                'add_modified_by' => session('usr_id'),
                'add_active' => 0,
            ]);

        logUserActivity('Manage Addresses', 'Deleted address ' . $address->add_name);

        session()->flash('successMessage', 'Address has been Deleted.');
        return redirect()->back();
    }

    public function addresses_restore(Request $request, $add_id)
    {
        $address = DB::table('addresses')
            ->where('add_id', '=', $add_id)
            ->first();

        if (!$address) {
            alert()->error('Client not found.');
            return redirect()->back();
        }

        DB::table('addresses')
            ->where('add_id', '=', $add_id)
            ->update([
                'add_date_modified' => Carbon::now(),
                'add_modified_by' => session('usr_id'),
                'add_active' => 1,
            ]);

        logUserActivity('Manage Addresses', 'Restored address ' . $address->add_name);

        session()->flash('successMessage', 'Address has been Restored.');
        return redirect()->back();
    }
    // END ADDRESSES

    // START CHEMICALS
    public function chemicals_active(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('chemicals')
            ->leftJoin('users as creator', 'chemicals.chem_created_by', '=', 'creator.usr_id')
            ->leftJoin('users as modifier', 'chemicals.chem_modified_by', '=', 'modifier.usr_id')
            ->where('chemicals.chem_active', '=', '1');

        $query->select(
            'chemicals.chem_id',
            'chemicals.chem_name',
            'chemicals.chem_date_created',
            'chemicals.chem_date_modified',
            'chemicals.chem_active',

            // Created by
            'creator.usr_first_name as created_first_name',
            'creator.usr_last_name as created_last_name',

            // Modified by
            'modifier.usr_first_name as modified_first_name',
            'modifier.usr_last_name as modified_last_name'
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('chemicals.chem_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_last_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('chemicals.chem_name', 'asc');

        $chemicals = $query->paginate(500);

        return view('management.chemicals.active', compact('chemicals', 'search'));
    }

    public function chemicals_deleted(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('chemicals')
            ->leftJoin('users as creator', 'chemicals.chem_created_by', '=', 'creator.usr_id')
            ->leftJoin('users as modifier', 'chemicals.chem_modified_by', '=', 'modifier.usr_id')
            ->where('chemicals.chem_active', '=', '0');

        $query->select(
            'chemicals.chem_id',
            'chemicals.chem_name',
            'chemicals.chem_date_created',
            'chemicals.chem_date_modified',
            'chemicals.chem_active',

            // Created by
            'creator.usr_first_name as created_first_name',
            'creator.usr_last_name as created_last_name',

            // Modified by
            'modifier.usr_first_name as modified_first_name',
            'modifier.usr_last_name as modified_last_name'
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('chemicals.chem_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_last_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('chemicals.chem_name', 'asc');

        $chemicals = $query->paginate(500);

        return view('management.chemicals.deleted', compact('chemicals', 'search'));
    }

    public function chemicals_add(Request $request)
    {
        $request->validate([
            'chem_name' => 'required|string|max:255|unique:chemicals,chem_name',
        ]);

        DB::table('chemicals')->insert([
            'chem_uuid' => generateuuid(),
            'chem_name' => strtoupper($request->chem_name),
            'chem_date_created' => Carbon::now(),
            'chem_created_by' => session('usr_id'),
            'chem_active' => 1,
        ]);

        logUserActivity('Manage Chemicals', 'Added new chemical ' . $request->chem_name);

        session()->flash('successMessage', 'Chemical has been added.');
        return redirect()->back();
    }

    public function chemicals_update(Request $request, $chem_id)
    {
        // Validate request
        $request->validate([
            'chem_name' => 'required|string|max:255',
        ]);

        DB::table('chemicals')
            ->where('chem_id', $chem_id)
            ->update([
                'chem_name' => strtoupper($request->chem_name),
                'chem_date_modified' => Carbon::now(),
                'chem_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Chemicals', 'Updated chemical ' . $request->chem_name);

        session()->flash('successMessage', 'Chemical has been updated.');
        return redirect()->back();
    }

    public function chemicals_delete(Request $request, $chem_id)
    {
        $chemical = DB::table('chemicals')
            ->where('chem_id', '=', $chem_id)
            ->first();

        if (!$chemical) {
            alert()->error('Client not found.');
            return redirect()->back();
        }

        DB::table('chemicals')
            ->where('chem_id', '=', $chem_id)
            ->update([
                'chem_date_modified' => Carbon::now(),
                'chem_modified_by' => session('usr_id'),
                'chem_active' => 0,
            ]);

        logUserActivity('Manage Chemicals', 'Deleted chemical ' . $chemical->chem_name);

        session()->flash('successMessage', 'Chemical has been Deleted.');
        return redirect()->back();
    }

    public function chemicals_restore(Request $request, $chem_id)
    {
        $chemical = DB::table('chemicals')
            ->where('chem_id', '=', $chem_id)
            ->first();

        if (!$chemical) {
            alert()->error('Client not found.');
            return redirect()->back();
        }

        DB::table('chemicals')
            ->where('chem_id', '=', $chem_id)
            ->update([
                'chem_date_modified' => Carbon::now(),
                'chem_modified_by' => session('usr_id'),
                'chem_active' => 1,
            ]);

        logUserActivity('Manage Chemicals', 'Restored chemical ' . $chemical->chem_name);

        session()->flash('successMessage', 'Chemical has been Restored.');
        return redirect()->back();
    }
    // END CHEMICALS

    // START BRANCHES
    public function branches_active(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('branches')
            ->leftJoin('users as creator', 'branches.branch_created_by', '=', 'creator.usr_id')
            ->leftJoin('users as modifier', 'branches.branch_modified_by', '=', 'modifier.usr_id')
            ->where('branches.branch_id', '!=', 1)
            ->where('branches.branch_active', '=', '1');

        $query->select(
            'branches.branch_id',
            'branches.branch_name',
            'branches.branch_latitude',
            'branches.branch_longitude',
            'branches.branch_date_created',
            'branches.branch_date_modified',
            'branches.branch_active',

            // Created by
            'creator.usr_first_name as created_first_name',
            'creator.usr_last_name as created_last_name',

            // Modified by
            'modifier.usr_first_name as modified_first_name',
            'modifier.usr_last_name as modified_last_name'
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('branches.branch_name', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_latitude', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_longitude', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_last_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('branches.branch_name', 'asc');

        $branches = $query->paginate(500);

        return view('management.branches.active', compact('branches', 'search'));
    }

    public function branches_deleted(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('branches')
            ->leftJoin('users as creator', 'branches.branch_created_by', '=', 'creator.usr_id')
            ->leftJoin('users as modifier', 'branches.branch_modified_by', '=', 'modifier.usr_id')
            ->where('branches.branch_id', '!=', 1)
            ->where('branches.branch_active', '=', '0');

        $query->select(
            'branches.branch_id',
            'branches.branch_name',
            'branches.branch_date_created',
            'branches.branch_date_modified',
            'branches.branch_active',

            // Created by
            'creator.usr_first_name as created_first_name',
            'creator.usr_last_name as created_last_name',

            // Modified by
            'modifier.usr_first_name as modified_first_name',
            'modifier.usr_last_name as modified_last_name'
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('branches.branch_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_last_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('branches.branch_name', 'asc');

        $branches = $query->paginate(500);

        return view('management.branches.deleted', compact('branches', 'search'));
    }

    public function branches_add(Request $request)
    {
        $request->validate([
            'branch_name' => 'required|string|max:255',
            'branch_longitude' => 'required|string|max:255',
            'branch_latitude' => 'required|string|max:255',
        ]);

        // Check if branch already exists (active or inactive)
        $existingBranch = DB::table('branches')
            ->where('branch_name', $request->branch_name)
            ->first();

        // If existing branch found
        if ($existingBranch) {

            // If inactive, reactivate it
            if ($existingBranch->branch_active == 0) {

                DB::table('branches')
                    ->where('branch_id', $existingBranch->branch_id)
                    ->update([
                        'branch_active' => 1,
                    ]);

                // RESTORE RELATED RECORDS (add this block)
                DB::table('service_package_areas')
                    ->where('branch_id', $existingBranch->branch_id)
                    ->update(['svcpa_active' => 1]);

                DB::table('service_package_area_termites')
                    ->where('branch_id', $existingBranch->branch_id)
                    ->update(['svcpat_active' => 1]);

                DB::table('service_package_area_devices')
                    ->where('branch_id', $existingBranch->branch_id)
                    ->update(['svcpad_active' => 1]);

                DB::table('service_package_area_locations')
                    ->where('branch_id', $existingBranch->branch_id)
                    ->update(['svcpal_active' => 1]);

                logUserActivity('Manage Branches', 'Reactivated branch ' . $request->branch_name);

                session()->flash('successMessage', 'Existing branch has been reactivated.');
                return redirect()->back();
            }

            // If already active
            session()->flash('errorMessage', 'Branch already exists.');
            return redirect()->back();
        }

        // Create new branch
        $branch_id = DB::table('branches')->insertGetId([
            'branch_uuid' => generateuuid(),
            'branch_name' => strtoupper($request->branch_name),
            'branch_longitude' => $request->branch_longitude,
            'branch_latitude' => $request->branch_latitude,
            'branch_date_created' => Carbon::now(),
            'branch_created_by' => session('usr_id'),
            'branch_active' => 1,
        ]);

        $areas = [
            ['svcpa_area' => 'ATTIC', 'svcpa_cost' => 10.00],
            ['svcpa_area' => 'BASEMENT', 'svcpa_cost' => 20.00],
            ['svcpa_area' => 'BATHROOM', 'svcpa_cost' => 30.00],
            ['svcpa_area' => 'BEDROOM', 'svcpa_cost' => 40.00],
            ['svcpa_area' => 'DINING ROOM', 'svcpa_cost' => 50.00],
            ['svcpa_area' => 'GARAGE', 'svcpa_cost' => 60.00],
            ['svcpa_area' => 'GARDEN/YARD', 'svcpa_cost' => 70.00],
            ['svcpa_area' => 'KITCHEN', 'svcpa_cost' => 80.00],
            ['svcpa_area' => 'LIVING ROOM', 'svcpa_cost' => 90.00],
            ['svcpa_area' => 'OFFICE/STUDY', 'svcpa_cost' => 100.00],
            ['svcpa_area' => 'OTHERS', 'svcpa_cost' => 0.00],
            ['svcpa_area' => 'STORAGE ROOM', 'svcpa_cost' => 110.00],
            ['svcpa_area' => 'WHOLE PROPERTY', 'svcpa_cost' => 120.00],
        ];

        $servicePackageAreas = [];

        foreach ($areas as $area) {
            $servicePackageAreas[] = [
                'svcpa_uuid' => generateuuid(),
                'branch_id' => $branch_id,
                'svcpa_area' => $area['svcpa_area'],
                'svcpa_cost' => $area['svcpa_cost'],
                'svcpa_date_created' => Carbon::now(),
                'svcpa_created_by' => session('usr_id'),
                'svcpa_active' => 1,
            ];
        }

        DB::table('service_package_areas')->insert($servicePackageAreas);

        $termites = [
            ['svcpat_sqm_details' => '1sqm - 50sqm', 'svcpat_cost' => 10000.00],
            ['svcpat_sqm_details' => '51sqm - 100sqm', 'svcpat_cost' => 184.00],
            ['svcpat_sqm_details' => '101sqm - 500sqm', 'svcpat_cost' => 150.00],
            ['svcpat_sqm_details' => '501sqm - 1000sqm', 'svcpat_cost' => 120.00],
            ['svcpat_sqm_details' => '1001sqm - 999999sqm', 'svcpat_cost' => 100.00],
        ];

        $servicePackageAreaTermites = [];

        foreach ($termites as $termite) {
            $servicePackageAreaTermites[] = [
                'svcpat_uuid' => generateuuid(),
                'branch_id' => $branch_id,
                'svcpat_sqm_details' => $termite['svcpat_sqm_details'],
                'svcpat_cost' => $termite['svcpat_cost'],
                'svcpat_date_created' => Carbon::now(),
                'svcpat_created_by' => session('usr_id'),
                'svcpat_active' => 1,
            ];
        }

        DB::table('service_package_area_termites')->insert($servicePackageAreaTermites);

        DB::table('service_package_area_devices')->insert([
            'svcpad_uuid' => generateuuid(),
            'branch_id' => $branch_id,
            'svcpad_cost' => 15000,
            'svcpad_date_created' => Carbon::now(),
            'svcpad_created_by' => session('usr_id'),
            'svcpad_active' => 1,
        ]);

        DB::table('service_package_area_locations')->insert([
            'svcpal_uuid' => generateuuid(),
            'branch_id' => $branch_id,
            'svcpal_first_cost' => 100.00,
            'svcpal_succeeding_cost' => 10.00,
            'svcpal_date_created' => Carbon::now(),
            'svcpal_created_by' => session('usr_id'),
            'svcpal_active' => 1,
        ]);

        logUserActivity('Manage Branches', 'Added new branch ' . $request->branch_name);
        session()->flash('successMessage', 'Branch has been added.');
        return redirect()->back();
    }

    public function branches_update(Request $request, $branch_id)
    {
        // Validate request
        $request->validate([
            'branch_name' => 'required|string|max:255',
            'branch_latitude' => 'required|numeric',
            'branch_longitude' => 'required|numeric',
        ]);

        DB::table('branches')
            ->where('branch_id', $branch_id)
            ->update([
                'branch_name' => strtoupper($request->branch_name),
                'branch_latitude' => $request->branch_latitude,
                'branch_longitude' => $request->branch_longitude,
                'branch_date_modified' => Carbon::now(),
                'branch_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Branches', 'Updated branch ' . $request->branch_name);

        session()->flash('successMessage', 'Branch has been updated.');
        return redirect()->back();
    }

    public function branches_delete(Request $request, $branch_id)
    {
        $branch = DB::table('branches')
            ->where('branch_id', '=', $branch_id)
            ->first();

        if (!$branch) {
            alert()->error('Client not found.');
            return redirect()->back();
        }

        // Deactivate branch
        DB::table('branches')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'branch_date_modified' => Carbon::now(),
                'branch_modified_by' => session('usr_id'),
                'branch_active' => 0,
            ]);

        // Deactivate related service package areas
        DB::table('service_package_areas')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'svcpa_active' => 0,
            ]);

        // Deactivate related termites
        DB::table('service_package_area_termites')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'svcpat_active' => 0,
            ]);

        // Deactivate related devices
        DB::table('service_package_area_devices')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'svcpad_active' => 0,
            ]);

        // Deactivate related locations
        DB::table('service_package_area_locations')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'svcpal_active' => 0,
            ]);

        logUserActivity('Manage Branches', 'Deleted branch ' . $branch->branch_name);

        session()->flash('successMessage', 'Branch has been Deleted.');
        return redirect()->back();
    }

    public function branches_restore(Request $request, $branch_id)
    {
        $branch = DB::table('branches')
            ->where('branch_id', '=', $branch_id)
            ->first();

        if (!$branch) {
            alert()->error('Client not found.');
            return redirect()->back();
        }

        // Restore branch
        DB::table('branches')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'branch_date_modified' => Carbon::now(),
                'branch_modified_by' => session('usr_id'),
                'branch_active' => 1,
            ]);

        // Restore related service package areas
        DB::table('service_package_areas')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'svcpa_active' => 1,
            ]);

        // Restore related termites
        DB::table('service_package_area_termites')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'svcpat_active' => 1,
            ]);

        // Restore related devices
        DB::table('service_package_area_devices')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'svcpad_active' => 1,
            ]);

        // Restore related locations
        DB::table('service_package_area_locations')
            ->where('branch_id', '=', $branch_id)
            ->update([
                'svcpal_active' => 1,
            ]);

        logUserActivity('Manage Branches', 'Restored branch ' . $branch->branch_name);

        session()->flash('successMessage', 'Branch has been Restored.');
        return redirect()->back();
    }
    // END BRANCHES

    // START SERVICES
    public function services_active(Request $request)
    {
        $search = $request->search ?? '';
        $sessionBranchId = session('branch_id');

        // General Service Package Areas
        $query = DB::table('service_package_areas')
            ->leftJoin('branches', 'service_package_areas.branch_id', '=', 'branches.branch_id')
            ->where('service_package_areas.svcpa_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('service_package_areas.branch_id', $sessionBranchId);
        }

        $query->select(
            'service_package_areas.svcpa_id',
            'service_package_areas.branch_id',
            'branches.branch_name',
            'service_package_areas.svcpa_area',
            'service_package_areas.svcpa_cost',
            'service_package_areas.svcpa_date_created'
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('service_package_areas.svcpa_area', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('branches.branch_name')
            ->orderBy('service_package_areas.svcpa_area');

        $services = $query->paginate(500);

        // Termite Service Package Areas
        $termiteQuery = DB::table('service_package_area_termites')
            ->leftJoin('branches', 'service_package_area_termites.branch_id', '=', 'branches.branch_id')
            ->where('service_package_area_termites.svcpat_active', 1);

        // Branch filter (same logic as services)
        if ($sessionBranchId != 1) {
            $termiteQuery->where('service_package_area_termites.branch_id', $sessionBranchId);
        }

        // Search filter
        if (!empty($search)) {
            $termiteQuery->where(function ($q) use ($search) {
                $q->where('service_package_area_termites.svcpat_sqm_details', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $termiteServices = $termiteQuery
            ->select(
                'service_package_area_termites.svcpat_id',
                'service_package_area_termites.branch_id',
                'branches.branch_name',
                'service_package_area_termites.svcpat_sqm_details',
                'service_package_area_termites.svcpat_cost',
                'service_package_area_termites.svcpat_date_created'
            )
            ->orderBy('branches.branch_name')
            ->orderBy('service_package_area_termites.svcpat_sqm_details')
            ->paginate(500);

        // Service Packages (right panel)
        $packages = DB::table('service_packages')
            ->where('svcp_active', 1)
            ->get();

        // Branches (for any dropdowns)
        $branches = DB::table('branches')
            ->where('branch_active', 1)
            ->orderBy('branch_name')
            ->get();

        // Device Costing
        $deviceCosts = DB::table('service_package_area_devices')
            ->leftJoin('branches', 'service_package_area_devices.branch_id', '=', 'branches.branch_id')
            ->where('service_package_area_devices.svcpad_active', 1);

        if ($sessionBranchId != 1) {
            $deviceCosts->where('service_package_area_devices.branch_id', $sessionBranchId);
        }

        if (!empty($search)) {
            $deviceCosts->where(function ($q) use ($search) {
                $q->where('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $deviceCosts = $deviceCosts
            ->select(
                'service_package_area_devices.svcpad_id',
                'service_package_area_devices.branch_id',
                'service_package_area_devices.svcpad_cost',
                'branches.branch_name'
            )
            ->orderBy('branches.branch_name')
            ->get();

        $locationCosts = DB::table('service_package_area_locations')
            ->leftJoin('branches', 'service_package_area_locations.branch_id', '=', 'branches.branch_id')
            ->where('service_package_area_locations.svcpal_active', 1);

        if ($sessionBranchId != 1) {
            $locationCosts->where('service_package_area_locations.branch_id', $sessionBranchId);
        }

        if (!empty($search)) {
            $locationCosts->where(function ($q) use ($search) {
                $q->where('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $locationCosts = $locationCosts
            ->select(
                'service_package_area_locations.svcpal_id',
                'service_package_area_locations.branch_id',
                'service_package_area_locations.svcpal_first_cost',
                'service_package_area_locations.svcpal_succeeding_cost',
                'service_package_area_locations.svcpal_active',
                'branches.branch_name'
            )
            ->orderBy('branches.branch_name')
            ->get();

        return view('management.services.active', compact(
            'services',
            'termiteServices',
            'packages',
            'branches',
            'search',
            'deviceCosts',
            'locationCosts'
        ));
    }

    public function services_area_cost_update(Request $request, $svcpa_id)
    {
        // Validate request
        $request->validate([
            'svcpa_cost' => 'required|numeric|min:0',
        ]);

        // Get current record
        $service = DB::table('service_package_areas')
            ->where('svcpa_id', $svcpa_id)
            ->first();

        // Normalize values for consistent logging
        $formatCost = fn($value) => number_format((float) $value, 2, '.', '');

        $oldCost = $service ? $formatCost($service->svcpa_cost) : '0.00';
        $newCost = $formatCost($request->svcpa_cost);

        // Update record
        DB::table('service_package_areas')
            ->where('svcpa_id', $svcpa_id)
            ->update([
                'svcpa_cost' => $request->svcpa_cost,
                'svcpa_date_modified' => Carbon::now(),
                'svcpa_modified_by' => session('usr_id'),
            ]);

        // Log activity
        logUserActivity(
            'Manage Service Pricing',
            'Updated service area ' . $request->svcpa_area .
            ' in ' . $request->branch_name .
            ' from cost ' . $oldCost .
            ' to ' . $newCost
        );

        session()->flash('successMessage', 'Service cost has been updated.');

        return redirect()->back();
    }

    public function services_area_termites_cost_update(Request $request, $svcpat_id)
    {
        // Validate request
        $request->validate([
            'svcpat_cost' => 'required|numeric|min:0',
        ]);

        // Get current record
        $termite = DB::table('service_package_area_termites')
            ->where('svcpat_id', $svcpat_id)
            ->first();

        // Normalize values for consistent logging
        $formatCost = fn($value) => number_format((float) $value, 2, '.', '');

        $oldCost = $termite ? $formatCost($termite->svcpat_cost) : '0.00';
        $newCost = $formatCost($request->svcpat_cost);

        // Update record
        DB::table('service_package_area_termites')
            ->where('svcpat_id', $svcpat_id)
            ->update([
                'svcpat_cost' => $request->svcpat_cost,
                'svcpat_date_modified' => Carbon::now(),
                'svcpat_modified_by' => session('usr_id'),
            ]);

        // Log activity
        logUserActivity(
            'Manage Termite Service Pricing',
            'Updated termite service "' . $request->svcpat_sqm_details . '"' .
            ' in ' . $request->branch_name .
            ' from ' . $oldCost . ' to ' . $newCost
        );

        session()->flash('successMessage', 'Termite service cost has been updated.');

        return redirect()->back();
    }

    public function services_area_delete(Request $request, $svcpa_id)
    {
        $service = DB::table('service_package_areas')
            ->where('svcpa_id', '=', $svcpa_id)
            ->first();

        if (!$service) {
            alert()->error('Service area not found.');
            return redirect()->back();
        }

        DB::table('service_package_areas')
            ->where('svcpa_id', '=', $svcpa_id)
            ->update([
                'svcpa_date_modified' => Carbon::now(),
                'svcpa_modified_by' => session('usr_id'),
                'svcpa_active' => 0,
            ]);

        logUserActivity('Manage Services', 'Deleted area ' . $service->svcpa_area);

        session()->flash('successMessage', 'Area has been Deleted.');
        return redirect()->back();
    }

    public function services_area_restore(Request $request, $svcpa_id)
    {
        $service = DB::table('service_package_areas')
            ->where('svcpa_id', '=', $svcpa_id)
            ->first();

        if (!$service) {
            alert()->error('Service area not found.');
            return redirect()->back();
        }

        DB::table('service_package_areas')
            ->where('svcpa_id', '=', $svcpa_id)
            ->update([
                'svcpa_date_modified' => Carbon::now(),
                'svcpa_modified_by' => session('usr_id'),
                'svcpa_active' => 1,
            ]);

        logUserActivity('Manage Services', 'Restored area ' . $service->svcpa_area);

        session()->flash('successMessage', 'Area has been Restored.');
        return redirect()->back();
    }

    public function services_area_device_cost_update(Request $request, $svcpad_id)
    {
        // Validate request
        $request->validate([
            'svcpad_cost' => 'required|numeric',
        ]);

        // Get current record
        $device = DB::table('service_package_area_devices')
            ->leftJoin('branches', 'service_package_area_devices.branch_id', '=', 'branches.branch_id')
            ->where('service_package_area_devices.svcpad_id', $svcpad_id)
            ->select(
                'service_package_area_devices.*',
                'branches.branch_name'
            )
            ->first();

        // Normalize values for logging
        $formatCost = fn($value) => number_format((float) $value, 2, '.', '');

        $oldCost = $device ? $formatCost($device->svcpad_cost) : '0.00';
        $newCost = $formatCost($request->svcpad_cost);

        // Update record
        DB::table('service_package_area_devices')
            ->where('svcpad_id', $svcpad_id)
            ->update([
                'svcpad_cost' => $request->svcpad_cost,
                'svcpad_date_modified' => Carbon::now(),
                'svcpad_modified_by' => session('usr_id'),
            ]);

        // Log activity
        logUserActivity(
            'Manage Device Costing',
            'Updated device cost in ' . $device->branch_name .
            ' from cost ' . $oldCost .
            ' to ' . $newCost
        );

        // Flash message
        session()->flash('successMessage', 'Device cost has been updated.');

        return redirect()->back();
    }

    public function services_area_location_cost_update(Request $request, $svcpal_id)
    {
        // Validate request
        $request->validate([
            'svcpal_first_cost' => 'required|numeric',
            'svcpal_succeeding_cost' => 'required|numeric',
        ]);

        // Get current record
        $location = DB::table('service_package_area_locations')
            ->leftJoin('branches', 'service_package_area_locations.branch_id', '=', 'branches.branch_id')
            ->where('service_package_area_locations.svcpal_id', $svcpal_id)
            ->select(
                'service_package_area_locations.*',
                'branches.branch_name'
            )
            ->first();

        // Normalize values for logging
        $format = fn($value) => number_format((float) $value, 2, '.', '');

        $oldFirst = $location ? $format($location->svcpal_first_cost) : '0.00';
        $oldSucceeding = $location ? $format($location->svcpal_succeeding_cost) : '0.00';

        $newFirst = $format($request->svcpal_first_cost);
        $newSucceeding = $format($request->svcpal_succeeding_cost);

        // Update record
        DB::table('service_package_area_locations')
            ->where('svcpal_id', $svcpal_id)
            ->update([
                'svcpal_first_cost' => $request->svcpal_first_cost,
                'svcpal_succeeding_cost' => $request->svcpal_succeeding_cost,
                'svcpal_date_modified' => Carbon::now(),
                'svcpal_modified_by' => session('usr_id'),
            ]);

        // Log activity (same style as device function)
        logUserActivity(
            'Manage Location Costing',
            'Updated location cost in ' . $location->branch_name .
            ' from cost ' . $oldFirst . ' / ' . $oldSucceeding .
            ' to ' . $newFirst . ' / ' . $newSucceeding
        );

        session()->flash('successMessage', 'Location cost has been updated.');

        return redirect()->back();
    }
    // END SERVICES

    // START LOGS
    public function login_histories(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('user_login_logs')
            ->leftJoin('users', 'user_login_logs.usr_id', '=', 'users.usr_id');

        $query->select(
            'user_login_logs.log_id',
            'user_login_logs.log_date',
            'user_login_logs.log_ip',
            'user_login_logs.log_mac',
            'users.usr_first_name',
            'users.usr_last_name'
        );

        // SEARCH
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('user_login_logs.log_ip', 'LIKE', "%$search%")
                    ->orWhere('user_login_logs.log_mac', 'LIKE', "%$search%");
            });
        }

        // IMPORTANT: latest first
        $query->orderBy('user_login_logs.log_date', 'desc');

        $logs = $query->paginate(1000);

        return view('management.logs.login', compact('logs', 'search'));
    }

    public function price_histories(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('user_price_change_logs')
            ->leftJoin('users as updater', 'user_price_change_logs.upcl_created_by', '=', 'updater.usr_id')
            ->leftJoin('services', 'user_price_change_logs.svc_id', '=', 'services.svc_id')
            ->leftJoin('users as client', 'services.usr_id', '=', 'client.usr_id')
            ->where('user_price_change_logs.upcl_active', '=', '1');

        $query->select(
            'user_price_change_logs.upcl_uuid',
            'user_price_change_logs.svc_id',
            'user_price_change_logs.upcl_old_final_price',
            'user_price_change_logs.upcl_new_final_price',
            'user_price_change_logs.upcl_date_created',
            'services.svc_sa_number',
            'updater.usr_first_name as updater_first_name',
            'updater.usr_last_name as updater_last_name',
            'client.usr_first_name as client_first_name',
            'client.usr_last_name as client_last_name'
        );

        // SEARCH
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('updater.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('updater.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('client.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('client.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('services.svc_sa_number', 'LIKE', "%$search%");
            });
        }

        // IMPORTANT: latest first
        $query->orderBy('user_price_change_logs.upcl_date_created', 'desc');

        $logs = $query->paginate(1000);

        return view('management.logs.price', compact('logs', 'search'));
    }

    public function user_histories(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('user_activity_logs')
            ->leftJoin('users', 'user_activity_logs.usr_id', '=', 'users.usr_id')
            ->where('user_activity_logs.log_active', '=', '1');

        $query->select(
            'user_activity_logs.log_id',
            'user_activity_logs.log_date',
            'user_activity_logs.log_title',
            'user_activity_logs.log_details',
            'user_activity_logs.log_active',
            'users.usr_first_name',
            'users.usr_last_name'
        );

        // SEARCH
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('user_activity_logs.log_title', 'LIKE', "%$search%")
                    ->orWhere('user_activity_logs.log_details', 'LIKE', "%$search%");
            });
        }

        // IMPORTANT: latest first
        $query->orderBy('user_activity_logs.log_date', 'desc');

        $logs = $query->paginate(1000);

        return view('management.logs.user', compact('logs', 'search'));
    }
    // END LOGS
}
