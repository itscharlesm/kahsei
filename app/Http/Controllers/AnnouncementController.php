<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Session;
use DB;

class AnnouncementController extends Controller
{
    public function save(Request $request)
    {
        $ann_image = $request->file('ann_image');

        if(isset($ann_image))
        {
            $validator = Validator::make($request->all(), [
                'ann_image' => 'required|image|mimes:jpeg,jpg,png|max:3072',
            ]);

            if ($validator->fails()) {
                alert()->warning('Invalid image attachment','Attached image is either more thyan 3MB or does not conform with allowed file types.');
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $file_uuid = generateuuid();
            $fileName = $file_uuid . '.' . request()->ann_image->getClientOriginalExtension();

            DB::table('announcements')
            ->insert([
                'ann_uuid' => $file_uuid,
                'ann_title' => $request->ann_title,
                'ann_content' => $request->ann_content,
                'ann_image' => $fileName,
                'ann_date_created' => \Carbon\Carbon::now(),
                'ann_created_by' => session('usr_id')
            ]);

            Storage::disk('public')->put('/images/announcements/' . $fileName, fopen($request->file('ann_image'), 'r+'));

        } else {
            DB::table('announcements')
            ->insert([
                'ann_uuid' => generateuuid(),
                'ann_title' => $request->ann_title,
                'ann_content' => $request->ann_content,
                'ann_image' => $ann_image,
                'ann_date_created' => \Carbon\Carbon::now(),
                'ann_created_by' => session('usr_id')
            ]);

        }

        alert()->success('Success','Announcement has been successfully posted.');
        return redirect()->action([AdminController::class, 'home']);
    }

    public function delete($ann_uuid)
    {
        DB::table('announcements')
        ->where('ann_uuid','=',$ann_uuid)
        ->update([
            'ann_active' => '0'
        ]);

        alert()->success('Success','Announcement has been successfully deleted.');
        return redirect()->action([AdminController::class, 'home']);
    }
}
