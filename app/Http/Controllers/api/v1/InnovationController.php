<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Innovation;
use App\Models\InnovationType;
use App\Models\Innovator;
use App\Models\LocationRegion;
use App\Models\Reporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InnovationController extends Controller
{
    /**
     * get_innovations
     * 
     * @param int user_id
     */
    function get_innovations($user_id): JsonResponse
    {
        $innovations = DB::table('innovations')
            ->where('usr_id', $user_id)
            ->where('inno_active', 1)
            // nat_id
            ->leftJoin('innovation_natures', 'innovation_natures.nat_id', 'innovations.nat_id')
            // typ_id
            ->leftJoin('innovation_types', 'innovation_types.typ_id', 'innovations.typ_id')
            // cat_id
            ->leftJoin('innovation_categories', 'innovation_categories.cat_id', 'innovations.cat_id')
            // brg_id
            ->leftJoin('location_barangays', 'location_barangays.brg_id', 'innovations.brg_id')
            // mun_id
            ->leftJoin('location_municipalities', 'location_municipalities.mun_id', 'innovations.mun_id')
            // prov_id
            ->leftJoin('location_provinces', 'location_provinces.prov_id', 'innovations.prov_id')
            // reg_id
            ->leftJoin('location_regions', 'location_regions.reg_id', 'innovations.reg_id')
            // com_id
            ->leftJoin('innovation_community_types', 'innovation_community_types.com_id', 'innovations.com_id')
            // lic_id
            ->leftJoin('innovation_license_types', 'innovation_license_types.lic_id', 'innovations.lic_id')
            // stat_id
            ->leftJoin('innovation_statuses', 'innovation_statuses.stat_id', 'innovations.stat_id')
            ->leftJoin('innovators', 'innovators.inno_id', 'innovations.inno_id')
            ->leftJoin('reporters', 'reporters.inno_id', 'innovations.inno_id')
            ->select(
                'innovations.inno_id',
                'inno_title',
                // 'nat_name',
                // 'typ_name',
                // 'cat_name',
                // 'brg_name',
                // 'mun_name',
                // 'prov_name',
                'reg_name',
                'reg_description',
                // 'com_name',
                // 'lic_name',
                // 'stat_name',
                // 'inno_com_type_specify',
                // 'inno_cover_photo',
                // 'inno_description',
                // 'inno_needs',
                // 'inno_users',
                // 'inno_insights',
                // 'inno_latitude',
                // 'inno_longitude',
                // 'inno_date_solutions_map',
                // 'inno_is_approved_for_posting',
                'inno_date_created',
                'innovators.inov_first_name',
                'innovators.inov_middle_name',
                'innovators.inov_last_name',
                'reporters.rep_first_name',
                // 'reporters.rep_middle_name',
                'reporters.rep_last_name',
                // 'inno_date_modified',
                // 'inno_active',
            )->orderByDesc('innovations.inno_date_created')
            // TODO: Ask sir siang about verified status
            // ->orderBy('innovations.inno_is_approved_for_posting')
            ->get();



        // $innovations = Innovation::with('innovationType')
        // ->select('innovations.*', 'innovation_types.typ_name as innovation_type_name')
        // ->get();
        // dd($innovations);
        return response()->json($innovations);
    }

    /**
     * view_innovation
     * 
     * @param int innovation_id
     */
    function view_innovation($id): JsonResponse
    {
        $innovation = DB::table('innovations')
            ->where('inno_id', $id)
            ->where('inno_active', 1)
            // nat_id
            ->leftJoin('innovation_natures', 'innovation_natures.nat_id', 'innovations.nat_id')
            // typ_id
            ->leftJoin('innovation_types', 'innovation_types.typ_id', 'innovations.typ_id')
            // cat_id
            ->leftJoin('innovation_categories', 'innovation_categories.cat_id', 'innovations.cat_id')
            // brg_id
            ->leftJoin('location_barangays', 'location_barangays.brg_id', 'innovations.brg_id')
            // mun_id
            ->leftJoin('location_municipalities', 'location_municipalities.mun_id', 'innovations.mun_id')
            // prov_id
            ->leftJoin('location_provinces', 'location_provinces.prov_id', 'innovations.prov_id')
            // reg_id
            ->leftJoin('location_regions', 'location_regions.reg_id', 'innovations.reg_id')
            // com_id
            ->leftJoin('innovation_community_types', 'innovation_community_types.com_id', 'innovations.com_id')
            // lic_id
            ->leftJoin('innovation_license_types', 'innovation_license_types.lic_id', 'innovations.lic_id')
            // stat_id
            ->leftJoin('innovation_statuses', 'innovation_statuses.stat_id', 'innovations.stat_id')
            ->select(
                'innovations.inno_id',
                'inno_title',
                'innovations.nat_id',
                'innovations.typ_id',
                'innovations.cat_id',
                'nat_name',
                'typ_name',
                'cat_name',
                'brg_name',
                'inno_brg',
                'mun_name',
                'inno_mun',
                'prov_name',
                'inno_prov',
                'reg_name',
                'inno_reg',
                'reg_description',
                'innovations.com_id',
                'innovations.lic_id',
                'innovations.stat_id',
                'com_name',
                'lic_name',
                'stat_name',
                'inno_cat_type_others',
                'inno_com_type_specify',
                // 'inno_cover_photo',
                'inno_description',
                'inno_needs',
                'inno_users',
                'inno_insights',
                'inno_latitude',
                'inno_longitude',
                'inno_date_solutions_map',
                // 'inno_is_approved_for_posting',
                'inno_date_created',
                // 'inno_date_modified',
                // 'inno_active',
            )->first();

        $innovation_images = DB::table('innovation_images')
            ->where('inno_id', $innovation->inno_id)
            ->where('img_active', 1)
            ->select('img_id', 'img_file')
            ->get();
        // ->pluck('img_id', 'img_file');


        $innovator = DB::table('innovators')
            ->where('inno_id', $innovation->inno_id)
            ->leftJoin('innovator_educ_backgrounds', 'innovator_educ_backgrounds.edub_id', 'innovators.edub_id')
            ->leftJoin('innovator_educ_attainments', 'innovator_educ_attainments.att_id', 'innovators.att_id')
            // ->leftJoin('innovator_affiliations', 'innovator_affiliations.inno_id', 'innovators.inov_id')
            // ->leftJoin('innovator_trainings', 'innovator_trainings.inno_id', 'innovators.inov_id')
            ->select(
                'innovators.inov_id',
                'inov_first_name',
                'inov_middle_name',
                'inov_last_name',
                'inov_age',
                'inov_sex',
                'innovators.edub_id',
                'edub_name',
                'edub_others',
                'innovators.att_id',
                'att_name',
                'att_others',
                'inov_address',
                'inov_mobile',
                'inov_email'
            )->first();

        $reporter = DB::table('reporters')
            ->where('inno_id', $innovation->inno_id)
            ->select(
                'rep_id',
                'rep_first_name',
                'rep_middle_name',
                'rep_last_name',
                'rep_mobile',
                'rep_email'
            )->first();

        // return json_encode($innovation)
        return response()->json([
            'innovation' => $innovation,
            'innovationImages' => $innovation_images,
            'innovator' => $innovator,
            'reporter' => $reporter
        ]);
    }

    /**
     * post_innovation
     * 
     * @param Object from mobile
     */
    function post_innovation(Request $request): JsonResponse
    {
        $user_id = auth('sanctum')->user()->usr_id;

        // dd($user_id);
        $barangay = $request->barangay;
        $municipality = $request->municipality;
        $province = $request->province;
        $region = $request->region;

        //FIND THE INDEXES OF EACH BARANGAY, MUNICIPALITY, PROVINCE AND REGION
        // Get the index of location_regions table with $region 
        $region_id = DB::table('location_regions')
            ->where('reg_name', 'LIKE', '%' . $region . '%')
            ->pluck('reg_id')
            ->first();
        // dd($region_id);

        // Get the index of location_provinces table with $region_id and LIKE $province
        $province_id = DB::table('location_provinces')
            ->where('reg_id', $region_id)
            ->where('prov_name', 'LIKE', '%' . $province . '%')
            ->pluck('prov_id')
            ->first();
        // dd($province_id);

        // Get the index of location_municipalities with $province_id and LIKE $municipality
        $municipality_id = DB::table('location_municipalities')
            ->where('prov_id', $province_id)
            ->where('mun_name', 'LIKE', '%' . $municipality . '%')
            ->pluck('mun_id')
            ->first();
        // dd($municipality_id);

        // Get the index of location_barangays with $municipality_id and LIKE $barangay
        $barangay_id = DB::table('location_barangays')
            ->where('mun_id', $municipality_id)
            ->where('brg_name', 'LIKE', '%' . $barangay . '%')
            ->pluck('brg_id')
            ->first();
        // dd($barangay_id);



        // Store the innovation fields
        $innovation = Innovation::create([
            'inno_uuid' => generateuuid(),
            'usr_id' => $user_id,
            'inno_title' => $request->innovationTitle,
            'nat_id' => $request->innovationNature,
            'typ_id' => $request->innovationType,
            'cat_id' => $request->innovationCategory,
            'inno_cat_type_others' => $request->innovationCategoryOthers,
            'brg_id' => $barangay_id,
            'inno_brg' => $barangay,
            'mun_id' => $municipality_id,
            'inno_mun' => $municipality,
            'prov_id' => $province_id,
            'inno_prov' => $province,
            'reg_id' => $region_id,
            'inno_reg' => $region,
            'com_id' => $request->innovationCommunityType,
            'inno_com_type_specify' => $request->innovationCommunityTypeOthers,
            'lic_id' => $request->innovationLicense,
            'stat_id' => $request->innovationStatus,
            // TODO: Ask sir siang
            'inno_cover_photo' => null,

            'inno_description' => $request->innovationDescription,
            'inno_needs' => $request->innovationNeeds,
            'inno_users' => $request->innovationUsers,
            'inno_insights' => $request->innovationInsights,

            // Temporary null
            'inno_latitude' => $request->latitude,
            'inno_longitude' => $request->longitude,
            'inno_date_solutions_map' => $request->innovationMappingDate,
            'inno_is_approved_for_posting' => 0,
            'inno_status' => 0,
            'inno_created_by' => $user_id,
            'inno_active' => 1
        ]);

        $imageInputs = ['image0', 'image1', 'image2'];

        foreach ($imageInputs as $input) {
            if ($request->hasFile($input)) {
                $file = $request->file($input);
                $fileExtension = $file->getClientOriginalExtension();
                $fileName = generateuuid() . '.' . $fileExtension;

                DB::table('innovation_images')->insert([
                    'inno_id' => $innovation->inno_id,
                    'img_file' => $fileName,
                    'img_active' => '1'
                ]);

                Storage::disk('public')->put('/images/innovations/' . $fileName, fopen($file, 'r+'));
            }
        }

        // Store the innovator fields
        Innovator::create([
            'inno_id' => $innovation->inno_id,
            'inov_last_name' => $request->innovatorLastName,
            'inov_first_name' => $request->innovatorFirstName,
            'inov_middle_name' => $request->innovatorMiddleName,
            'inov_age' => $request->innovatorAge,
            'inov_sex' => $request->innovatorGender == 1 ? "Male" : ($request->innovatorGender == 2 ? "Female" : null),
            'inov_address' => $request->innovatorAddress,
            'inov_mobile' => $request->innovatorContact,
            'inov_email' => $request->innovatorEmail,
            'edub_id' => $request->educationalBackground,
            'edub_others' => $request->educationalBackgroundOther,
            'att_id' => $request->educationalAttainment,
            'att_others' => $request->educationalAttainmentOther
        ]);

        // Store the reporter fields
        Reporter::create([
            'inno_id' => $innovation->inno_id,
            'rep_last_name' => $request->reporterLastName,
            'rep_first_name' => $request->reporterFirstName,
            'rep_middle_name' => $request->reporterMiddleName,
            'rep_mobile' => $request->reporterMobile,
            'rep_email' => $request->reporterEmail,
        ]);

        // return response()->json($request->all());
        // return response()->json(['innovation_id' => $innovation->inno_id]);
        return $this->view_innovation($innovation->inno_id);
    }

    function edit_innovation(Request $request): JsonResponse
    {
        $user_id = auth('sanctum')->user()->usr_id;
        $innovation_id = $request->innovationID;
        $innovator_id = $request->innovatorID;
        $reporter_id = $request->reporterID;

        // dd($user_id);
        $barangay = $request->barangay;
        $municipality = $request->municipality;
        $province = $request->province;
        $region = $request->region;

        //FIND THE INDEXES OF EACH BARANGAY, MUNICIPALITY, PROVINCE AND REGION
        // Get the index of location_regions table with $region 
        $region_id = DB::table('location_regions')
            ->where('reg_name', 'LIKE', '%' . $region . '%')
            ->pluck('reg_id')
            ->first();
        // dd($region_id);

        // Get the index of location_provinces table with $region_id and LIKE $province
        $province_id = DB::table('location_provinces')
            ->where('reg_id', $region_id)
            ->where('prov_name', 'LIKE', '%' . $province . '%')
            ->pluck('prov_id')
            ->first();
        // dd($province_id);

        // Get the index of location_municipalities with $province_id and LIKE $municipality
        $municipality_id = DB::table('location_municipalities')
            ->where('prov_id', $province_id)
            ->where('mun_name', 'LIKE', '%' . $municipality . '%')
            ->pluck('mun_id')
            ->first();
        // dd($municipality_id);

        // Get the index of location_barangays with $municipality_id and LIKE $barangay
        $barangay_id = DB::table('location_barangays')
            ->where('mun_id', $municipality_id)
            ->where('brg_name', 'LIKE', '%' . $barangay . '%')
            ->pluck('brg_id')
            ->first();
        // dd($barangay_id);

        $innovation = Innovation::find($innovation_id);
        $innovation->inno_title = $request->innovationTitle;
        $innovation->nat_id = $request->innovationNature;
        $innovation->typ_id = $request->innovationType;
        $innovation->cat_id = $request->innovationCategory;
        $innovation->inno_cat_type_others = $request->innovationCategoryOthers;
        $innovation->brg_id = $barangay_id;
        $innovation->inno_brg = $barangay;
        $innovation->mun_id = $municipality_id;
        $innovation->inno_mun = $municipality;
        $innovation->prov_id = $province_id;
        $innovation->inno_prov = $province;
        $innovation->reg_id = $region_id;
        $innovation->inno_reg = $region;
        $innovation->com_id = $request->innovationCommunityType;
        $innovation->inno_com_type_specify = $request->innovationCommunityTypeOthers;
        $innovation->lic_id = $request->innovationLicense;
        $innovation->stat_id =  $request->innovationStatus;
        $innovation->inno_description = $request->innovationDescription;
        $innovation->inno_needs = $request->innovationNeeds;
        $innovation->inno_users = $request->innovationUsers;
        $innovation->inno_insights = $request->innovationInsights;
        $innovation->inno_latitude = $request->latitude;
        $innovation->inno_longitude = $request->longitude;
        $innovation->inno_date_solutions_map = $request->innovationMappingDate;
        $innovation->save();


        $imageInputs = ['image0', 'image1', 'image2'];

        foreach ($imageInputs as $input) {
            if ($request->hasFile($input)) {
                $file = $request->file($input);
                $fileExtension = $file->getClientOriginalExtension();
                $fileName = generateuuid() . '.' . $fileExtension;

                DB::table('innovation_images')->insert([
                    'inno_id' => $innovation->inno_id,
                    'img_file' => $fileName,
                    'img_active' => '1'
                ]);

                Storage::disk('public')->put('/images/innovations/' . $fileName, fopen($file, 'r+'));
            }
        }

        $deleted_images = $request->deletedImages;
        $deleted_images_id = explode(",", $deleted_images);

        foreach ($deleted_images_id as $id) {
            DB::table('innovation_images')
                ->where('img_id', $id)
                ->update([
                    'img_active' => '0'
                ]);
        }

        $innovator = Innovator::find($innovator_id);
        $innovator->inov_first_name = $request->innovatorFirstName;
        $innovator->inov_middle_name = $request->innovatorMiddleName;
        $innovator->inov_last_name = $request->innovatorLastName;
        $innovator->inov_age = $request->innovatorAge;
        $innovator->inov_sex = $request->innovatorGender == 1 ? "Male" : ($request->innovatorGender == 2 ? "Female" : null);
        $innovator->inov_address = $request->innovatorAddress;
        $innovator->inov_mobile = $request->innovatorContact;
        $innovator->edub_id = $request->educationalBackground;
        $innovator->edub_others = $request->educationalBackgroundOther;
        $innovator->att_id = $request->educationalAttainment;
        $innovator->att_others = $request->educationalAttainmentOther;
        $innovator->save();

        $reporter = Reporter::find($reporter_id);
        $reporter->rep_first_name = $request->reporterFirstName;
        $reporter->rep_middle_name = $request->reporterMiddleName;
        $reporter->rep_last_name = $request->reporterLastName;
        $reporter->rep_mobile = $request->reporterMobile;
        $reporter->rep_email = $request->reporterEmail;
        $reporter->save();

        // return response()->json($request->all());
        return response()->json('done');
    }

    function delete_innovation(Request $request): JsonResponse
    {
        $user_id = $request->userID;

        // Find innovation with innovationID
        $innovation_id = $request->innovationID;
        $innovation = Innovation::find($innovation_id);

        // Prepare return message
        $return_message = [];

        if ($innovation) {
            // Flag 0 for inactive
            $innovation->inno_active = 0;
            $innovation->save();

            // Return all innovations if it is successfully deleted
            return $this->get_innovations($user_id);
        } else {
            // Return error if innovation is not found
            return response()->json('Error');
        }
    }
}
