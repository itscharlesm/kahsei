<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Response;
use DB;

class MainController extends Controller
{
    public function main()
    {
        return view('login');
    }

    public function registration()
    {
        return view('registration');
    }

    // public function mainBorrower()
    // {
    //     $acc_id = '2';

    //     $account = DB::table('accounts')
    //     ->where('acc_id', '=', $acc_id)
    //     ->first();

    //     $mode = 'borrower';

    //     return view('login',compact('account','mode'));
    // }

    // public function mainAccount($acc_id)
    // {
    //     $account = DB::table('accounts')
    //     ->where('acc_id', '=', $acc_id)
    //     ->first();

    //     return view('login',compact('account'));
    // }

    // public function privacypolicy()
    // {
    //     return view('privacypolicy');
    // }

    // public function mapua()
    // {
    //     $acc_id = '2';

    //     $account = DB::table('accounts')
    //     ->where('acc_id', '=', $acc_id)
    //     ->first();

    //     return view('login',compact('account'));
    // }
}
