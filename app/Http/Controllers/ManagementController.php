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
