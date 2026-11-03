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

        return view('admin.management.addresses.active', compact('addresses', 'search'));
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

        return view('admin.management.addresses.deleted', compact('addresses', 'search'));
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

    // START POSITIONS
    public function positions_active(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('positions')
            ->where('positions.pos_active', '=', '1');

        $query->select(
            'positions.pos_id',
            'positions.pos_name',
            'positions.pos_description',
            'positions.pos_active',
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('positions.pos_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_last_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('positions.pos_name', 'asc');

        $positions = $query->paginate(500);

        return view('admin.management.positions.active', compact('positions', 'search'));
    }

    public function positions_deleted(Request $request)
    {
        $search = $request->search ?? '';

        $query = DB::table('positions')
            ->where('positions.pos_active', '=', '0');

        $query->select(
            'positions.pos_id',
            'positions.pos_name',
            'positions.pos_description',
            'positions.pos_active',
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('positions.pos_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('creator.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('modifier.usr_last_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('positions.pos_name', 'asc');

        $positions = $query->paginate(500);

        return view('admin.management.positions.deleted', compact('positions', 'search'));
    }

    public function positions_add(Request $request)
    {
        $request->validate([
            'pos_name' => 'required|string|max:255|unique:positions,pos_name',
            'pos_description' => 'nullable|string|max:255',
        ]);

        DB::table('positions')->insert([
            'pos_uuid' => generateuuid(),
            'pos_name' => strtoupper($request->pos_name),
            'pos_description' => strtoupper($request->pos_description),
            'pos_date_created' => Carbon::now(),
            'pos_created_by' => session('usr_id'),
            'pos_active' => 1,
        ]);

        logUserActivity('Manage Positions', 'Added new position ' . $request->pos_name);

        session()->flash('successMessage', 'Position has been added.');
        return redirect()->back();
    }

    public function positions_update(Request $request, $pos_id)
    {
        // Validate request
        $request->validate([
            'pos_name' => 'required|string|max:255',
            'pos_description' => 'nullable|string|max:255',
        ]);

        DB::table('positions')
            ->where('pos_id', $pos_id)
            ->update([
                'pos_name' => strtoupper($request->pos_name),
                'pos_description' => strtoupper($request->pos_description),
                'pos_date_modified' => Carbon::now(),
                'pos_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Positions', 'Updated position ' . $request->pos_name);

        session()->flash('successMessage', 'Position has been updated.');
        return redirect()->back();
    }

    public function positions_delete(Request $request, $pos_id)
    {
        $position = DB::table('positions')
            ->where('pos_id', '=', $pos_id)
            ->first();

        if (!$position) {
            alert()->error('Position not found.');
            return redirect()->back();
        }

        DB::table('positions')
            ->where('pos_id', '=', $pos_id)
            ->update([
                'pos_date_modified' => Carbon::now(),
                'pos_modified_by' => session('usr_id'),
                'pos_active' => 0,
            ]);

        logUserActivity('Manage Positions', 'Deleted position ' . $position->pos_name);

        session()->flash('successMessage', 'Position has been Deleted.');
        return redirect()->back();
    }

    public function positions_restore(Request $request, $pos_id)
    {
        $position = DB::table('positions')
            ->where('pos_id', '=', $pos_id)
            ->first();

        if (!$position) {
            alert()->error('Position not found.');
            return redirect()->back();
        }

        DB::table('positions')
            ->where('pos_id', '=', $pos_id)
            ->update([
                'pos_date_modified' => Carbon::now(),
                'pos_modified_by' => session('usr_id'),
                'pos_active' => 1,
            ]);

        logUserActivity('Manage Positions', 'Restored position ' . $position->pos_name);

        session()->flash('successMessage', 'Position has been Restored.');
        return redirect()->back();
    }
    // END POSITIONS

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

        return view('admin.management.logs.login', compact('logs', 'search'));
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

        return view('admin.management.logs.user', compact('logs', 'search'));
    }
    // END LOGS
}
