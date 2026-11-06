<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    // START ABOUT
    public function about()
    {
        $about = DB::table('homepage_about')
            ->where('about_active', 1)
            ->first();

        return view('admin.homepage.about', compact('about'));
    }

    public function about_update(Request $request, $about_id)
    {
        // Validate request
        $request->validate([
            'about_details' => 'required',
        ]);

        DB::table('homepage_about')
            ->where('about_id', $about_id)
            ->update([
                'about_details' => $request->about_details,
                'about_date_modified' => Carbon::now(),
                'about_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Homepage', 'Updated about');

        session()->flash('successMessage', 'About has been updated.');
        return redirect()->back();
    }
    // END ABOUT

    // START VISION
    public function vision()
    {
        $vision = DB::table('homepage_vision')
            ->where('vis_active', 1)
            ->first();

        return view('admin.homepage.vision', compact('vision'));
    }

    public function vision_update(Request $request, $vis_id)
    {
        // Validate request
        $request->validate([
            'vis_details' => 'required',
        ]);

        DB::table('homepage_vision')
            ->where('vis_id', $vis_id)
            ->update([
                'vis_details' => $request->vis_details,
                'vis_date_modified' => Carbon::now(),
                'vis_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Homepage', 'Updated vision');

        session()->flash('successMessage', 'Vision has been updated.');
        return redirect()->back();
    }
    // END VISION
}