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

    // START PARTNERS
    private function savePartnerImage($file)
    {
        $dir = public_path('images/partners');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileName = uniqid() . '_' . $file->getClientOriginalName();
        $path = $dir . '/' . $fileName;

        $source = imagecreatefrompng($file->getPathname());

        if ($source) {
            if (imageistruecolor($source) === false) {
                imagepalettetotruecolor($source);
            }

            // keep transparency
            imagealphablending($source, false);
            imagesavealpha($source, true);

            imagepng($source, $path, 7);
            imagedestroy($source);
        }

        return $fileName;
    }

    public function partners()
    {
        $partners = DB::table('homepage_partners')
            ->orderBy('prt_active', 'desc')
            ->orderBy('prt_title', 'asc')
            ->get();

        return view('admin.homepage.partners', compact('partners'));
    }

    public function partners_add(Request $request)
    {
        $request->validate([
            'prt_title' => 'required|string|max:255',
            'prt_image' => 'required|mimes:png|max:8192',
        ]);

        $imageName = $this->savePartnerImage($request->file('prt_image'));

        DB::table('homepage_partners')->insert([
            'prt_uuid' => generateuuid(),
            'prt_title' => $request->prt_title,
            'prt_image' => $imageName,
            'prt_date_created' => Carbon::now(),
            'prt_created_by' => session('usr_id'),
            'prt_active' => 1,
        ]);

        logUserActivity('Manage Homepage', 'Added partner ' . $request->prt_title);

        session()->flash('successMessage', 'Partner has been added.');
        return redirect()->back();
    }

    public function partners_update(Request $request, $obj_id)
    {
        $request->validate([
            'prt_title' => 'required|string|max:255',
            'prt_image' => 'nullable|mimes:png|max:8192',
        ]);

        $partner = DB::table('homepage_partners')
            ->where('prt_id', $obj_id)
            ->first();

        if (!$partner) {
            alert()->error('Error', 'Partner not found.');
            return redirect()->back();
        }

        $updateData = [
            'prt_title' => $request->prt_title,
            'prt_date_modified' => Carbon::now(),
            'prt_modified_by' => session('usr_id'),
        ];

        if ($request->hasFile('prt_image')) {
            $updateData['prt_image'] = $this->savePartnerImage($request->file('prt_image'));

            // remove old image
            if (!empty($partner->prt_image)) {
                $old = public_path('images/partners/' . $partner->prt_image);
                if (file_exists($old)) {
                    @unlink($old);
                }
            }
        }

        DB::table('homepage_partners')
            ->where('prt_id', $obj_id)
            ->update($updateData);

        logUserActivity('Manage Homepage', 'Updated partner ' . $partner->prt_title);

        session()->flash('successMessage', 'Partner has been updated.');
        return redirect()->back();
    }

    public function partners_delete(Request $request, $obj_id)
    {
        $partner = DB::table('homepage_partners')
            ->where('prt_id', $obj_id)
            ->first();

        if (!$partner) {
            alert()->error('Error', 'Partner not found.');
            return redirect()->back();
        }

        $newStatus = $partner->prt_active == 1 ? 0 : 1;

        DB::table('homepage_partners')
            ->where('prt_id', $obj_id)
            ->update([
                'prt_active' => $newStatus,
                'prt_date_modified' => Carbon::now(),
                'prt_modified_by' => session('usr_id'),
            ]);

        logUserActivity(
            'Manage Homepage',
            ($newStatus == 1 ? 'Activated' : 'Deactivated') . ' partner ' . $partner->prt_title
        );

        session()->flash('successMessage', 'Partner has been ' . ($newStatus == 1 ? 'activated' : 'deactivated') . '.');
        return redirect()->back();
    }
    // END PARTNERS

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