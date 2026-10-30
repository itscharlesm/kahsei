<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function home()
    {
        $logins = DB::table('user_login_logs')
            ->select('users.usr_last_name', 'users.usr_first_name', 'users.usr_image_path', 'user_login_logs.*', DB::raw('max(user_login_logs.log_date) as log_date_max'))
            ->join('users', 'users.usr_id', 'user_login_logs.usr_id')
            ->orderBy('log_date_max', 'desc')
            ->groupby('user_login_logs.usr_id')
            ->limit(3)
            ->get();

        $announcements = DB::table('announcements')
            ->join('users', 'users.usr_id', 'announcements.ann_created_by')
            ->where('ann_active', '=', '1')
            ->orderBy('ann_date_created', 'DESC')
            ->limit(6)
            ->get();

        return view('admin.home.main', compact('logins', 'announcements'));
    }
}