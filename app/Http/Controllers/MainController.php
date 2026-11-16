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

    public function home()
    {
        return view('home');
    }
}
