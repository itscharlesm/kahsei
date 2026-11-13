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

    // START GOALS
    public function goals()
    {
        $goals = DB::table('homepage_goals')
            ->where('goals_active', 1)
            ->first();

        return view('admin.homepage.goals', compact('goals'));
    }

    public function goals_update(Request $request, $goals_id)
    {
        // Validate request
        $request->validate([
            'goals_details' => 'required',
        ]);

        DB::table('homepage_goals')
            ->where('goals_id', $goals_id)
            ->update([
                'goals_details' => $request->goals_details,
                'goals_date_modified' => Carbon::now(),
                'goals_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Homepage', 'Updated goals');

        session()->flash('successMessage', 'Goals has been updated.');
        return redirect()->back();
    }
    // END GOALS

    // START MISSION
    public function mission()
    {
        $mission = DB::table('homepage_mission')
            ->where('mis_active', 1)
            ->first();

        return view('admin.homepage.mission', compact('mission'));
    }

    public function mission_update(Request $request, $mis_id)
    {
        // Validate request
        $request->validate([
            'mis_details' => 'required',
        ]);

        DB::table('homepage_mission')
            ->where('mis_id', $mis_id)
            ->update([
                'mis_details' => $request->mis_details,
                'mis_date_modified' => Carbon::now(),
                'mis_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Homepage', 'Updated mission');

        session()->flash('successMessage', 'Mission has been updated.');
        return redirect()->back();
    }
    // END MISSION

    // START OBJECTIVES
    public function objectives()
    {
        $objectives = DB::table('homepage_objectives')
            ->where('obj_active', 1)
            ->first();

        return view('admin.homepage.objectives', compact('objectives'));
    }

    public function objectives_update(Request $request, $obj_id)
    {
        // Validate request
        $request->validate([
            'obj_details' => 'required',
        ]);

        DB::table('homepage_objectives')
            ->where('obj_id', $obj_id)
            ->update([
                'obj_details' => $request->obj_details,
                'obj_date_modified' => Carbon::now(),
                'obj_modified_by' => session('usr_id'),
            ]);

        logUserActivity('Manage Homepage', 'Updated objectives');

        session()->flash('successMessage', 'Objectives has been updated.');
        return redirect()->back();
    }
    // END OBJECTIVES

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