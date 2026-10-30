<?php
use Carbon\Carbon;

function normalizeAddressStreet(?string $street): ?string
{
    return $street === null
        ? null
        : mb_strtoupper($street, 'UTF-8');
}

function getTransactionNumber()
{
    $latest_mtrx_number = DB::table('menu_transactions')
        ->select('mtrx_id')
        ->orderBy('mtrx_id', 'desc')
        ->first();

    if (!$latest_mtrx_number) {
        $mtrx_number = '000001';
    } else {
        $lastNumber = intval($latest_mtrx_number->mtrx_id);
        $mtrx_number = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    return $mtrx_number;
}

function getTotalTransactionAmountToday()
{
    $today = Carbon::today()->toDateString();

    $totalTransactionAmount = DB::table('menu_transactions')
        ->whereDate('mtrx_date_created', $today)
        ->sum('mtrx_total');

    return $totalTransactionAmount;
}

function getCashOnHandtToday()
{
    $today = Carbon::today()->toDateString();

    $totalTransactionAmount = DB::table('user_cash')
        ->whereDate('coh_date_created', $today)
        ->sum('coh_on_hand_cash');

    return $totalTransactionAmount;
}

// function hasUploadedPhotos($inno_id)
// {
//     $innovation_image = DB::table('innovation_images')
//     ->where('inno_id','=',$inno_id)
//     ->first();

//     if($innovation_image){
//         return true;
//     }else{
//         return false;
//     }
// }

// function getploadedPhoto($inno_id)
// {
//     $innovation_image = DB::table('innovation_images')
//     ->where('inno_id','=',$inno_id)
//     ->first();

//    return $innovation_image->img_file;
// }

// function getProvinceName($prov_id)
// {
//     $location_province = DB::table('location_provinces')
//     ->where('prov_id','=',$prov_id)
//     ->first();

//     return $location_province->prov_name;
// }

// function getMunicipalityName($mun_id)
// {
//     $location_municipality = DB::table('location_municipalities')
//     ->where('mun_id','=',$mun_id)
//     ->first();

//     return $location_municipality->mun_name;
// }

// function getBarangayName($brg_id)
// {
//     $location_barangay = DB::table('location_barangays')
//     ->where('brg_id','=',$brg_id)
//     ->first();

//     return $location_barangay->brg_name;
// }

// function countForApprovalInnovations()
// {
//     $innovations = DB::table('innovations')
//     ->where('inno_active','=','1')
//     ->where('inno_status','=','1')
//     ->count();

//     return $innovations;
// }

// function countApprovedInnovations()
// {
//     $innovations = DB::table('innovations')
//     ->where('inno_active','=','1')
//     ->where('inno_status','=','2')
//     ->count();

//     return $innovations;
// }

// function countDisapprovedInnovations()
// {
//     $innovations = DB::table('innovations')
//     ->where('inno_active','=','1')
//     ->where('inno_status','=','0')
//     ->count();

//     return $innovations;
// }

// function countSubmittedInnovations()
// {
//     $innovations = DB::table('innovations')
//     ->where('inno_active','=','1')
//     ->count();

//     return $innovations;
// }

// function siteHit()
// {
//     DB::table('sitehitusers')
//     ->insert([
//         'usrIP' => \Request::ip(),
//         'usrDate' => \Carbon\Carbon::now()
//     ]);

//     $sitehit = DB::table('sitehits')
//     ->whereDate('hitDate' ,'=', date("Y-m-d"))
//     ->first();

//     if($sitehit){
//         DB::table('sitehits')
//         ->where('hitID','=', $sitehit->hitID)
//         ->increment("hitCount", 1);

//     }else{
//         DB::table('sitehits')
//         ->insert([
//             'hitDate' => date("Y-m-d"),
//             'hitCount' => 1
//         ]);
//     }
// }

function sendEmail($emailSubject, $emailContent, $emailTo)
{
    session()->put('emailTo', $emailTo);
    session()->put('emailSubject', $emailSubject);

    Mail::raw($emailContent, function ($message) {
        $message
            ->to(session()->get('emailTo'), 'Infinit SMS User')
            ->subject(session()->get('emailSubject'));
        $message->from('mailer@infinitsms.com', 'Infinit SMS');
    });
}

function generateuuid()
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = '';

    for ($i = 0; $i < 32; $i++) {
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    }

    return $string;
}

function generateCode()
{
    $characters = '0123456789';
    $string = '';

    for ($i = 0; $i < 5; $i++) {
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    }

    return $string;
}

function generateDigitCode()
{
    return rand(100000, 999999);
}

function unauthorize()
{
    echo redirect('/logout');
    exit();
}

function setUserSessionVariables($user)
{
    Session::put('usr_id', $user->usr_id);
    Session::put('usr_uuid', $user->usr_uuid);
    Session::put('utyp_id', $user->utyp_id);
    Session::put('usr_last_name', $user->usr_last_name);
    Session::put('usr_first_name', $user->usr_first_name);
    Session::put('usr_middle_name', $user->usr_middle_name);
    Session::put('usr_email', $user->usr_email);
    Session::put('usr_birth_date', $user->usr_birth_date);
    Session::put('usr_image_path', $user->usr_image_path);
    Session::put('usr_mobile', $user->usr_mobile);
    Session::put('usr_full_name', $user->usr_first_name . ' ' . $user->usr_middle_name . ' ' . $user->usr_last_name);

    // 👉 Get highest role (LOWEST rol_id = highest priority)
    $highestRole = DB::table('user_roles as ur')
        ->join('roles as r', 'ur.rol_id', '=', 'r.rol_id')
        ->where('ur.usr_id', $user->usr_id)
        ->where('ur.url_active', '1')
        ->orderBy('r.rol_id', 'ASC') // change if using priority column
        ->select('r.rol_name')
        ->first();

    // Store highest role name
    Session::put('role_name', $highestRole ? $highestRole->rol_name : 'No Role');

    // (Optional) still keep your role flags
    $roles = DB::table('roles')->get();

    foreach ($roles as $role) {
        $user_role = DB::table('user_roles')
            ->where('usr_id', '=', $user->usr_id)
            ->where('rol_id', '=', $role->rol_id)
            ->where('url_active', '=', '1')
            ->first();

        Session::put($role->rol_name, $user_role ? '1' : '0');
    }

    recordLogin($user->usr_id);
}

function getUserName($usr_id)
{
    $user = DB::table('users')
        ->where('usr_id', '=', $usr_id)
        ->first();

    if ($user) {
        $last_name = $user->usr_last_name;
        $first_name = $user->usr_first_name;
        $display_name = $first_name . ' ' . $last_name;
        return $display_name;
    } else {
        return '';
    }
}

function incrementUserLoginCounter($usr_email)
{
    DB::table('users')
        ->where('usr_email', '=', $usr_email)
        ->increment('usr_invalid_login_count', 1);
}

function resetUserLoginCounter($usr_email)
{
    DB::table('users')
        ->where('usr_email', '=', $usr_email)
        ->update([
            'usr_invalid_login_count' => '0'
        ]);
}

function recordLogin($usr_id)
{
    DB::table('user_login_logs')
        ->insert([
            'usr_id' => $usr_id,
            'log_date' => \Carbon\Carbon::now(),
            'log_ip' => \Request::ip(),
        ]);
}

function getAvatar($usr_id)
{
    try {
        $user = DB::table('users')
            ->where('usr_id', '=', $usr_id)
            ->first();

        if ($user->usr_image_path <> '') {
            return 'images/users/' . $user->usr_image_path;
        } else {
            return 'images/avatar.png';
        }
    } catch (Exception $e) {
        return 'images/avatar.png';
    }
}

function getLastLogin($usr_id)
{
    $login = DB::table('user_login_logs')
        ->where('usr_id', '=', $usr_id)
        ->orderBy('log_date', 'desc')
        ->first();

    if (isset($login)) {
        return $login->log_date;
    } else {
        return '-never-';
    }
}

if (!function_exists('logUserActivity')) {
    function logUserActivity($role_name, $log_details)
    {
        DB::table('user_activity_logs')->insert([
            'usr_id' => session('usr_id'),
            'log_date' => \Carbon\Carbon::now(),
            'log_title' => $role_name,
            'log_details' => $log_details,
            'log_active' => 1,
        ]);
    }
}

// function getContactCategories($con_id)
// {
//     $contact_categories = DB::table('contact_categories')
//     ->join('categories','categories.cat_id','=','contact_categories.cat_id')
//     ->where('contact_categories.con_id','=',$con_id)
//     ->orderBy('categories.cat_name')
//     ->get();

//     $result = '';

//     foreach($contact_categories as $contact_category)
//     {
//         $result = $result. '<span class="badge badge-info">' . $contact_category->cat_name . '</span> &nbsp';
//     }

//     return $result;
// }

// function isPresentContactCategory($con_id,$cat_id)
// {
//     $contact_category = DB::table('contact_categories')
//     ->where('con_id', '=', $con_id)
//     ->where('cat_id', '=', $cat_id)
//     ->first();

//     if($contact_category){
//         return true;
//     }else{
//         return false;
//     }
// }

// function get_remaining_daily_credits()
// {
//     $daily_credit = DB::table('daily_credits')
//     ->where('acc_id', '=', session('acc_id'))
//     ->whereDate('cdt_date', '=', \Carbon\Carbon::now())
//     ->first();

//     if($daily_credit){
//         return $daily_credit->cdt_credits - $daily_credit->cdt_consumed;
//     }else{
//         $daily_credits = get_daily_account_credits();
//         DB::table('daily_credits')
//         ->insert([
//             'acc_id' => session('acc_id'),
//             'cdt_date' => \Carbon\Carbon::now(),
//             'cdt_credits' => $daily_credits,
//             'cdt_consumed' => '0'
//         ]);
//         return $daily_credits;
//     }

// }

// function get_daily_account_credits()
// {
//     $account = DB::table('accounts')
//     ->where('acc_id', '=', session('acc_id'))
//     ->first();

//     return $account->acc_daily_credits;
// }

// function increment_daily_credit_counter($credits)
// {
//     DB::table('daily_credits')
//     ->where('acc_id','=',session('acc_id'))
//     ->whereDate('cdt_date', '=', \Carbon\Carbon::now())
//     ->increment('cdt_consumed', $credits);
// }

// function count_borrow_requests()
// {
//     $borrow_requests = DB::table('borrow_requests')
//     ->join('equipments','equipments.eqp_id','=','borrow_requests.eqp_id')
//     ->where('borrow_requests.res_status','=','1')
//     ->where('borrow_requests.res_active','=','1')
//     ->orderBy('borrow_requests.res_date_requested','DESC')
//     ->count();

//     return $borrow_requests;
// }

// function count_borrow_unreturned()
// {
//     $borrow_requests = DB::table('borrow_requests')
//     ->join('equipments','equipments.eqp_id','=','borrow_requests.eqp_id')
//     ->where('borrow_requests.res_status','=','2')
//     ->where('borrow_requests.res_active','=','1')
//     ->orderBy('borrow_requests.res_date_requested','DESC')
//     ->count();

//     return $borrow_requests;
// }

// function return_on($bor_date,$num_days)
// {
//     $return_date = date('Y-m-d', strtotime($bor_date . ' + ' . $num_days . ' days'));
//     return $return_date;
// }

// function overdue_days($res_id)
// {
//     $borrow_request = DB::table('borrow_requests')
//     ->join('equipments','equipments.eqp_id','=','borrow_requests.eqp_id')
//     ->where('borrow_requests.res_id','=',$res_id)
//     ->first();

//     $date1 = new DateTime(today());
//     $date2 = new DateTime($borrow_request->res_date_to_return);
//     $interval = $date2->diff($date1);

//     if($date1 > $date2){
//         return '<span class="badge badge-danger">' . $interval->days . ' DAY(S) OVERDUE</span>';
//     }else{
//         return '<span class="badge badge-success"> WILL BE OVERDUE IN ' . $interval->days . ' DAY(S)</span>';
//     }
// }

// function count_overdue_borrows()
// {
//     $borrow_requests = DB::table('borrow_requests')
//     ->join('equipments','equipments.eqp_id','=','borrow_requests.eqp_id')
//     ->where('borrow_requests.res_status','=','2')
//     ->where('borrow_requests.bor_id','=',session('usr_id'))
//     ->where('borrow_requests.res_active','=','1')
//     ->get();

//     $overdue_count = 0;

//     foreach($borrow_requests as $borrow_request)
//     {
//         $date1 = new DateTime(today());
//         $date2 = new DateTime($borrow_request->res_date_to_return);

//         if($date1 > $date2){
//             $overdue_count += 1;
//         }
//     }

//     return $overdue_count;
// }
?>