<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\DemoMail;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function index()
    {
        $emailData = [
            'title' => 'Mail from ItSolutionStuff.com',
            'body' => 'This is for testing email using smtp.'
        ];

        Mail::to('james.apoloz27@gmail.com')->send(new DemoMail($emailData));

        dd("Email is sent successfully.");
    }
}
