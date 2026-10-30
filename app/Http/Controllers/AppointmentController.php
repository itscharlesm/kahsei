<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use App\Http\Controllers\api\v1\mobile_controllers\MobileMapboxDistanceController;

class AppointmentController extends Controller
{
    // START BOOK APPOINTMENTS
    public function clients(Request $request)
    {
        $search = $request->search ?? '';

        $sessionBranchId = session('branch_id');

        // Base query
        $query = DB::table('users')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.branch_id')
            ->where('users.utyp_id', '=', '3')
            ->where('users.usr_active', '=', '1');

        // Branch filter (unless super admin)
        if ($sessionBranchId != 1) {
            $query->where('users.branch_id', $sessionBranchId);
        }

        $query->select(
            'users.usr_id',
            'users.usr_uuid',
            'branches.branch_name',
            'users.usr_last_name',
            'users.usr_first_name',
            'users.usr_middle_name',
            'users.usr_email',
            'users.usr_mobile',
            'users.usr_birth_date',
            'users.usr_active',
        )
            ->groupBy(
                'users.usr_id',
                'users.usr_uuid',
                'branches.branch_name',
                'users.usr_last_name',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_email',
                'users.usr_mobile',
                'users.usr_birth_date',
                'users.usr_active'
            )
            ->orderBy('users.usr_last_name')
            ->orderBy('users.usr_first_name');

        // Search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_email', 'LIKE', "%$search%")
                    ->orWhere('users.usr_mobile', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $clients = $query->paginate(300);

        // Get all addresses for listed clients
        $clientIds = collect($clients->items())->pluck('usr_id');

        $addresses = DB::table('user_addresses')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->whereIn('user_addresses.usr_id', $clientIds)
            ->where('user_addresses.uadd_active', 1)
            ->select(
                'user_addresses.*',
                'addresses.add_name'
            )
            ->get()
            ->groupBy('usr_id');

        // Branch dropdown: branch_id = 1 ("ALL") can see every branch;
        // everyone else only sees their own branch
        if ($sessionBranchId == 1) {
            $branches = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_active', 1)
                ->get();
        } else {
            $branches = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_active', 1)
                ->where('branch_id', $sessionBranchId)
                ->get();
        }

        $servicePackages = DB::table('service_packages')->get();

        $servicePackageAreas = DB::table('service_package_areas')
            ->where('svcpa_active', 1)
            ->select('svcpa_id', 'svcpa_area', 'svcpa_cost', 'branch_id')
            ->get()
            ->groupBy('branch_id');

        $termiteAreas = DB::table('service_package_area_termites')
            ->where('svcpat_active', 1)
            ->select('svcpat_id', 'svcpat_sqm_details', 'svcpat_cost', 'branch_id')
            ->get()
            ->groupBy('branch_id');

        return view('service_orders.appointments.book', compact('clients', 'search', 'branches', 'addresses', 'servicePackages', 'servicePackageAreas', 'termiteAreas'));
    }

    public function clients_book(Request $request)
    {
        $request->validate([
            'usr_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'uadd_id' => 'required|integer',
            'svcp_id' => 'required|integer',
            'svca_client_date' => 'required|date',
            'svca_client_time' => 'required',
            'svc_problem_description' => 'nullable|string',
            'is_package' => 'nullable|boolean',
            'svcpa_id' => 'nullable|integer',
            'svcpat_id' => 'nullable|integer',
            'initiate_contract' => 'nullable|boolean',
            'svc_final_price' => ['required_if:initiate_contract,1', 'nullable', 'numeric', 'min:0'],
            'svcc_type' => ['required_if:initiate_contract,1', 'nullable', 'regex:/^(WEEKLY|MONTHLY(-[2-4]X)?|BIMONTHLY|QUARTERLY|SEMI-ANNUAL|ANNUAL)$/'],
            'svcc_total_years' => ['required_if:initiate_contract,1', 'nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'svcc_contract' => 'required_if:initiate_contract,1|nullable|file|mimes:jpg,jpeg,png,pdf,docx|max:10240',
            'extra_svcp_id' => 'nullable|array',
            'extra_svcp_id.*' => 'required|integer|not_in:8',
            'extra_svcpa_id' => 'nullable|array',
            'extra_svcpa_id.*' => 'required|integer',
        ], [
            'svcc_type.regex' => 'Invalid frequency selected.',
            'svcc_total_years.regex' => 'Duration must be in years.months format, e.g. 1.5 or 3.11.',
        ]);

        $isTermite = (int) $request->svcp_id === 8 ? 1 : 0;
        $svcpaId = $isTermite ? null : $request->svcpa_id;
        $svcpatId = $isTermite ? $request->svcpat_id : null;
        $isPackage = (!$isTermite && $request->boolean('is_package')) ? 1 : 0;
        $initiateContract = $request->boolean('initiate_contract');

        // Extras are not allowed when the main Problem Type is termite
        $extraPestIds = $isTermite ? [] : array_values(array_filter((array) $request->input('extra_svcp_id', [])));
        $extraAreaIds = $isTermite ? [] : array_values(array_filter((array) $request->input('extra_svcpa_id', [])));

        $insertExtraRows = function ($targetSvcId) use ($extraPestIds, $extraAreaIds) {
            // Extra Problem Types
            foreach ($extraPestIds as $extraPestId) {
                DB::table('service_order_pests')->insert([
                    'svcop_uuid' => generateuuid(),
                    'svc_id' => $targetSvcId,
                    'svcp_id' => $extraPestId,
                    'svcop_date_created' => Carbon::now(),
                    'svcop_created_by' => session('usr_id'),
                    'svcop_active' => 1,
                ]);
            }

            // Extra Locations (area)
            foreach ($extraAreaIds as $extraAreaId) {
                DB::table('service_orders')->insert([
                    'svco_uuid' => generateuuid(),
                    'svc_id' => $targetSvcId,
                    'svcpa_id' => $extraAreaId,
                    'svcpat_id' => null,
                    'svco_date_created' => Carbon::now(),
                    'svco_created_by' => session('usr_id'),
                    'svco_active' => 1,
                ]);
            }
        };

        if ($isTermite) {
            $initialPrice = DB::table('service_package_area_termites')
                ->where('svcpat_id', $svcpatId)
                ->value('svcpat_cost') ?? 0;
        } else {
            $initialPrice = DB::table('service_package_areas')
                ->where('svcpa_id', $svcpaId)
                ->value('svcpa_cost') ?? 0;
        }

        foreach ($extraAreaIds as $extraAreaId) {
            $initialPrice += DB::table('service_package_areas')
                ->where('svcpa_id', $extraAreaId)
                ->value('svcpa_cost') ?? 0;
        }

        // With a contract, the entered Final Price replaces the area price and
        // is saved to both svc_initial_price and svc_final_price.
        $basePrice = $initiateContract ? (float) $request->svc_final_price : $initialPrice;

        $priceFields = ['svc_initial_price' => $basePrice];
        if ($initiateContract) {
            $priceFields['svc_final_price'] = $basePrice;
        }

        // Distance & Location Price Calculation
        $address = DB::table('user_addresses')->where('uadd_id', $request->uadd_id)->first();
        $branch = DB::table('branches')->where('branch_id', $request->branch_id)->first();
        $locationFee = DB::table('service_package_area_locations')->where('branch_id', $request->branch_id)->first();

        $kmDistance = 0;
        $locationPrice = 0;

        if ($address && $branch && $locationFee) {

            // Haversine (straight-line fallback)
            $lat1 = deg2rad($branch->branch_latitude);
            $lon1 = deg2rad($branch->branch_longitude);
            $lat2 = deg2rad($address->uadd_latitude);
            $lon2 = deg2rad($address->uadd_longitude);

            $dlat = $lat2 - $lat1;
            $dlon = $lon2 - $lon1;

            $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
            $haversineKm = 6371 * 2 * asin(sqrt($a));

            // Try Mapbox driving distance first, fall back to Haversine if it fails
            try {
                $mapboxKm = MobileMapboxDistanceController::getDrivingDistanceKm(
                    $branch->branch_longitude,
                    $branch->branch_latitude,
                    $address->uadd_longitude,
                    $address->uadd_latitude
                );

                $rawKm = ($mapboxKm !== null && $mapboxKm > 0) ? $mapboxKm : $haversineKm;
            } catch (\Exception $e) {
                $rawKm = $haversineKm;
            }

            // Round: .1–.4 round down, .5–.9 round up
            $kmDistance = (fmod($rawKm, 1) >= 0.5) ? ceil($rawKm) : floor($rawKm);

            // Price: first 10km = flat rate, beyond = flat + extra km * succeeding cost
            if ($kmDistance <= 10) {
                $locationPrice = $locationFee->svcpal_first_cost;
            } else {
                $locationPrice = $locationFee->svcpal_first_cost
                    + (($kmDistance - 10) * $locationFee->svcpal_succeeding_cost);
            }
        }

        $svcId = DB::table('services')->insertGetId(array_merge([
            'svc_uuid' => generateuuid(),
            'branch_id' => $request->branch_id,
            'usr_id' => $request->usr_id,
            'svc_km_distance' => $kmDistance,
            'svc_is_package' => $isPackage,
            'svcpat_id' => $svcpatId,
            'svc_is_termite' => $isTermite,
            'svc_problem_description' => strtoupper($request->svc_problem_description),
            'svc_status' => 'REQUESTED',
            'svc_location_price' => $locationPrice,
            'svc_balance' => $basePrice + $locationPrice,
            'svc_payment_status' => 'NO PAYMENT',
            'svc_date_created' => Carbon::now(),
            'svc_created_by' => session('usr_id'),
            'svc_active' => 1,
        ], $priceFields));

        DB::table('services')
            ->where('svc_id', $svcId)
            ->update(['svc_sa_number' => $svcId]);

        DB::table('service_order_pests')->insert([
            'svcop_uuid' => generateuuid(),
            'svc_id' => $svcId,
            'svcp_id' => $request->svcp_id,
            'svcop_date_created' => Carbon::now(),
            'svcop_created_by' => session('usr_id'),
            'svcop_active' => 1,
        ]);

        DB::table('service_orders')->insert([
            'svco_uuid' => generateuuid(),
            'svc_id' => $svcId,
            'svcpa_id' => $svcpaId,
            'svcpat_id' => $svcpatId,
            'svco_date_created' => Carbon::now(),
            'svco_created_by' => session('usr_id'),
            'svco_active' => 1,
        ]);

        $insertExtraRows($svcId);

        DB::table('service_appointments')->insert([
            'svca_uuid' => generateuuid(),
            'svc_id' => $svcId,
            'uadd_id' => $request->uadd_id,
            'svca_client_date' => $request->svca_client_date,
            'svca_client_time' => $request->svca_client_time,
            'svca_status' => 'UNASSIGNED',
            'svca_date_created' => Carbon::now(),
            'svca_created_by' => session('usr_id'),
            'svca_active' => 1,
        ]);

        $serviceOrder = 'SA-' . str_pad($svcId, 6, '0', STR_PAD_LEFT);
        logUserActivity('Book Appointment', 'Booked appointment ' . $serviceOrder);

        // === INITIATE CONTRACT (optional) ===
        if ($initiateContract) {
            $durationParts = explode('.', $request->svcc_total_years);
            $contractYears = (int) $durationParts[0];
            $contractMonths = isset($durationParts[1]) ? (int) $durationParts[1] : 0;

            if ($contractMonths > 11) {
                session()->flash('errorMessage', 'Appointment booked, but contract was not created: months portion of duration cannot exceed 11 (e.g. use 2.0 instead of 1.12).');
                return redirect()->back();
            }

            if ($contractYears < 1 && $contractMonths < 1) {
                session()->flash('errorMessage', 'Appointment booked, but contract was not created: duration must be at least 1 month.');
                return redirect()->back();
            }

            $totalContractMonths = ($contractYears * 12) + $contractMonths;
            $totalYears = (float) ($contractYears . '.' . str_pad($contractMonths, 2, '0', STR_PAD_LEFT));

            $svccType = $request->svcc_type;
            $baseType = $svccType;
            $occurrence = 1;
            if (preg_match('/^MONTHLY-(\d)X$/', $svccType, $freqMatch)) {
                $baseType = 'MONTHLY';
                $occurrence = (int) $freqMatch[1];
            }

            $occurrencesPerYear = match ($baseType) {
                'WEEKLY' => 52,
                'MONTHLY' => 12 * $occurrence,
                'BIMONTHLY' => 6,
                'QUARTERLY' => 4,
                'SEMI-ANNUAL' => 2,
                'ANNUAL' => 1,
                default => 0,
            };

            $totalServices = (int) round(($occurrencesPerYear / 12) * $totalContractMonths);

            if ($totalServices < 1) {
                session()->flash('errorMessage', 'Appointment booked, but contract was not created: invalid contract frequency/duration combination.');
                return redirect()->back();
            }

            // Same upload pattern as finished_appointments_view_initiate_contract
            $file = $request->file('svcc_contract');
            $ext = strtolower($file->getClientOriginalExtension());
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $path = public_path('images/contracts/' . $fileName);

            if (in_array($ext, ['jpg', 'jpeg'])) {
                $source = imagecreatefromjpeg($file->getPathname());
                imagejpeg($source, $path, 75);
                imagedestroy($source);
            } elseif ($ext === 'png') {
                $source = imagecreatefrompng($file->getPathname());
                if ($source && imageistruecolor($source) === false) {
                    imagepalettetotruecolor($source);
                }
                if ($source) {
                    imagepng($source, $path, 7);
                    imagedestroy($source);
                }
            } else {
                // pdf / docx - no compression, just move
                $file->move(public_path('images/contracts'), $fileName);
            }

            $svccId = DB::table('service_contracts')->insertGetId([
                'svcc_uuid' => generateuuid(),
                'usr_id' => $request->usr_id,
                'svc_id' => $svcId,
                'svcc_type' => $svccType,
                'svcc_total_years' => $totalYears,
                'svcc_total_services' => $totalServices,
                'svcc_contract' => $fileName,
                'svcc_date_created' => Carbon::now(),
                'svcc_created_by' => session('usr_id'),
                'svcc_active' => 1,
            ], 'svcc_id');

            // Link the appointment already booked above to this contract
            DB::table('services')->where('svc_id', $svcId)->update(['svcc_id' => $svccId]);

            $baseDate = Carbon::parse($request->svca_client_date);
            $clientTime = $request->svca_client_time;
            $daysInterval = 365 / $occurrencesPerYear;

            // i starts at 1 because the first occurrence is $svcId, already created above.
            for ($i = 1; $i < $totalServices; $i++) {
                $nextDate = $baseDate->copy()->addDays((int) round($daysInterval * $i));

                $newSvcId = DB::table('services')->insertGetId(array_merge([
                    'svc_uuid' => generateuuid(),
                    'branch_id' => $request->branch_id,
                    'usr_id' => $request->usr_id,
                    'svc_km_distance' => $kmDistance,
                    'svc_is_package' => $isPackage,
                    'svcpat_id' => $svcpatId,
                    'svc_is_termite' => $isTermite,
                    'svc_problem_description' => strtoupper($request->svc_problem_description),
                    'svc_status' => 'REQUESTED',
                    'svc_location_price' => $locationPrice,
                    'svc_balance' => $basePrice + $locationPrice,
                    'svc_payment_status' => 'NO PAYMENT',
                    'svcc_id' => $svccId,
                    'svc_date_created' => Carbon::now(),
                    'svc_created_by' => session('usr_id'),
                    'svc_active' => 1,
                ], $priceFields));

                DB::table('services')
                    ->where('svc_id', $newSvcId)
                    ->update(['svc_sa_number' => $newSvcId]);

                DB::table('service_order_pests')->insert([
                    'svcop_uuid' => generateuuid(),
                    'svc_id' => $newSvcId,
                    'svcp_id' => $request->svcp_id,
                    'svcop_date_created' => Carbon::now(),
                    'svcop_created_by' => session('usr_id'),
                    'svcop_active' => 1,
                ]);

                DB::table('service_orders')->insert([
                    'svco_uuid' => generateuuid(),
                    'svc_id' => $newSvcId,
                    'svcpa_id' => $svcpaId,
                    'svcpat_id' => $svcpatId,
                    'svco_date_created' => Carbon::now(),
                    'svco_created_by' => session('usr_id'),
                    'svco_active' => 1,
                ]);

                $insertExtraRows($newSvcId);

                DB::table('service_appointments')->insert([
                    'svca_uuid' => generateuuid(),
                    'svc_id' => $newSvcId,
                    'uadd_id' => $request->uadd_id,
                    'svca_client_date' => $nextDate->format('Y-m-d'),
                    'svca_client_time' => $clientTime,
                    'svca_status' => 'UNASSIGNED',
                    'svca_date_created' => Carbon::now(),
                    'svca_created_by' => session('usr_id'),
                    'svca_active' => 1,
                ]);

                $newServiceOrder = 'SA-' . str_pad($newSvcId, 6, '0', STR_PAD_LEFT);
                logUserActivity('Book Appointment', 'Auto-generated contract appointment ' . $newServiceOrder);
            }

            logUserActivity('Book Appointment', 'Initiated ' . $svccType . ' contract (' . $totalYears . ' yr, ' . $totalServices . ' service/s) for ' . $serviceOrder);

            session()->flash('successMessage', 'Appointment successfully booked and contract initiated (' . $totalServices . ' appointment(s) generated).');
            return redirect()->back();
        }

        session()->flash('successMessage', 'Appointment successfully booked.');
        return redirect()->back();
    }
    // END BOOK APPOINTMENTS

    // START REQUESTED APPOINTMENTS
    public function requested_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionBranchId = session('branch_id');

        $query = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('users as creators', 'services.svc_created_by', '=', 'creators.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'service_appointments.svc_id', '=', 'services.svc_id')
            ->where('services.svc_active', 1)
            ->whereIn('services.svc_status', ['REQUESTED', 'CONFIRM ASSESSMENT']);

        // Branch filter (same logic as users_active)
        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_problem_description',
            'services.svc_status',
            'services.svc_payment_status',
            'services.svc_created_by',
            'services.usr_id',
            'service_appointments.svca_client_date',
            'users.usr_first_name',
            'users.usr_last_name',
            'users.usr_email',
            'users.usr_mobile',
            'branches.branch_name',
            'creators.usr_first_name as creator_first_name',
            'creators.usr_last_name as creator_last_name'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_email', 'LIKE', "%$search%")
                    ->orWhere('users.usr_mobile', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('services.svc_date_created', 'asc');

        $appointments = $query->paginate(300);

        return view('service_orders.appointments.requested.requested', compact('appointments', 'search'));
    }

    public function requested_appointments_view($svc_id)
    {
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->where('services.svc_id', $svc_id)
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_km_distance',
                'services.svc_property_type',
                'services.svc_is_package',
                'services.svcpat_id',
                'services.svc_is_termite',
                'services.svc_type_treatment',
                'services.svc_sqm_initial',
                'services.svc_sqm_final',
                'services.svc_with_device',
                'services.svc_device_count',
                'services.svc_problem_description',
                'services.svc_status',
                'services.svc_infestation',
                'services.svc_initial_price',
                'services.svc_location_price',
                'services.svc_device_price',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_payment_status',
                'services.svc_assessment_recommendation',
                'services.svcc_id',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email',
                'users.usr_mobile',
                'branches.branch_id',
                'branches.branch_name',
                'service_appointments.svca_client_date',
                'service_appointments.svca_client_time',
                'service_appointments.svca_approved_date',
                'service_appointments.svca_approved_time_from',
                'service_appointments.svca_approved_time_to',
                'service_appointments.svca_date_approved',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        // Pest Types for this service
        $pestTypes = DB::table('service_order_pests')
            ->leftJoin('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
            ->where('service_order_pests.svc_id', $svc_id)
            ->where('service_order_pests.svcop_active', 1)
            ->select(
                'service_order_pests.svcop_id',
                'service_packages.svcp_id',
                'service_packages.svcp_pest_type'
            )
            ->get();

        // Add Pest Type
        $existingPests = DB::table('service_order_pests')
            ->where('svc_id', $svc_id)
            ->where('svcop_active', 1)
            ->pluck('svcp_id')
            ->toArray();

        $servicePackages = DB::table('service_packages')
            ->where('svcp_id', '!=', 8)
            ->whereNotIn('svcp_id', $existingPests)
            ->get();

        // Service Orders with Areas (non-termite: svcpat_id IS NULL)
        $serviceAreas = DB::table('service_orders')
            ->leftJoin('service_package_areas', 'service_orders.svcpa_id', '=', 'service_package_areas.svcpa_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_areas.svcpa_id',
                'service_package_areas.svcpa_area',
                'service_package_areas.svcpa_cost'
            )
            ->get();

        // Add Service Area
        $existingAreas = DB::table('service_orders')
            ->where('svc_id', $svc_id)
            ->where('svco_active', 1)
            ->pluck('svcpa_id')
            ->toArray();

        $servicePackageAreas = DB::table('service_package_areas')
            ->whereNotIn('svcpa_id', $existingAreas)
            ->get();

        // Service Orders with Termite Areas (termite: svcpat_id IS NOT NULL)
        $termiteAreas = DB::table('service_orders')
            ->leftJoin('service_package_area_termites', 'service_orders.svcpat_id', '=', 'service_package_area_termites.svcpat_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNotNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_area_termites.svcpat_id',
                'service_package_area_termites.svcpat_sqm_details',
                'service_package_area_termites.svcpat_cost'
            )
            ->get();

        // Device cost for this branch (termite only)
        $deviceCost = DB::table('service_package_area_devices')
            ->where('branch_id', $display->branch_id)
            ->where('svcpad_active', 1)
            ->select('svcpad_id', 'svcpad_cost')
            ->first();

        $locationRate = DB::table('service_package_area_locations')
            ->where('branch_id', $display->branch_id)
            ->where('svcpal_active', 1)
            ->select('svcpal_first_cost', 'svcpal_succeeding_cost')
            ->first();

        $termiteAreaOptions = DB::table('service_package_area_termites')
            ->where('branch_id', $display->branch_id)
            ->where('svcpat_active', 1)
            ->select('svcpat_id', 'svcpat_sqm_details', 'svcpat_cost')
            ->get();

        // Client Appointment Images
        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        $contracts = DB::table('service_contracts')
            ->where('svc_id', $svc_id)
            ->where('svcc_active', 1)
            ->orderByDesc('svcc_date_created')
            ->get();

        $hasOwnContract = $contracts->isNotEmpty();

        if (!empty($display->svcc_id) && !$contracts->pluck('svcc_id')->contains($display->svcc_id)) {
            $linkedContract = DB::table('service_contracts')
                ->where('svcc_id', $display->svcc_id)
                ->where('svcc_active', 1)
                ->first();

            if ($linkedContract) {
                $contracts->push($linkedContract);
            }
        }

        foreach ($contracts as $contract) {
            $contract->generated_services = DB::table('services')
                ->where('svcc_id', $contract->svcc_id)
                ->where('svc_active', 1)
                ->orderBy('svc_id')
                ->select('svc_id', 'svc_sa_number', 'svc_status')
                ->get();
        }

        $chemicals = DB::table('chemicals')
            ->where('chem_active', 1)
            ->select('chem_id', 'chem_name')
            ->get();

        $serviceChemicals = DB::table('service_chemicals')
            ->where('svc_id', $svc_id)
            ->where('svcch_active', 1)
            ->select('svcch_id', 'chem_id', 'svcch_expected_quantity', 'svcch_unit')
            ->get();

        return view('service_orders.appointments.requested.view_requested', compact(
            'display',
            'pestTypes',
            'servicePackages',
            'serviceAreas',
            'servicePackageAreas',
            'termiteAreas',
            'deviceCost',
            'locationRate',
            'termiteAreaOptions',
            'appointmentImages',
            'contracts',
            'hasOwnContract',
            'chemicals',
            'serviceChemicals'
        ));
    }

    public function requested_appointments_view_add_pest(Request $request)
    {
        $request->validate([
            'svc_id' => 'required',
            'svcp_id' => 'required'
        ]);

        $svc_id = $request->svc_id;
        $svcp_id = $request->svcp_id;

        // check if already exists active
        $exists = DB::table('service_order_pests')
            ->where('svc_id', $svc_id)
            ->where('svcp_id', $svcp_id)
            ->where('svcop_active', 1)
            ->first();

        if ($exists) {
            alert()->error('Pest type already added.');
            return redirect()->back();
        }

        DB::table('service_order_pests')->insert([
            'svcop_uuid' => generateuuid(),
            'svc_id' => $svc_id,
            'svcp_id' => $svcp_id,
            'svcop_date_created' => Carbon::now(),
            'svcop_created_by' => session('usr_id'),
            'svcop_active' => 1
        ]);

        $pest = DB::table('service_packages')
            ->where('svcp_id', $svcp_id)
            ->first();

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Appointments',
            'Added pest type ' . ($pest->svcp_pest_type ?? '') . ' to ' . $serviceOrder
        );

        session()->flash('successMessage', 'Pest type successfully added.');
        return redirect()->back();
    }

    public function requested_appointments_view_delete_pest(Request $request, $svcop_id)
    {
        $pest = DB::table('service_order_pests')
            ->leftJoin('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
            ->where('service_order_pests.svcop_id', $svcop_id)
            ->select(
                'service_order_pests.svcop_id',
                'service_order_pests.svc_id',
                'service_packages.svcp_pest_type'
            )
            ->first();

        if (!$pest) {
            alert()->error('Pest type not found.');
            return redirect()->back();
        }

        DB::table('service_order_pests')
            ->where('svcop_id', $svcop_id)
            ->update([
                'svcop_date_modified' => Carbon::now(),
                'svcop_modified_by' => session('usr_id'),
                'svcop_active' => 0
            ]);

        $serviceOrder = 'SA-' . str_pad($pest->svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Appointments',
            'Deleted pest type ' . $pest->svcp_pest_type . ' from ' . $serviceOrder
        );

        session()->flash('successMessage', 'Appointment service pest type has been deleted.');
        return redirect()->back();
    }

    public function requested_appointments_view_add_service(Request $request)
    {
        $request->validate([
            'svc_id' => 'required',
            'svcpa_id' => 'required'
        ]);

        $svc_id = $request->svc_id;
        $svcpa_id = $request->svcpa_id;

        // check if already added
        $exists = DB::table('service_orders')
            ->where('svc_id', $svc_id)
            ->where('svcpa_id', $svcpa_id)
            ->where('svco_active', 1)
            ->first();

        if ($exists) {
            alert()->error('Service area already added.');
            return redirect()->back();
        }

        $area = DB::table('service_package_areas')
            ->where('svcpa_id', $svcpa_id)
            ->first();

        if (!$area) {
            alert()->error('Service area not found.');
            return redirect()->back();
        }

        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if (!$service) {
            alert()->error('Service not found.');
            return redirect()->back();
        }

        $newInitial = $service->svc_initial_price + $area->svcpa_cost;
        $newBalance = $service->svc_balance + $area->svcpa_cost;

        DB::beginTransaction();

        // insert service order
        DB::table('service_orders')->insert([
            'svco_uuid' => generateuuid(),
            'svc_id' => $svc_id,
            'svcpa_id' => $svcpa_id,
            'svco_date_created' => Carbon::now(),
            'svco_created_by' => session('usr_id'),
            'svco_active' => 1
        ]);

        // update service pricing
        DB::table('services')
            ->where('svc_id', $svc_id)
            ->update([
                'svc_initial_price' => $newInitial,
                'svc_balance' => $newBalance
            ]);

        DB::commit();

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Appointments',
            'Added service area "' . $area->svcpa_area . '" to ' . $serviceOrder .
            '. Added ' . number_format($area->svcpa_cost, 2) .
            '. New Initial: ' . number_format($newInitial, 2) .
            ', New Balance: ' . number_format($newBalance, 2)
        );

        session()->flash('successMessage', 'Service area successfully added.');
        return redirect()->back();
    }

    public function requested_appointments_view_delete_service(Request $request, $svcpa_id)
    {
        $service = DB::table('service_orders')
            ->leftJoin('service_package_areas', 'service_orders.svcpa_id', '=', 'service_package_areas.svcpa_id')
            ->leftJoin('services', 'service_orders.svc_id', '=', 'services.svc_id')
            ->where('service_orders.svcpa_id', $svcpa_id)
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_orders.svc_id',
                'service_orders.svcpa_id',
                'service_package_areas.svcpa_area',
                'service_package_areas.svcpa_cost',
                'services.svc_initial_price',
                'services.svc_balance'
            )
            ->first();

        if (!$service) {
            alert()->error('Service order not found.');
            return redirect()->back();
        }

        $newInitialPrice = $service->svc_initial_price - $service->svcpa_cost;
        $newBalance = $service->svc_balance - $service->svcpa_cost;

        DB::beginTransaction();

        try {
            // Soft delete service order
            DB::table('service_orders')
                ->where('svcpa_id', $svcpa_id)
                ->update([
                    'svco_date_modified' => Carbon::now(),
                    'svco_modified_by' => session('usr_id'),
                    'svco_active' => 0
                ]);

            // Update service prices
            DB::table('services')
                ->where('svc_id', $service->svc_id)
                ->update([
                    'svc_initial_price' => $newInitialPrice,
                    'svc_balance' => $newBalance
                ]);

            // If no active service orders (package areas) remain for this
            // service, the booking is effectively empty — cascade-deactivate
            // its related records (mirrors the tables populated together in
            // finished_appointments_view_initiate_contract: service_order_pests,
            // service_chemicals, service_appointments).
            $hasActiveOrdersLeft = DB::table('service_orders')
                ->where('svc_id', $service->svc_id)
                ->where('svco_active', 1)
                ->exists();

            if (!$hasActiveOrdersLeft) {
                DB::table('service_order_pests')
                    ->where('svc_id', $service->svc_id)
                    ->where('svcop_active', 1)
                    ->update([
                        'svcop_date_modified' => Carbon::now(),
                        'svcop_modified_by' => session('usr_id'),
                        'svcop_active' => 0
                    ]);

                DB::table('service_chemicals')
                    ->where('svc_id', $service->svc_id)
                    ->where('svcch_active', 1)
                    ->update([
                        'svcch_date_modified' => Carbon::now(),
                        'svcch_modified_by' => session('usr_id'),
                        'svcch_active' => 0
                    ]);

                DB::table('service_appointments')
                    ->where('svc_id', $service->svc_id)
                    ->where('svca_active', 1)
                    ->update([
                        'svca_date_modified' => Carbon::now(),
                        'svca_modified_by' => session('usr_id'),
                        'svca_active' => 0
                    ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            alert()->error('Something went wrong while deleting the service order.');
            return redirect()->back();
        }

        $serviceOrder = 'SA-' . str_pad($service->svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Appointments',
            'Deleted service area "' . $service->svcpa_area .
            '" from ' . $serviceOrder .
            '. Deducted ' . number_format($service->svcpa_cost, 2) .
            ' from Initial Price and Balance. ' .
            'New Initial Price: ' . number_format($newInitialPrice, 2) .
            ', New Balance: ' . number_format($newBalance, 2) .
            (!$hasActiveOrdersLeft ? '. No remaining service orders — related pests, chemicals, and appointment also deactivated.' : '')
        );

        session()->flash(
            'successMessage',
            'Appointment service order has been deleted and prices updated.'
        );

        return redirect()->back();
    }

    public function requested_appointments_view_assess(Request $request)
    {
        $request->validate([
            'svc_id' => 'required',
            'svc_sa_number' => 'required',
            'svc_infestation' => 'required',
            'svc_location_price' => 'required|numeric',
            'svc_final_price' => 'required|numeric',
            'svca_approved_date' => 'required',
            'svca_approved_time_from' => 'required',
            'svca_approved_time_to' => 'required',
            'chem_id' => 'required|array|min:1',
            'chem_id.*' => 'required',
            'svcch_expected_quantity' => 'required|array',
            'svcch_expected_quantity.*' => 'required|numeric',
            'svcch_unit' => 'required|array',
            'svcch_unit.*' => 'required',
        ]);

        $svc_id = $request->svc_id;

        if (
            DB::table('services')
                ->where('svc_sa_number', $request->svc_sa_number)
                ->where('svc_id', '!=', $svc_id)
                ->exists()
        ) {

            session()->flash('errorMessage', 'SA Number already exists.');
            return redirect()->back();
        }

        $svc_id = $request->svc_id;
        $isTermite = $request->svc_is_termite;
        $isPackage = $request->svc_is_package;
        $servicePrice = $request->svc_location_price;
        $finalPrice = $request->svc_final_price;

        // Fetch existing service record
        $service = DB::table('services')->where('svc_id', $svc_id)->first();

        // Shared fields across both paths
        $sharedFields = [
            'svc_sa_number' => $request->svc_sa_number,
            'svc_property_type' => $request->svc_property_type,
            'svc_km_distance' => $request->svc_km_distance,
            'svc_fixed_price' => $request->svc_fixed_price,
            'svc_assessment_recommendation' => strtoupper($request->svc_assessment_recommendation),
        ];

        if ($isTermite == 1) {
            // TERMITE PATH
            $treatmentType = $request->svc_type_treatment;
            $withDevice = ($treatmentType === 'HYBRID TREATMENT') ? 1 : 0;
            $deviceCount = $withDevice ? (int) $request->svc_device_count : null;
            $sqmInitial = $request->svc_sqm_initial ?? $service->svc_sqm_initial;

            // Recompute device price server-side (mirrors JS: count × unit cost)
            $deviceCostRow = DB::table('service_package_area_devices')
                ->where('branch_id', $service->branch_id)
                ->where('svcpad_active', 1)
                ->first();

            $devicePrice = ($withDevice && $deviceCount && $deviceCostRow)
                ? $deviceCount * $deviceCostRow->svcpad_cost
                : 0;

            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update(array_merge($sharedFields, [
                    'svc_is_termite' => 1,
                    'svcpat_id' => $request->svcpat_id,
                    'svc_type_treatment' => $treatmentType,
                    'svc_with_device' => $withDevice,
                    'svc_device_count' => $deviceCount,
                    'svc_device_price' => $devicePrice,
                    'svc_sqm_final' => $sqmInitial,
                    'svc_status' => 'CONFIRM ASSESSMENT',
                    'svc_infestation' => $request->svc_infestation,
                    'svc_location_price' => $servicePrice,
                    'svc_final_price' => $finalPrice,
                    'svc_balance' => $finalPrice,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]));

            DB::table('service_orders')
                ->where('svc_id', $svc_id)
                ->update([
                    'svcpat_id' => $request->svcpat_id,
                    'svco_date_modified' => Carbon::now(),
                    'svco_modified_by' => session('usr_id'),
                ]);

        } else {
            // NON-TERMITE PATH
            $sqmInitial = $service->svc_sqm_initial;
            if ($isPackage == 0 && is_null($service->svc_sqm_initial)) {
                $sqmInitial = null;
            } elseif ($isPackage == 1) {
                $sqmInitial = $request->svc_sqm_initial ?? $service->svc_sqm_initial;
            }

            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update(array_merge($sharedFields, [
                    'svc_is_package' => $isPackage,
                    'svc_sqm_final' => $isPackage == 1 ? $sqmInitial : null,
                    'svc_status' => 'CONFIRM ASSESSMENT',
                    'svc_infestation' => $request->svc_infestation,
                    'svc_location_price' => $servicePrice,
                    'svc_initial_price' => $request->svc_initial_price ?? $service->svc_initial_price,
                    'svc_final_price' => $finalPrice,
                    'svc_balance' => $finalPrice,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]));
        }

        // APPOINTMENT (shared)
        DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->update([
                'svca_approved_date' => $request->svca_approved_date,
                'svca_approved_time_from' => $request->svca_approved_time_from,
                'svca_approved_time_to' => $request->svca_approved_time_to,
                'svca_date_approved' => Carbon::now(),
                'svca_approved_by' => session('usr_id'),
                'svca_date_modified' => Carbon::now(),
                'svca_modified_by' => session('usr_id'),
            ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        DB::table('service_chemicals')
            ->where('svc_id', $svc_id)
            ->update([
                'svcch_active' => 0,
                'svcch_date_modified' => Carbon::now(),
                'svcch_modified_by' => session('usr_id'),
            ]);

        foreach ($request->chem_id as $i => $chemId) {
            if (empty($chemId))
                continue;

            DB::table('service_chemicals')->insert([
                'svcch_uuid' => generateuuid(),
                'svc_id' => $svc_id,
                'chem_id' => $chemId,
                'svcch_expected_quantity' => $request->svcch_expected_quantity[$i],
                'svcch_unit' => $request->svcch_unit[$i],
                'svcch_date_created' => Carbon::now(),
                'svcch_created_by' => session('usr_id'),
                'svcch_active' => 1,
            ]);
        }

        logUserActivity('Manage Appointments', 'Assessed appointment ' . $serviceOrder);

        session()->flash('successMessage', 'Appointment successfully assessed.');
        return redirect()->back();
    }

    public function requested_appointments_view_assess_confirmation(Request $request)
    {
        $request->validate([
            'svc_id' => 'required',
            'svc_sa_number' => 'required',
            'svc_infestation' => 'required',
            'svc_location_price' => 'required|numeric',
            'svc_final_price' => 'required|numeric',
            'svca_approved_date' => 'required',
            'svca_approved_time_from' => 'required',
            'svca_approved_time_to' => 'required',
            'chem_id' => 'required|array|min:1',
            'chem_id.*' => 'required',
            'svcch_expected_quantity' => 'required|array',
            'svcch_expected_quantity.*' => 'required|numeric',
            'svcch_unit' => 'required|array',
            'svcch_unit.*' => 'required',
        ]);

        $svc_id = $request->svc_id;

        if (
            DB::table('services')
                ->where('svc_sa_number', $request->svc_sa_number)
                ->where('svc_id', '!=', $svc_id)
                ->exists()
        ) {

            session()->flash('errorMessage', 'SA Number already exists.');
            return redirect()->back();
        }

        $svc_id = $request->svc_id;
        $isTermite = $request->svc_is_termite;
        $isPackage = $request->svc_is_package;
        $servicePrice = $request->svc_location_price;
        $finalPrice = $request->svc_final_price;

        $service = DB::table('services')->where('svc_id', $svc_id)->first();

        // ← NEW: shared fields (mirrors assess method)
        $sharedFields = [
            'svc_sa_number' => $request->svc_sa_number,
            'svc_property_type' => $request->svc_property_type,
            'svc_km_distance' => $request->svc_km_distance,
            'svc_fixed_price' => $request->svc_fixed_price,
            'svc_assessment_recommendation' => strtoupper($request->svc_assessment_recommendation),
        ];

        if ($isTermite == 1) {
            $treatmentType = $request->svc_type_treatment;
            $withDevice = ($treatmentType === 'HYBRID TREATMENT') ? 1 : 0;
            $deviceCount = $withDevice ? (int) $request->svc_device_count : null;
            $sqmFinal = $request->svc_sqm_final ?? $service->svc_sqm_final;

            $deviceCostRow = DB::table('service_package_area_devices')
                ->where('branch_id', $service->branch_id)
                ->where('svcpad_active', 1)
                ->first();

            $devicePrice = ($withDevice && $deviceCount && $deviceCostRow)
                ? $deviceCount * $deviceCostRow->svcpad_cost
                : 0;

            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update(array_merge($sharedFields, [   // ← merged
                    'svc_is_termite' => 1,
                    'svcpat_id' => $request->svcpat_id,
                    'svc_type_treatment' => $treatmentType,
                    'svc_with_device' => $withDevice,
                    'svc_device_count' => $deviceCount,
                    'svc_device_price' => $devicePrice,
                    'svc_sqm_final' => $sqmFinal,
                    'svc_status' => 'ASSESSED',
                    'svc_infestation' => $request->svc_infestation,
                    'svc_location_price' => $servicePrice,
                    'svc_final_price' => $finalPrice,
                    'svc_balance' => $finalPrice,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]));

            DB::table('service_orders')
                ->where('svc_id', $svc_id)
                ->update([
                    'svcpat_id' => $request->svcpat_id,
                    'svco_date_modified' => Carbon::now(),
                    'svco_modified_by' => session('usr_id'),
                ]);

        } else {
            $sqmFinal = $service->svc_sqm_initial;
            if ($isPackage == 0 && is_null($service->svc_sqm_initial)) {
                $sqmFinal = null;
            } elseif ($isPackage == 1) {
                $sqmFinal = $request->svc_sqm_initial ?? $service->svc_sqm_initial;
            }

            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update(array_merge($sharedFields, [   // ← merged
                    'svc_is_package' => $isPackage,
                    'svc_sqm_final' => $isPackage == 1 ? $sqmFinal : null,
                    'svc_status' => 'ASSESSED',
                    'svc_infestation' => $request->svc_infestation,
                    'svc_location_price' => $servicePrice,
                    'svc_initial_price' => $request->svc_initial_price ?? $service->svc_initial_price,
                    'svc_final_price' => $finalPrice,
                    'svc_balance' => $finalPrice,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]));
        }

        DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->update([
                'svca_approved_date' => $request->svca_approved_date,
                'svca_approved_time_from' => $request->svca_approved_time_from,
                'svca_approved_time_to' => $request->svca_approved_time_to,
                'svca_date_approved' => Carbon::now(),
                'svca_approved_by' => session('usr_id'),
                'svca_date_modified' => Carbon::now(),
                'svca_modified_by' => session('usr_id'),
            ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        DB::table('service_chemicals')
            ->where('svc_id', $svc_id)
            ->update([
                'svcch_active' => 0,
                'svcch_date_modified' => Carbon::now(),
                'svcch_modified_by' => session('usr_id'),
            ]);

        foreach ($request->chem_id as $i => $chemId) {
            if (empty($chemId))
                continue;

            DB::table('service_chemicals')->insert([
                'svcch_uuid' => generateuuid(),
                'svc_id' => $svc_id,
                'chem_id' => $chemId,
                'svcch_expected_quantity' => $request->svcch_expected_quantity[$i],
                'svcch_unit' => $request->svcch_unit[$i],
                'svcch_date_created' => Carbon::now(),
                'svcch_created_by' => session('usr_id'),
                'svcch_active' => 1,
            ]);
        }

        logUserActivity('Manage Appointments', 'Confirmed Assessment ' . $serviceOrder);

        session()->flash('successMessage', 'Appointment successfully assessed.');
        return redirect()->action(
            [AppointmentController::class, 'assessed_appointments_view'],
            ['svc_id' => $svc_id]
        );
    }
    // END REQUESTED APPOINTMENTS

    // START SCHEDULING APPOINTMENTS
    public function scheduling_appointments(Request $request)
    {
        $search = $request->search ?? '';

        // ALWAYS TIMELINE VIEW
        $view = 'timeline';

        $sessionBranchId = session('branch_id');

        // Base query: only ACTIVE schedules are joined (check lives in the join so it stays a left join)
        $query = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', function ($join) {
                $join->on('service_appointments.svc_id', '=', 'services.svc_id');
                // ->where('service_appointments.svca_active', '=', 1); // uncomment if this column exists
            })
            ->leftJoin('service_appointment_schedules', function ($join) {
                $join->on('service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
                    ->where('service_appointment_schedules.svcas_active', '=', 1);
            })
            ->where('services.svc_active', 1)
            ->where('services.svc_status', 'SCHEDULED');

        // Branch filter
        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_status',
            'services.svc_payment_status',
            'services.svc_date_created',

            'users.usr_first_name',
            'users.usr_last_name',
            'users.usr_email',
            'users.usr_mobile',

            'branches.branch_name',

            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',

            'service_appointment_schedules.svcas_id'
        );

        // Search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_email', 'LIKE', "%$search%")
                    ->orWhere('users.usr_mobile', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_appointments.svca_approved_date', 'asc')
            ->orderBy('service_appointments.svca_approved_time_from', 'asc');

        // Get all available dates
        $availableDates = (clone $query)
            ->whereNotNull('service_appointments.svca_approved_date')
            ->orderBy('service_appointments.svca_approved_date', 'asc')
            ->pluck('service_appointments.svca_approved_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->toArray();

        // Default to OLDEST approved date
        $defaultDate = !empty($availableDates)
            ? $availableDates[0]
            : now()->toDateString();

        // Selected timeline date
        $tlDate = $request->tl_date
            ? Carbon::parse($request->tl_date)->toDateString()
            : $defaultDate;

        // Previous / Next Date
        $dateIndex = array_search($tlDate, $availableDates);

        $prevDate = ($dateIndex !== false && $dateIndex > 0)
            ? $availableDates[$dateIndex - 1]
            : null;

        $nextDate = ($dateIndex !== false && $dateIndex < count($availableDates) - 1)
            ? $availableDates[$dateIndex + 1]
            : null;

        // Timeline rows
        // Safety net: newest schedule first, then keep only one row per appointment
        $tlRows = (clone $query)
            ->whereDate('service_appointments.svca_approved_date', $tlDate)
            ->orderByDesc('service_appointment_schedules.svcas_id')
            ->get()
            ->unique('svc_id')
            ->values();

        // Pull all ACTIVE technician assignments for the svcas_ids on this date
        $svcasIds = $tlRows->pluck('svcas_id')->filter()->unique()->values()->toArray();

        $techAssignsBySvcasId = [];
        if (!empty($svcasIds)) {
            $assignRows = DB::table('service_appointment_schedule_assigns')
                ->leftJoin('users as technicians', 'technicians.usr_id', '=', 'service_appointment_schedule_assigns.svcasa_assigned_to')
                ->whereIn('service_appointment_schedule_assigns.svcas_id', $svcasIds)
                ->where('service_appointment_schedule_assigns.svcasa_active', 1)
                // ->where('technicians.usr_active', 1) // uncomment to hide soft-deleted technician accounts
                ->select(
                    'service_appointment_schedule_assigns.svcas_id',
                    'technicians.usr_first_name as tech_first_name',
                    'technicians.usr_last_name as tech_last_name'
                )
                ->get();

            foreach ($assignRows as $assignRow) {
                $techAssignsBySvcasId[$assignRow->svcas_id][] = $assignRow;
            }
        }

        // Group by technician (fan out: a multi-tech appointment shows under each assigned tech's row)
        $timelineByTech = [];

        foreach ($tlRows as $row) {
            $assignedTechs = $techAssignsBySvcasId[$row->svcas_id] ?? [];

            if (empty($assignedTechs)) {
                $timelineByTech['Unassigned'][] = $row;
                continue;
            }

            foreach ($assignedTechs as $assignedTech) {
                $techKey = $assignedTech->tech_last_name
                    ? trim($assignedTech->tech_last_name . ', ' . $assignedTech->tech_first_name)
                    : 'Unassigned';

                $timelineByTech[$techKey][] = $row;
            }
        }

        ksort($timelineByTech);

        // ASSESSED appointments (for the same timeline date)
        $assessedQuery = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', function ($join) {
                $join->on('service_appointments.svc_id', '=', 'services.svc_id');
                // ->where('service_appointments.svca_active', '=', 1); // uncomment if this column exists
            })
            ->where('services.svc_active', 1)
            ->where('services.svc_status', 'ASSESSED');

        if ($sessionBranchId != 1) {
            $assessedQuery->where('services.branch_id', $sessionBranchId);
        }

        $assessedQuery->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_status',
            'services.svc_payment_status',
            'users.usr_first_name',
            'users.usr_last_name',
            'users.usr_email',
            'users.usr_mobile',
            'branches.branch_name',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to'
        );

        // Dates that have ASSESSED appointments (for the ! badge in flatpickr)
        $assessedDates = (clone $assessedQuery)
            ->whereNotNull('service_appointments.svca_approved_date')
            ->pluck('service_appointments.svca_approved_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->toArray();

        // Assessed rows for the currently selected date
        $assessedRows = (clone $assessedQuery)
            ->whereDate('service_appointments.svca_approved_date', $tlDate)
            ->orderBy('service_appointments.svca_approved_time_from', 'asc')
            ->get();

        // Merged clickable dates (scheduled + assessed)
        $clickableDates = array_values(array_unique(array_merge($availableDates, $assessedDates)));
        sort($clickableDates);

        return view(
            'service_orders.appointments.scheduling',
            compact(
                'search',
                'view',
                'tlDate',
                'tlRows',
                'timelineByTech',
                'availableDates',
                'prevDate',
                'nextDate',
                'assessedRows',
                'assessedDates',
                'clickableDates'
            )
        );
    }
    // END SCHEDULING APPOINTMENTS

    // START ASSESSED APPOINTMENTS
    public function assessed_appointments_view($svc_id)
    {
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->leftJoin('users as approver', 'service_appointments.svca_approved_by', '=', 'approver.usr_id')
            ->where('services.svc_id', $svc_id)
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_km_distance',
                'services.svc_property_type',
                'services.svc_is_package',
                'services.svcpat_id',
                'services.svc_is_termite',
                'services.svc_type_treatment',
                'services.svc_sqm_initial',
                'services.svc_sqm_final',
                'services.svc_with_device',
                'services.svc_device_count',
                'services.svc_problem_description',
                'services.svc_status',
                'services.svc_infestation',
                'services.svc_initial_price',
                'services.svc_location_price',
                'services.svc_device_price',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_payment_status',
                'services.svc_assessment_recommendation',
                'services.svcc_id',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email',
                'users.usr_mobile',
                'branches.branch_id',
                'branches.branch_name',
                'service_appointments.svca_client_date',
                'service_appointments.svca_client_time',
                'service_appointments.svca_approved_date',
                'service_appointments.svca_approved_time_from',
                'service_appointments.svca_approved_time_to',
                'service_appointments.svca_date_approved',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        // Pest Types for this service
        $pestTypes = DB::table('service_order_pests')
            ->leftJoin('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
            ->where('service_order_pests.svc_id', $svc_id)
            ->where('service_order_pests.svcop_active', 1)
            ->select(
                'service_order_pests.svcop_id',
                'service_packages.svcp_id',
                'service_packages.svcp_pest_type'
            )
            ->get();

        // Service Orders with Areas (non-termite: svcpat_id IS NULL)
        $serviceAreas = DB::table('service_orders')
            ->leftJoin('service_package_areas', 'service_orders.svcpa_id', '=', 'service_package_areas.svcpa_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_areas.svcpa_id',
                'service_package_areas.svcpa_area',
                'service_package_areas.svcpa_cost'
            )
            ->get();

        // Service Orders with Termite Areas (termite: svcpat_id IS NOT NULL)
        $termiteAreas = DB::table('service_orders')
            ->leftJoin('service_package_area_termites', 'service_orders.svcpat_id', '=', 'service_package_area_termites.svcpat_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNotNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_area_termites.svcpat_id',
                'service_package_area_termites.svcpat_sqm_details',
                'service_package_area_termites.svcpat_cost'
            )
            ->get();

        // Client Appointment Images
        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        $approvedDate = null;
        $approvedTimeFrom = null;
        $approvedTimeTo = null;

        $appointment = DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->first();

        if ($appointment) {
            $approvedDate = $appointment->svca_approved_date;
            $approvedTimeFrom = $appointment->svca_approved_time_from;
            $approvedTimeTo = $appointment->svca_approved_time_to;
        }

        // Day-of-week name from approved date (MONDAY, TUESDAY, etc.)
        $dayName = $approvedDate
            ? strtoupper(Carbon::parse($approvedDate)->format('l'))
            : null;

        // Technicians
        $technicians = DB::table('users')
            ->where('utyp_id', 2)
            ->where('usr_active', 1)
            ->where('branch_id', $display->branch_id)
            ->orderBy('usr_last_name', 'asc')
            ->select('usr_id', 'usr_first_name', 'usr_last_name')
            ->get()
            ->map(function ($tech) use ($dayName, $approvedDate, $approvedTimeFrom, $approvedTimeTo) {
                // Check rest day
                $isRestDay = false;
                if ($dayName) {
                    $avail = DB::table('user_availabilities')
                        ->where('usr_id', $tech->usr_id)
                        ->where('uavail_name', $dayName)
                        ->first();
                    $isRestDay = !$avail || $avail->uavail_active == 0;
                }

                // Check existing assignments that overlap
                $isBusy = false;
                if ($approvedDate && $approvedTimeFrom && $approvedTimeTo) {
                    $conflict = DB::table('service_appointment_schedule_assigns')
                        ->join('service_appointment_schedules', 'service_appointment_schedules.svcas_id', '=', 'service_appointment_schedule_assigns.svcas_id')
                        ->join('service_appointments', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
                        ->where('service_appointment_schedule_assigns.svcasa_assigned_to', $tech->usr_id)
                        ->where('service_appointment_schedule_assigns.svcasa_active', 1)
                        ->where('service_appointment_schedules.svcas_active', 1)
                        ->where('service_appointments.svca_approved_date', $approvedDate)
                        ->where('service_appointments.svca_approved_time_from', '<', $approvedTimeTo)
                        ->where('service_appointments.svca_approved_time_to', '>', $approvedTimeFrom)
                        ->first();
                    $isBusy = (bool) $conflict;
                }

                $tech->is_rest_day = $isRestDay;
                $tech->is_busy = $isBusy;
                return $tech;
            });

        // Existing schedules for the timeline (all techs, same date)
        $daySchedules = [];
        if ($approvedDate) {
            $rows = DB::table('service_appointment_schedule_assigns')
                ->join('service_appointment_schedules', 'service_appointment_schedules.svcas_id', '=', 'service_appointment_schedule_assigns.svcas_id')
                ->join('service_appointments', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
                ->join('services', 'services.svc_id', '=', 'service_appointments.svc_id')
                ->join('users as clients', 'clients.usr_id', '=', 'services.usr_id')
                ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
                ->leftJoin('users as assigner', 'assigner.usr_id', '=', 'service_appointment_schedules.svcas_assigned_by')
                ->where('service_appointment_schedule_assigns.svcasa_active', 1)
                ->where('service_appointment_schedules.svcas_active', 1)
                ->where('service_appointments.svca_approved_date', $approvedDate)
                ->select(
                    'service_appointment_schedule_assigns.svcasa_assigned_to as svcas_assigned_to',
                    'service_appointment_schedules.svcas_date_assigned',
                    'service_appointment_schedules.svcas_assigned_by',
                    'assigner.usr_first_name as assigner_first_name',
                    'assigner.usr_last_name as assigner_last_name',
                    'service_appointments.svca_approved_time_from',
                    'service_appointments.svca_approved_time_to',
                    'clients.usr_first_name',
                    'clients.usr_last_name',
                    'clients.usr_email',
                    'clients.usr_mobile',
                    'user_addresses.uadd_street',
                    'user_addresses.uadd_barangay',
                    'user_addresses.uadd_city',
                    'user_addresses.uadd_province',
                    'user_addresses.uadd_region',
                    'services.svc_km_distance'
                )
                ->get();

            foreach ($rows as $row) {
                $daySchedules[$row->svcas_assigned_to][] = $row;
            }
        }

        $allTechs = $technicians->map(function ($t) {
            return [
                'id' => $t->usr_id,
                'label' => $t->usr_last_name . ', ' . substr($t->usr_first_name, 0, 1) . '.',
                'is_rest' => $t->is_rest_day,
                'is_busy' => $t->is_busy,
            ];
        })->values();

        $mappingImages = DB::table('service_mapping_images')
            ->where('svc_id', $svc_id)
            ->where('svcmp_active', 1)
            ->orderByDesc('svcmp_date_created')
            ->get();

        $contracts = DB::table('service_contracts')
            ->where('svc_id', $svc_id)
            ->where('svcc_active', 1)
            ->orderByDesc('svcc_date_created')
            ->get();

        $hasOwnContract = $contracts->isNotEmpty();

        if (!empty($display->svcc_id) && !$contracts->pluck('svcc_id')->contains($display->svcc_id)) {
            $linkedContract = DB::table('service_contracts')
                ->where('svcc_id', $display->svcc_id)
                ->where('svcc_active', 1)
                ->first();

            if ($linkedContract) {
                $contracts->push($linkedContract);
            }
        }

        foreach ($contracts as $contract) {
            $contract->generated_services = DB::table('services')
                ->where('svcc_id', $contract->svcc_id)
                ->where('svc_active', 1)
                ->orderBy('svc_id')
                ->select('svc_id', 'svc_sa_number', 'svc_status')
                ->get();
        }

        // Chemicals used for this service
        $serviceChemicals = DB::table('service_chemicals')
            ->join('chemicals', 'service_chemicals.chem_id', '=', 'chemicals.chem_id')
            ->where('service_chemicals.svc_id', $svc_id)
            ->where('service_chemicals.svcch_active', 1)
            ->select(
                'service_chemicals.svcch_id',
                'chemicals.chem_name',
                'service_chemicals.svcch_expected_quantity',
                'service_chemicals.svcch_unit'
            )
            ->get();

        return view('service_orders.appointments.view_assessed', compact('display', 'pestTypes', 'serviceAreas', 'termiteAreas', 'appointmentImages', 'technicians', 'approvedDate', 'approvedTimeFrom', 'approvedTimeTo', 'daySchedules', 'allTechs', 'mappingImages', 'contracts', 'hasOwnContract', 'serviceChemicals'));
    }

    public function assessed_appointments_update_schedule(Request $request)
    {
        $request->validate([
            'svc_id' => 'required|integer',
            'svca_approved_date' => 'required|date',
            'svca_approved_time_from' => 'required',
            'svca_approved_time_to' => 'required',
        ]);

        $svc_id = $request->svc_id;

        DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->update([
                'svca_approved_date' => $request->svca_approved_date,
                'svca_approved_time_from' => $request->svca_approved_time_from,
                'svca_approved_time_to' => $request->svca_approved_time_to,
                'svca_date_modified' => Carbon::now(),
                'svca_modified_by' => session('usr_id'),
            ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity('Manage Appointments', 'Updated Schedule ' . $serviceOrder);

        session()->flash('successMessage', 'Schedule updated successfully.');
        return redirect()->back();
    }

    public function mapping_images_store(Request $request)
    {
        $request->validate([
            'svc_id' => 'required|integer',
            'mapping_images' => 'required|array|min:1',
            'mapping_images.*' => 'mimes:jpeg,jpg,png,webp|max:8192',
        ]);

        $svc_id = $request->svc_id;

        foreach ($request->file('mapping_images') as $file) {
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $path = public_path('images/mappings/' . $fileName);
            $ext = strtolower($file->getClientOriginalExtension());

            if (in_array($ext, ['jpg', 'jpeg'])) {
                $source = imagecreatefromjpeg($file->getPathname());
                imagejpeg($source, $path, 75);
                imagedestroy($source);

            } elseif ($ext === 'png') {
                $source = imagecreatefrompng($file->getPathname());

                if ($source && imageistruecolor($source) === false) {
                    imagepalettetotruecolor($source);
                }

                if ($source) {
                    imagepng($source, $path, 7);
                    imagedestroy($source);
                }

            } elseif ($ext === 'webp') {
                $source = imagecreatefromwebp($file->getPathname());
                imagewebp($source, $path, 75);
                imagedestroy($source);
            }

            DB::table('service_mapping_images')->insert([
                'svcmp_uuid' => generateuuid(),
                'svc_id' => $svc_id,
                'svcmp_image' => $fileName,
                'svcmp_date_created' => Carbon::now(),
                'svcmp_created_by' => session('usr_id'),
                'svcmp_active' => 1,
            ]);
        }

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);
        logUserActivity('Manage Appointments', 'Uploaded Mapping Image(s) for ' . $serviceOrder);

        session()->flash('successMessage', 'Mapping image(s) uploaded successfully.');
        return redirect()->back();
    }

    public function mapping_images_delete(Request $request)
    {
        $request->validate([
            'svcmp_uuid' => 'required',
            'svc_id' => 'required|integer',
        ]);

        DB::table('service_mapping_images')
            ->where('svcmp_uuid', $request->svcmp_uuid)
            ->update([
                'svcmp_active' => 0,
                'svcmp_date_modified' => Carbon::now(),
                'svcmp_modified_by' => session('usr_id'),
            ]);

        $serviceOrder = 'SA-' . str_pad($request->svc_id, 6, '0', STR_PAD_LEFT);
        logUserActivity('Manage Appointments', 'Removed Mapping Image for ' . $serviceOrder);

        session()->flash('successMessage', 'Mapping image removed successfully.');
        return redirect()->back();
    }

    public function assessed_appointments_view_assign(Request $request)
    {
        $request->validate([
            'svc_id' => 'required',
            'svcasa_assigned_to' => 'required|array|min:1',
        ]);

        $svc_id = $request->svc_id;

        // Get the svca_id for this service
        $appointment = DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->first();

        DB::table('services')
            ->where('svc_id', $svc_id)
            ->update([
                'svc_status' => 'SCHEDULED',
            ]);

        DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->update([
                'svca_status' => 'ASSIGNED',
                'svca_date_modified' => Carbon::now(),
                'svca_modified_by' => session('usr_id'),
            ]);

        $svcas_id = DB::table('service_appointment_schedules')
            ->insertGetId([
                'svcas_uuid' => generateuuid(),
                'svca_id' => $appointment->svca_id,
                'svcas_assigned_by' => session('usr_id'),
                'svcas_date_assigned' => Carbon::now(),
                'svcas_date_created' => Carbon::now(),
                'svcas_created_by' => session('usr_id'),
                'svcas_active' => 1,
            ]);

        foreach ($request->svcasa_assigned_to as $techId) {
            DB::table('service_appointment_schedule_assigns')
                ->insert([
                    'svcasa_uuid' => generateuuid(),
                    'svcas_id' => $svcas_id,
                    'svcasa_assigned_to' => $techId,
                    'svcasa_date_created' => Carbon::now(),
                    'svcasa_created_by' => session('usr_id'),
                    'svcasa_active' => 1,
                ]);
        }

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity('Manage Appointments', 'Assigned Technician(s) ' . $serviceOrder);

        session()->flash('successMessage', 'Assigned technician(s) successfully.');
        return redirect()->action(
            [AppointmentController::class, 'scheduled_appointments_view'],
            ['svc_id' => $svc_id]
        );
    }
    // END ASSESSED APPOINTMENTS

    // START SCHEDULED APPOINTMENTS
    public function scheduled_appointments_view($svc_id)
    {
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->leftJoin('users as approver', 'service_appointments.svca_approved_by', '=', 'approver.usr_id')
            ->leftJoin('service_appointment_schedules', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
            ->where('services.svc_id', $svc_id)
            ->where('service_appointment_schedules.svcas_active', 1)
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_km_distance',
                'services.svc_property_type',
                'services.svc_is_package',
                'services.svcpat_id',
                'services.svc_is_termite',
                'services.svc_type_treatment',
                'services.svc_sqm_initial',
                'services.svc_sqm_final',
                'services.svc_with_device',
                'services.svc_device_count',
                'services.svc_problem_description',
                'services.svc_status',
                'services.svc_infestation',
                'services.svc_initial_price',
                'services.svc_location_price',
                'services.svc_device_price',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_payment_status',
                'services.svc_assessment_recommendation',
                'services.svcc_id',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email',
                'users.usr_mobile',
                'branches.branch_id',
                'branches.branch_name',
                'service_appointments.svca_client_date',
                'service_appointments.svca_client_time',
                'service_appointments.svca_approved_date',
                'service_appointments.svca_approved_time_from',
                'service_appointments.svca_approved_time_to',
                'service_appointments.svca_date_approved',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'service_appointment_schedules.svcas_id',
                'service_appointment_schedules.svcas_date_assigned',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        // Currently assigned technicians (one-to-many)
        $assignedTechs = DB::table('service_appointment_schedule_assigns')
            ->leftJoin('users as assigned_to', 'assigned_to.usr_id', '=', 'service_appointment_schedule_assigns.svcasa_assigned_to')
            ->where('service_appointment_schedule_assigns.svcas_id', $display->svcas_id)
            ->where('service_appointment_schedule_assigns.svcasa_active', 1)
            ->select(
                'assigned_to.usr_id',
                'assigned_to.usr_first_name',
                'assigned_to.usr_last_name'
            )
            ->get();

        $display->assigned_technician_ids = $assignedTechs->pluck('usr_id')->toArray();

        $display->assigned_to_label = $assignedTechs->isEmpty()
            ? null
            : $assignedTechs->map(fn($t) => $t->usr_first_name . ' ' . $t->usr_last_name)->implode(', ');

        $pestTypes = DB::table('service_order_pests')
            ->leftJoin('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
            ->where('service_order_pests.svc_id', $svc_id)
            ->where('service_order_pests.svcop_active', 1)
            ->select(
                'service_order_pests.svcop_id',
                'service_packages.svcp_id',
                'service_packages.svcp_pest_type'
            )
            ->get();

        $serviceAreas = DB::table('service_orders')
            ->leftJoin('service_package_areas', 'service_orders.svcpa_id', '=', 'service_package_areas.svcpa_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_areas.svcpa_id',
                'service_package_areas.svcpa_area',
                'service_package_areas.svcpa_cost'
            )
            ->get();

        $termiteAreas = DB::table('service_orders')
            ->leftJoin('service_package_area_termites', 'service_orders.svcpat_id', '=', 'service_package_area_termites.svcpat_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNotNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_area_termites.svcpat_id',
                'service_package_area_termites.svcpat_sqm_details',
                'service_package_area_termites.svcpat_cost'
            )
            ->get();

        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        $approvedDate = null;
        $approvedTimeFrom = null;
        $approvedTimeTo = null;

        $appointment = DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->first();

        if ($appointment) {
            $approvedDate = $appointment->svca_approved_date;
            $approvedTimeFrom = $appointment->svca_approved_time_from;
            $approvedTimeTo = $appointment->svca_approved_time_to;
        }

        $dayName = $approvedDate
            ? strtoupper(Carbon::parse($approvedDate)->format('l'))
            : null;

        $technicians = DB::table('users')
            ->where('utyp_id', 2)
            ->where('usr_active', 1)
            ->where('branch_id', $display->branch_id)
            ->orderBy('usr_last_name', 'asc')
            ->select('usr_id', 'usr_first_name', 'usr_last_name')
            ->get()
            ->map(function ($tech) use ($dayName, $approvedDate, $approvedTimeFrom, $approvedTimeTo) {
                $isRestDay = false;
                if ($dayName) {
                    $avail = DB::table('user_availabilities')
                        ->where('usr_id', $tech->usr_id)
                        ->where('uavail_name', $dayName)
                        ->first();
                    $isRestDay = !$avail || $avail->uavail_active == 0;
                }

                $isBusy = false;
                if ($approvedDate && $approvedTimeFrom && $approvedTimeTo) {
                    $conflict = DB::table('service_appointment_schedule_assigns')
                        ->join('service_appointment_schedules', 'service_appointment_schedules.svcas_id', '=', 'service_appointment_schedule_assigns.svcas_id')
                        ->join('service_appointments', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
                        ->where('service_appointment_schedule_assigns.svcasa_assigned_to', $tech->usr_id)
                        ->where('service_appointment_schedule_assigns.svcasa_active', 1)
                        ->where('service_appointment_schedules.svcas_active', 1)
                        ->where('service_appointments.svca_approved_date', $approvedDate)
                        ->where('service_appointments.svca_approved_time_from', '<', $approvedTimeTo)
                        ->where('service_appointments.svca_approved_time_to', '>', $approvedTimeFrom)
                        ->first();
                    $isBusy = (bool) $conflict;
                }

                $tech->is_rest_day = $isRestDay;
                $tech->is_busy = $isBusy;
                return $tech;
            });

        // Existing schedules for the timeline (all techs, same date)
        $daySchedules = [];
        if ($approvedDate) {
            $rows = DB::table('service_appointment_schedule_assigns')
                ->join('service_appointment_schedules', 'service_appointment_schedules.svcas_id', '=', 'service_appointment_schedule_assigns.svcas_id')
                ->join('service_appointments', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
                ->join('services', 'services.svc_id', '=', 'service_appointments.svc_id')
                ->join('users as clients', 'clients.usr_id', '=', 'services.usr_id')
                ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
                ->leftJoin('users as assigner', 'assigner.usr_id', '=', 'service_appointment_schedules.svcas_assigned_by')
                ->where('service_appointment_schedule_assigns.svcasa_active', 1)
                ->where('service_appointment_schedules.svcas_active', 1)
                ->where('service_appointments.svca_approved_date', $approvedDate)
                ->select(
                    'service_appointment_schedule_assigns.svcasa_assigned_to as svcas_assigned_to',
                    'service_appointment_schedules.svcas_date_assigned',
                    'service_appointment_schedules.svcas_assigned_by',
                    'assigner.usr_first_name as assigner_first_name',
                    'assigner.usr_last_name as assigner_last_name',
                    'service_appointments.svca_approved_time_from',
                    'service_appointments.svca_approved_time_to',
                    'service_appointments.svc_id as appt_svc_id',
                    'clients.usr_first_name',
                    'clients.usr_last_name',
                    'clients.usr_email',
                    'clients.usr_mobile',
                    'user_addresses.uadd_street',
                    'user_addresses.uadd_barangay',
                    'user_addresses.uadd_city',
                    'user_addresses.uadd_province',
                    'user_addresses.uadd_region',
                    'services.svc_km_distance'
                )
                ->get();

            foreach ($rows as $row) {
                $daySchedules[$row->svcas_assigned_to][] = $row;
            }
        }

        $allTechs = $technicians->map(function ($t) {
            return [
                'id' => $t->usr_id,
                'label' => $t->usr_last_name . ', ' . substr($t->usr_first_name, 0, 1) . '.',
                'is_rest' => $t->is_rest_day,
                'is_busy' => $t->is_busy,
            ];
        })->values();

        $mappingImages = DB::table('service_mapping_images')
            ->where('svc_id', $svc_id)
            ->where('svcmp_active', 1)
            ->orderByDesc('svcmp_date_created')
            ->get();

        $contracts = DB::table('service_contracts')
            ->where('svc_id', $svc_id)
            ->where('svcc_active', 1)
            ->orderByDesc('svcc_date_created')
            ->get();

        $hasOwnContract = $contracts->isNotEmpty();

        if (!empty($display->svcc_id) && !$contracts->pluck('svcc_id')->contains($display->svcc_id)) {
            $linkedContract = DB::table('service_contracts')
                ->where('svcc_id', $display->svcc_id)
                ->where('svcc_active', 1)
                ->first();

            if ($linkedContract) {
                $contracts->push($linkedContract);
            }
        }

        foreach ($contracts as $contract) {
            $contract->generated_services = DB::table('services')
                ->where('svcc_id', $contract->svcc_id)
                ->where('svc_active', 1)
                ->orderBy('svc_id')
                ->select('svc_id', 'svc_sa_number', 'svc_status')
                ->get();
        }

        // Chemicals used for this service
        $serviceChemicals = DB::table('service_chemicals')
            ->join('chemicals', 'service_chemicals.chem_id', '=', 'chemicals.chem_id')
            ->where('service_chemicals.svc_id', $svc_id)
            ->where('service_chemicals.svcch_active', 1)
            ->select(
                'service_chemicals.svcch_id',
                'chemicals.chem_name',
                'service_chemicals.svcch_expected_quantity',
                'service_chemicals.svcch_unit'
            )
            ->get();

        return view('service_orders.appointments.view_scheduled', compact(
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'appointmentImages',
            'technicians',
            'approvedDate',
            'approvedTimeFrom',
            'approvedTimeTo',
            'daySchedules',
            'allTechs',
            'mappingImages',
            'contracts',
            'hasOwnContract',
            'serviceChemicals'
        ));
    }

    public function scheduled_appointments_update_scheduled(Request $request)
    {
        $request->validate([
            'svc_id' => 'required|integer',
            'svca_approved_date' => 'required|date',
            'svca_approved_time_from' => 'required',
            'svca_approved_time_to' => 'required',
        ]);

        $svc_id = $request->svc_id;

        DB::transaction(function () use ($request, $svc_id) {
            $appointment = DB::table('service_appointments')
                ->where('svc_id', $svc_id)
                ->first();

            // Update the schedule, and move the appointment back to ASSESSED
            DB::table('service_appointments')
                ->where('svc_id', $svc_id)
                ->update([
                    'svca_approved_date' => $request->svca_approved_date,
                    'svca_approved_time_from' => $request->svca_approved_time_from,
                    'svca_approved_time_to' => $request->svca_approved_time_to,
                    'svca_status' => 'ASSESSED',
                    'svca_date_modified' => Carbon::now(),
                    'svca_modified_by' => session('usr_id'),
                ]);

            // Move the service back to ASSESSED
            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update([
                    'svc_status' => 'ASSESSED',
                ]);

            // Soft delete the existing technician assignment(s)
            $scheduleIds = DB::table('service_appointment_schedules')
                ->where('svca_id', $appointment->svca_id)
                ->where('svcas_active', 1)
                ->pluck('svcas_id');

            if ($scheduleIds->isNotEmpty()) {
                DB::table('service_appointment_schedule_assigns')
                    ->whereIn('svcas_id', $scheduleIds)
                    ->where('svcasa_active', 1)
                    ->update(['svcasa_active' => 0]);

                DB::table('service_appointment_schedules')
                    ->whereIn('svcas_id', $scheduleIds)
                    ->update(['svcas_active' => 0]);
            }
        });

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity('Manage Appointments', 'Updated Schedule and Unassigned Technician(s) ' . $serviceOrder);

        session()->flash('successMessage', 'Schedule updated. Please assign a technician again.');

        // The appointment is ASSESSED now, so the "scheduled" view no longer applies
        return redirect(url('service/order/appointments/assessed/' . $svc_id));
    }

    public function scheduled_appointments_view_update_technician(Request $request, $svc_id)
    {
        $request->validate([
            'svcasa_assigned_to' => 'required|array|min:1',
        ]);

        $schedule = DB::table('service_appointment_schedules')
            ->join('service_appointments', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_schedules.svcas_active', 1)
            ->select('service_appointment_schedules.svcas_id')
            ->first();

        $svcas_id = $schedule->svcas_id;
        $selectedTechIds = $request->svcasa_assigned_to;

        // All rows (active or inactive) for this svcas_id
        $existingRows = DB::table('service_appointment_schedule_assigns')
            ->where('svcas_id', $svcas_id)
            ->get();

        $existingByTechId = $existingRows->keyBy('svcasa_assigned_to');

        foreach ($selectedTechIds as $techId) {
            if ($existingByTechId->has($techId)) {
                // Already has a row (active or inactive) — reactivate it
                DB::table('service_appointment_schedule_assigns')
                    ->where('svcas_id', $svcas_id)
                    ->where('svcasa_assigned_to', $techId)
                    ->update(['svcasa_active' => 1]);
            } else {
                // No row yet for this tech — insert fresh
                DB::table('service_appointment_schedule_assigns')
                    ->insert([
                        'svcasa_uuid' => generateuuid(),
                        'svcas_id' => $svcas_id,
                        'svcasa_assigned_to' => $techId,
                        'svcasa_date_created' => Carbon::now(),
                        'svcasa_created_by' => session('usr_id'),
                        'svcasa_active' => 1,
                    ]);
            }
        }

        // Deactivate any tech that was active before but is no longer selected
        DB::table('service_appointment_schedule_assigns')
            ->where('svcas_id', $svcas_id)
            ->where('svcasa_active', 1)
            ->whereNotIn('svcasa_assigned_to', $selectedTechIds)
            ->update(['svcasa_active' => 0]);

        DB::table('service_appointment_schedules')
            ->where('svcas_id', $svcas_id)
            ->update([
                'svcas_date_modified' => Carbon::now(),
                'svcas_modified_by' => session('usr_id'),
            ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity('Manage Appointments', 'Changed Technician(s) ' . $serviceOrder);

        session()->flash('successMessage', 'Technician(s) changed successfully.');
        return redirect()->back();
    }
    // END SCHEDULED APPOINTMENTS

    // START ONGOING APPOINTMENTS
    public function ongoing_appointments(Request $request)
    {
        $search = $request->search ?? '';

        $sessionBranchId = session('branch_id');

        $today = now()->toDateString();

        // Base query – ONGOING only (no longer joins technician directly — multiple techs possible per appointment)
        $query = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'service_appointments.svc_id', '=', 'services.svc_id')
            ->leftJoin(
                'service_appointment_schedules',
                'service_appointment_schedules.svca_id',
                '=',
                'service_appointments.svca_id'
            )
            ->where('services.svc_active', 1)
            ->whereIn('services.svc_status', ['ONGOING', 'DONE SERVICE']);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_status',
            'services.svc_payment_status',
            'services.svc_date_created',
            'users.usr_first_name',
            'users.usr_last_name',
            'users.usr_email',
            'users.usr_mobile',
            'branches.branch_name',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'service_appointment_schedules.svcas_id',
            'service_appointment_schedules.svcas_start_date',
            'service_appointment_schedules.svcas_start_time',
            'service_appointment_schedules.svcas_end_date',
            'service_appointment_schedules.svcas_end_time',
            'service_appointment_schedules.svcas_time_finished'
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_email', 'LIKE', "%$search%")
                    ->orWhere('users.usr_mobile', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_appointments.svca_approved_date', 'asc')
            ->orderBy('service_appointments.svca_approved_time_from', 'asc');

        // Available dates
        $availableDates = (clone $query)
            ->whereNotNull('service_appointments.svca_approved_date')
            ->orderBy('service_appointments.svca_approved_date', 'asc')
            ->pluck('service_appointments.svca_approved_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->toArray();

        if (in_array($today, $availableDates)) {
            $defaultDate = $today;
        } elseif (!empty($availableDates)) {
            $pastDates = array_filter($availableDates, fn($d) => $d < $today);
            $defaultDate = !empty($pastDates)
                ? max($pastDates)
                : min($availableDates);
        } else {
            $defaultDate = $today;
        }

        $tlDate = $request->tl_date
            ? Carbon::parse($request->tl_date)->toDateString()
            : $defaultDate;

        $dateIndex = array_search($tlDate, $availableDates);

        $prevDate = ($dateIndex !== false && $dateIndex > 0)
            ? $availableDates[$dateIndex - 1]
            : null;

        $nextDate = ($dateIndex !== false && $dateIndex < count($availableDates) - 1)
            ? $availableDates[$dateIndex + 1]
            : null;

        // Timeline rows for selected date
        $tlRows = (clone $query)
            ->whereDate('service_appointments.svca_approved_date', $tlDate)
            ->get();

        // Pull all technician assignments for the svcas_ids on this date
        $svcasIds = $tlRows->pluck('svcas_id')->filter()->unique()->values()->toArray();

        $techAssignsBySvcasId = [];
        if (!empty($svcasIds)) {
            $assignRows = DB::table('service_appointment_schedule_assigns')
                ->leftJoin('users as technicians', 'technicians.usr_id', '=', 'service_appointment_schedule_assigns.svcasa_assigned_to')
                ->whereIn('service_appointment_schedule_assigns.svcas_id', $svcasIds)
                ->where('service_appointment_schedule_assigns.svcasa_active', 1)
                ->select(
                    'service_appointment_schedule_assigns.svcas_id',
                    'technicians.usr_first_name as tech_first_name',
                    'technicians.usr_last_name as tech_last_name'
                )
                ->get();

            foreach ($assignRows as $assignRow) {
                $techAssignsBySvcasId[$assignRow->svcas_id][] = $assignRow;
            }
        }

        // Group by technician (fan out: a multi-tech appointment shows under each assigned tech's row)
        $timelineByTech = [];

        foreach ($tlRows as $row) {
            $assignedTechs = $techAssignsBySvcasId[$row->svcas_id] ?? [];

            if (empty($assignedTechs)) {
                $timelineByTech['Unassigned'][] = $row;
                continue;
            }

            foreach ($assignedTechs as $assignedTech) {
                $techKey = $assignedTech->tech_last_name
                    ? trim($assignedTech->tech_last_name . ', ' . $assignedTech->tech_first_name)
                    : 'Unassigned';

                $timelineByTech[$techKey][] = $row;
            }
        }

        ksort($timelineByTech);

        return view(
            'service_orders.appointments.ongoing.ongoing',
            compact(
                'search',
                'tlDate',
                'tlRows',
                'timelineByTech',
                'availableDates',
                'prevDate',
                'nextDate'
            )
        );
    }

    public function ongoing_appointments_view($svc_id)
    {
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->leftJoin('users as approver', 'service_appointments.svca_approved_by', '=', 'approver.usr_id')
            ->leftJoin('service_appointment_schedules', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
            ->where('services.svc_id', $svc_id)
            ->where('service_appointment_schedules.svcas_active', 1)
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_km_distance',
                'services.svc_property_type',
                'services.svc_is_package',
                'services.svcpat_id',
                'services.svc_is_termite',
                'services.svc_type_treatment',
                'services.svc_sqm_initial',
                'services.svc_sqm_final',
                'services.svc_with_device',
                'services.svc_device_count',
                'services.svc_problem_description',
                'services.svc_status',
                'services.svc_infestation',
                'services.svc_initial_price',
                'services.svc_location_price',
                'services.svc_device_price',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_payment_status',
                'services.svc_assessment_recommendation',
                'services.svcc_id',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email',
                'users.usr_mobile',
                'branches.branch_id',
                'branches.branch_name',
                'service_appointments.svca_client_date',
                'service_appointments.svca_client_time',
                'service_appointments.svca_approved_date',
                'service_appointments.svca_approved_time_from',
                'service_appointments.svca_approved_time_to',
                'service_appointments.svca_date_approved',
                'service_appointments.svca_status',
                'service_appointment_schedules.svcas_id',
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_start_time',
                'service_appointment_schedules.svcas_end_date',
                'service_appointment_schedules.svcas_end_time',
                'service_appointment_schedules.svcas_time_travel',
                'service_appointment_schedules.svcas_time_finished',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'service_appointment_schedules.svcas_date_assigned',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        // Currently assigned technicians (one-to-many)
        $assignedTechs = DB::table('service_appointment_schedule_assigns')
            ->leftJoin('users as assigned_to', 'assigned_to.usr_id', '=', 'service_appointment_schedule_assigns.svcasa_assigned_to')
            ->where('service_appointment_schedule_assigns.svcas_id', $display->svcas_id)
            ->where('service_appointment_schedule_assigns.svcasa_active', 1)
            ->select(
                'assigned_to.usr_id',
                'assigned_to.usr_first_name',
                'assigned_to.usr_last_name'
            )
            ->get();

        $display->assigned_to_label = $assignedTechs->isEmpty()
            ? null
            : $assignedTechs->map(fn($t) => $t->usr_first_name . ' ' . $t->usr_last_name)->implode(', ');

        $pestTypes = DB::table('service_order_pests')
            ->leftJoin('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
            ->where('service_order_pests.svc_id', $svc_id)
            ->where('service_order_pests.svcop_active', 1)
            ->select(
                'service_order_pests.svcop_id',
                'service_packages.svcp_id',
                'service_packages.svcp_pest_type'
            )
            ->get();

        $serviceAreas = DB::table('service_orders')
            ->leftJoin('service_package_areas', 'service_orders.svcpa_id', '=', 'service_package_areas.svcpa_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_areas.svcpa_id',
                'service_package_areas.svcpa_area',
                'service_package_areas.svcpa_cost'
            )
            ->get();

        $termiteAreas = DB::table('service_orders')
            ->leftJoin('service_package_area_termites', 'service_orders.svcpat_id', '=', 'service_package_area_termites.svcpat_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNotNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_area_termites.svcpat_id',
                'service_package_area_termites.svcpat_sqm_details',
                'service_package_area_termites.svcpat_cost'
            )
            ->get();

        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        $techLocation = DB::table('service_appointments')
            ->join('service_appointment_schedules', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
            ->join('service_appointment_locations', 'service_appointment_schedules.svcas_id', '=', 'service_appointment_locations.svcas_id')
            ->join('users as tech_user', 'service_appointment_locations.usr_id', '=', 'tech_user.usr_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_locations.svcal_active', 1)
            ->select(
                'service_appointment_locations.svcal_latitude',
                'service_appointment_locations.svcal_longitude',
                'service_appointments.svca_status',
                'tech_user.usr_first_name as tech_first_name',
                'tech_user.usr_last_name as tech_last_name',
            )
            ->first();

        $mappingImages = DB::table('service_mapping_images')
            ->where('svc_id', $svc_id)
            ->where('svcmp_active', 1)
            ->orderByDesc('svcmp_date_created')
            ->get();

        $contracts = DB::table('service_contracts')
            ->where('svc_id', $svc_id)
            ->where('svcc_active', 1)
            ->orderByDesc('svcc_date_created')
            ->get();

        $hasOwnContract = $contracts->isNotEmpty();

        if (!empty($display->svcc_id) && !$contracts->pluck('svcc_id')->contains($display->svcc_id)) {
            $linkedContract = DB::table('service_contracts')
                ->where('svcc_id', $display->svcc_id)
                ->where('svcc_active', 1)
                ->first();

            if ($linkedContract) {
                $contracts->push($linkedContract);
            }
        }

        foreach ($contracts as $contract) {
            $contract->generated_services = DB::table('services')
                ->where('svcc_id', $contract->svcc_id)
                ->where('svc_active', 1)
                ->orderBy('svc_id')
                ->select('svc_id', 'svc_sa_number', 'svc_status')
                ->get();
        }

        // Chemicals used for this service
        $serviceChemicals = DB::table('service_chemicals')
            ->join('chemicals', 'service_chemicals.chem_id', '=', 'chemicals.chem_id')
            ->where('service_chemicals.svc_id', $svc_id)
            ->where('service_chemicals.svcch_active', 1)
            ->select(
                'service_chemicals.svcch_id',
                'chemicals.chem_name',
                'service_chemicals.svcch_expected_quantity',
                'service_chemicals.svcch_unit'
            )
            ->get();

        return view('service_orders.appointments.ongoing.view_ongoing', compact(
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'appointmentImages',
            'techLocation',
            'mappingImages',
            'contracts',
            'hasOwnContract',
            'serviceChemicals'
        ));
    }

    public function ongoing_appointments_view_mark_arrived(Request $request, $svc_id)
    {
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if (!$service) {
            session()->flash('errorMessage', 'Service order not found.');
            return redirect()->back();
        }

        $appointment = DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->first();

        if (!$appointment) {
            session()->flash('errorMessage', 'Service appointment not found.');
            return redirect()->back();
        }

        $schedule = DB::table('service_appointment_schedules')
            ->where('svca_id', $appointment->svca_id)
            ->where('svcas_active', 1)
            ->first();

        if (!$schedule) {
            session()->flash('errorMessage', 'Active schedule not found for this appointment.');
            return redirect()->back();
        }

        $now = Carbon::now();

        $startedTravelAt = Carbon::parse(
            $schedule->svcas_start_date . ' ' . $schedule->svcas_start_time
        );

        $totalSeconds = $startedTravelAt->diffInSeconds($now);

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        $travelTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        DB::table('service_appointments')
            ->where('svca_id', $appointment->svca_id)
            ->update([
                'svca_status' => 'ARRIVED',
            ]);

        DB::table('service_appointment_schedules')
            ->where('svcas_id', $schedule->svcas_id)
            ->update([
                'svcas_time_travel' => $travelTime,
                'svcas_date_modified' => $now,
                'svcas_modified_by' => session('usr_id'),
            ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Appointments',
            'Marked Service Order as Arrived (Admin Override) ' . $serviceOrder
        );

        session()->flash('successMessage', 'Service order marked as arrived successfully.');
        return redirect()->back();
    }

    public function ongoing_appointments_view_mark_done_service(Request $request, $svc_id)
    {
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if (!$service) {
            session()->flash('errorMessage', 'Service order not found.');
            return redirect()->back();
        }

        $appointment = DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->first();

        if (!$appointment) {
            session()->flash('errorMessage', 'Service appointment not found.');
            return redirect()->back();
        }

        $schedule = DB::table('service_appointment_schedules')
            ->where('svca_id', $appointment->svca_id)
            ->where('svcas_active', 1)
            ->first();

        if (!$schedule) {
            session()->flash('errorMessage', 'Active schedule not found for this appointment.');
            return redirect()->back();
        }

        $now = Carbon::now();

        $startedAt = Carbon::parse(
            $schedule->svcas_start_date . ' ' . $schedule->svcas_start_time
        );

        $totalSeconds = $startedAt->diffInSeconds($now);

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        $timeFinished = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        DB::table('services')
            ->where('svc_id', $svc_id)
            ->update([
                'svc_status' => 'DONE SERVICE',
            ]);

        DB::table('service_appointments')
            ->where('svca_id', $appointment->svca_id)
            ->update([
                'svca_status' => 'DONE SERVICE',
            ]);

        DB::table('service_appointment_schedules')
            ->where('svcas_id', $schedule->svcas_id)
            ->update([
                'svcas_end_date' => $now->toDateString(),
                'svcas_end_time' => $now->format('H:i:s'),
                'svcas_time_finished' => $timeFinished,
                'svcas_date_modified' => $now,
                'svcas_modified_by' => session('usr_id'),
            ]);

        DB::table('service_appointment_locations')
            ->where('svcas_id', $schedule->svcas_id)
            ->update([
                'svcal_active' => 0,
                'svcal_date_modified' => $now,
                'svcal_modified_by' => session('usr_id'),
            ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Appointments',
            'Marked Service Order as Done Service (Admin Override) ' . $serviceOrder
        );

        session()->flash('successMessage', 'Service order marked as done service successfully.');

        return redirect()->back();
    }

    public function ongoing_appointments_view_track($svc_id)
    {
        $techLocation = DB::table('service_appointments')
            ->join('service_appointment_schedules', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
            ->join('service_appointment_locations', 'service_appointment_schedules.svcas_id', '=', 'service_appointment_locations.svcas_id')
            ->join('users as tech_user', 'service_appointment_locations.usr_id', '=', 'tech_user.usr_id')
            ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_locations.svcal_active', 1)
            ->select(
                'service_appointment_locations.svcal_latitude',
                'service_appointment_locations.svcal_longitude',
                'service_appointments.svca_status',
                'tech_user.usr_first_name as tech_first_name',
                'tech_user.usr_last_name as tech_last_name',
                'user_addresses.uadd_latitude as client_latitude',
                'user_addresses.uadd_longitude as client_longitude',
            )
            ->first();

        return response()->json($techLocation);
    }

    public function ongoing_appointments_tracking(Request $request)
    {
        $sessionBranchId = session('branch_id');
        $searchName = $request->search_name ?? '';
        $filterBranch = $request->filter_branch ?? '';

        // Branch list for dropdown (branch_id != 1)
        $branches = DB::table('branches')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->orderBy('branch_name')
            ->get();

        // Base query: active locations only
        $query = DB::table('service_appointment_locations')
            ->join(
                'service_appointment_schedules',
                'service_appointment_locations.svcas_id',
                '=',
                'service_appointment_schedules.svcas_id'
            )
            ->join(
                'service_appointments',
                'service_appointment_schedules.svca_id',
                '=',
                'service_appointments.svca_id'
            )
            ->join(
                'services',
                'service_appointments.svc_id',
                '=',
                'services.svc_id'
            )
            ->join(
                'users as tech_user',
                'service_appointment_locations.usr_id',
                '=',
                'tech_user.usr_id'
            )
            ->join(
                'branches',
                'services.branch_id',
                '=',
                'branches.branch_id'
            )
            ->where('service_appointment_locations.svcal_active', 1)
            ->whereIn('service_appointments.svca_status', ['ONGOING', 'ARRIVED'])
            ->select(
                'service_appointment_locations.svcal_latitude',
                'service_appointment_locations.svcal_longitude',
                'service_appointments.svca_status',
                'services.svc_id',
                'services.branch_id as svc_branch_id',
                'branches.branch_name',
                'tech_user.usr_id as tech_id',
                'tech_user.usr_first_name as tech_first_name',
                'tech_user.usr_last_name as tech_last_name',
            );

        // Branch filter
        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif (!empty($filterBranch)) {
            $query->where('services.branch_id', $filterBranch);
        }

        // Name filter
        if (!empty($searchName)) {
            $query->where(function ($q) use ($searchName) {
                $q->where('tech_user.usr_first_name', 'LIKE', "%$searchName%")
                    ->orWhere('tech_user.usr_last_name', 'LIKE', "%$searchName%");
            });
        }

        $techLocations = $query->get();

        return view('service_orders.appointments.ongoing.track_ongoing', compact(
            'techLocations',
            'branches',
            'searchName',
            'filterBranch',
            'sessionBranchId'
        ));
    }
    // END ONGOING APPOINTMENTS

    // START FINISHED APPOINTMENTS
    public function finished_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionBranchId = session('branch_id');

        $query = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'service_appointments.svc_id', '=', 'services.svc_id')
            ->leftJoin(
                'service_appointment_schedules',
                'service_appointment_schedules.svca_id',
                '=',
                'service_appointments.svca_id'
            )
            ->where('services.svc_active', 1)
            ->where('services.svc_status', 'DONE REPORT');

        // Branch filter (same logic as users_active)
        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_payment_status',
            'users.usr_first_name',
            'users.usr_last_name',
            'users.usr_email',
            'users.usr_mobile',
            'branches.branch_name',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'service_appointment_schedules.svcas_start_date',
            'service_appointment_schedules.svcas_start_time',
            'service_appointment_schedules.svcas_end_date',
            'service_appointment_schedules.svcas_end_time',
            'service_appointment_schedules.svcas_time_travel',
            'service_appointment_schedules.svcas_time_finished'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_email', 'LIKE', "%$search%")
                    ->orWhere('users.usr_mobile', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('services.svc_date_created', 'asc');

        $appointments = $query->paginate(300);

        return view('service_orders.appointments.finished.finished', compact('appointments', 'search'));
    }

    public function finished_appointments_view($svc_id)
    {
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->leftJoin('users as approver', 'service_appointments.svca_approved_by', '=', 'approver.usr_id')
            ->leftJoin('service_appointment_schedules', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
            ->where('services.svc_id', $svc_id)
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_km_distance',
                'services.svc_property_type',
                'services.svc_is_package',
                'services.svcpat_id',
                'services.svc_is_termite',
                'services.svc_type_treatment',
                'services.svc_sqm_initial',
                'services.svc_sqm_final',
                'services.svc_with_device',
                'services.svc_device_count',
                'services.svc_problem_description',
                'services.svc_status',
                'services.svc_infestation',
                'services.svc_initial_price',
                'services.svc_location_price',
                'services.svc_device_price',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_payment_status',
                'services.svc_assessment_recommendation',
                'services.svc_rat_captured',
                'services.svc_snake_captured',
                'services.svc_lizard_captured',
                'services.svc_kitten_captured',
                'services.svc_other_captured',
                'services.svc_technician_recommendation',
                'services.svc_rating',
                'services.svc_date_rated',
                'services.svcc_id',
                'services.svc_date_completed',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email',
                'users.usr_mobile',
                'branches.branch_id',
                'branches.branch_name',
                'service_appointments.svca_client_date',
                'service_appointments.svca_client_time',
                'service_appointments.svca_approved_date',
                'service_appointments.svca_approved_time_from',
                'service_appointments.svca_approved_time_to',
                'service_appointments.svca_date_approved',
                'service_appointment_schedules.svcas_id',
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_start_time',
                'service_appointment_schedules.svcas_end_date',
                'service_appointment_schedules.svcas_time_travel',
                'service_appointment_schedules.svcas_time_finished',
                'service_appointment_schedules.svcas_end_time',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'service_appointment_schedules.svcas_date_assigned',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        // Currently assigned technicians (one-to-many)
        $assignedTechs = DB::table('service_appointment_schedule_assigns')
            ->leftJoin('users as assigned_to', 'assigned_to.usr_id', '=', 'service_appointment_schedule_assigns.svcasa_assigned_to')
            ->where('service_appointment_schedule_assigns.svcas_id', $display->svcas_id)
            ->where('service_appointment_schedule_assigns.svcasa_active', 1)
            ->select(
                'assigned_to.usr_id',
                'assigned_to.usr_first_name',
                'assigned_to.usr_last_name'
            )
            ->get();

        $display->assigned_to_label = $assignedTechs->isEmpty()
            ? null
            : $assignedTechs->map(fn($t) => $t->usr_last_name . ', ' . $t->usr_first_name)->implode(', ');

        $pestTypes = DB::table('service_order_pests')
            ->leftJoin('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
            ->where('service_order_pests.svc_id', $svc_id)
            ->where('service_order_pests.svcop_active', 1)
            ->select(
                'service_order_pests.svcop_id',
                'service_packages.svcp_id',
                'service_packages.svcp_pest_type'
            )
            ->get();

        $serviceAreas = DB::table('service_orders')
            ->leftJoin('service_package_areas', 'service_orders.svcpa_id', '=', 'service_package_areas.svcpa_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_areas.svcpa_id',
                'service_package_areas.svcpa_area',
                'service_package_areas.svcpa_cost'
            )
            ->get();

        $termiteAreas = DB::table('service_orders')
            ->leftJoin('service_package_area_termites', 'service_orders.svcpat_id', '=', 'service_package_area_termites.svcpat_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNotNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_area_termites.svcpat_id',
                'service_package_area_termites.svcpat_sqm_details',
                'service_package_area_termites.svcpat_cost'
            )
            ->get();

        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        $technicianImages = DB::table('service_appointment_schedule_images')
            ->join('service_appointment_schedules', 'service_appointment_schedule_images.svcas_id', '=', 'service_appointment_schedules.svcas_id')
            ->join('service_appointments', 'service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_schedule_images.svcasp_active', 1)
            ->select('service_appointment_schedule_images.*')
            ->get();

        $mappingImages = DB::table('service_mapping_images')
            ->where('svc_id', $svc_id)
            ->where('svcmp_active', 1)
            ->orderByDesc('svcmp_date_created')
            ->get();

        $contracts = DB::table('service_contracts')
            ->where('svc_id', $svc_id)
            ->where('svcc_active', 1)
            ->orderByDesc('svcc_date_created')
            ->get();

        $hasOwnContract = $contracts->isNotEmpty();

        if (!empty($display->svcc_id) && !$contracts->pluck('svcc_id')->contains($display->svcc_id)) {
            $linkedContract = DB::table('service_contracts')
                ->where('svcc_id', $display->svcc_id)
                ->where('svcc_active', 1)
                ->first();

            if ($linkedContract) {
                $contracts->push($linkedContract);
            }
        }

        foreach ($contracts as $contract) {
            $contract->generated_services = DB::table('services')
                ->where('svcc_id', $contract->svcc_id)
                ->where('svc_active', 1)
                ->orderBy('svc_id')
                ->select('svc_id', 'svc_sa_number', 'svc_status')
                ->get();
        }

        // Chemicals used for this service
        $serviceChemicals = DB::table('service_chemicals')
            ->join('chemicals', 'service_chemicals.chem_id', '=', 'chemicals.chem_id')
            ->where('service_chemicals.svc_id', $svc_id)
            ->where('service_chemicals.svcch_active', 1)
            ->select(
                'service_chemicals.svcch_id',
                'chemicals.chem_name',
                'service_chemicals.svcch_expected_quantity',
                'service_chemicals.svcch_actual_quantity',
                'service_chemicals.svcch_unit'
            )
            ->get();

        return view('service_orders.appointments.finished.view_finished', compact(
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'appointmentImages',
            'technicianImages',
            'mappingImages',
            'contracts',
            'hasOwnContract',
            'serviceChemicals'
        ));
    }

    public function finished_appointments_view_initiate_contract(Request $request, $svc_id)
    {
        $request->validate([
            'svcc_type' => ['required', 'regex:/^(WEEKLY|MONTHLY(-[2-4]X)?|BIMONTHLY|QUARTERLY|SEMI-ANNUAL|ANNUAL)$/'],
            'svcc_total_years' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'svcc_contract' => 'required|file|mimes:jpg,jpeg,png,pdf,docx|max:10240',
        ], [
            'svcc_type.regex' => 'Invalid frequency selected.',
            'svcc_total_years.regex' => 'Duration must be in years.months format, e.g. 1.5 or 3.11.',
        ]);

        // Original finished service/appointment we're basing the contract on
        $originalService = DB::table('services')->where('svc_id', $svc_id)->first();
        $originalAppointment = DB::table('service_appointments')->where('svc_id', $svc_id)->first();

        if (!$originalService || !$originalAppointment) {
            session()->flash('errorMessage', 'Service or appointment record not found.');
            return redirect()->back();
        }

        $svccType = $request->svcc_type;

        // Parse "years.months" format (e.g. 1.5 = 1yr 5mo, 3.11 = 3yr 11mo, 0.3 = 3mo)
        $durationParts = explode('.', $request->svcc_total_years);
        $contractYears = (int) $durationParts[0];
        $contractMonths = isset($durationParts[1]) ? (int) $durationParts[1] : 0;

        if ($contractMonths > 11) {
            session()->flash('errorMessage', 'Months portion of duration cannot exceed 11 (e.g. use 2.0 instead of 1.12).');
            return redirect()->back();
        }

        if ($contractYears < 1 && $contractMonths < 1) {
            session()->flash('errorMessage', 'Contract duration must be at least 1 month.');
            return redirect()->back();
        }

        $totalContractMonths = ($contractYears * 12) + $contractMonths;
        $totalYears = (float) ($contractYears . '.' . str_pad($contractMonths, 2, '0', STR_PAD_LEFT));

        // Parse base frequency + occurrence multiplier out of svcc_type (e.g. MONTHLY-3X)
        $baseType = $svccType;
        $occurrence = 1;
        if (preg_match('/^MONTHLY-(\d)X$/', $svccType, $freqMatch)) {
            $baseType = 'MONTHLY';
            $occurrence = (int) $freqMatch[1];
        }

        $occurrencesPerYear = match ($baseType) {
            'WEEKLY' => 52,
            'MONTHLY' => 12 * $occurrence,
            'BIMONTHLY' => 6,
            'QUARTERLY' => 4,
            'SEMI-ANNUAL' => 2,
            'ANNUAL' => 1,
            default => 0,
        };

        $totalServices = (int) round(($occurrencesPerYear / 12) * $totalContractMonths);

        if ($totalServices < 1) {
            session()->flash('errorMessage', 'Invalid contract frequency/duration combination.');
            return redirect()->back();
        }

        // Contract attachment upload (image compression follows mapping_images_store pattern)
        $file = $request->file('svcc_contract');
        $ext = strtolower($file->getClientOriginalExtension());
        $fileName = uniqid() . '_' . $file->getClientOriginalName();
        $path = public_path('images/contracts/' . $fileName);

        if (in_array($ext, ['jpg', 'jpeg'])) {
            $source = imagecreatefromjpeg($file->getPathname());
            imagejpeg($source, $path, 75);
            imagedestroy($source);
        } elseif ($ext === 'png') {
            $source = imagecreatefrompng($file->getPathname());
            if ($source && imageistruecolor($source) === false) {
                imagepalettetotruecolor($source);
            }
            if ($source) {
                imagepng($source, $path, 7);
                imagedestroy($source);
            }
        } else {
            // pdf / docx - no compression, just move
            $file->move(public_path('images/contracts'), $fileName);
        }

        $svccId = DB::table('service_contracts')->insertGetId([
            'svcc_uuid' => generateuuid(),
            'usr_id' => $originalService->usr_id,
            'svc_id' => $svc_id,
            'svcc_type' => $svccType,
            'svcc_total_years' => $totalYears,
            'svcc_total_services' => $totalServices,
            'svcc_contract' => $fileName,
            'svcc_date_created' => Carbon::now(),
            'svcc_created_by' => session('usr_id'),
            'svcc_active' => 1,
        ], 'svcc_id');

        $originalPests = DB::table('service_order_pests')
            ->where('svc_id', $svc_id)
            ->where('svcop_active', 1)
            ->get();

        $originalOrders = DB::table('service_orders')
            ->where('svc_id', $svc_id)
            ->where('svco_active', 1)
            ->get();

        $originalChemicals = DB::table('service_chemicals')
            ->where('svc_id', $svc_id)
            ->where('svcch_active', 1)
            ->get();

        $baseDate = Carbon::parse($originalAppointment->svca_client_date);
        $clientTime = $originalAppointment->svca_client_time;

        // Even spacing in days, derived from occurrences/year — handles WEEKLY,
        // MONTHLY, MONTHLY-2X/3X/4X, BIMONTHLY, QUARTERLY, SEMI-ANNUAL, ANNUAL
        // without a rigid per-type switch.
        $daysInterval = 365 / $occurrencesPerYear;

        for ($i = 1; $i <= $totalServices; $i++) {
            $nextDate = $baseDate->copy()->addDays((int) round($daysInterval * $i));

            // Replicates clients_book: create new services/orders/pests/appointment
            $newSvcId = DB::table('services')->insertGetId([
                'svc_uuid' => generateuuid(),
                'branch_id' => $originalService->branch_id,
                'usr_id' => $originalService->usr_id,
                'svc_km_distance' => $originalService->svc_km_distance,
                'svc_is_package' => $originalService->svc_is_package,
                'svcpat_id' => $originalService->svcpat_id,
                'svc_is_termite' => $originalService->svc_is_termite,
                'svc_problem_description' => strtoupper($originalService->svc_problem_description),
                'svc_status' => 'REQUESTED',
                'svc_initial_price' => $originalService->svc_initial_price,
                'svc_location_price' => $originalService->svc_location_price,
                'svc_balance' => $originalService->svc_initial_price + $originalService->svc_location_price,
                'svc_payment_status' => 'NO PAYMENT',
                'svcc_id' => $svccId,
                'svc_date_created' => Carbon::now(),
                'svc_created_by' => session('usr_id'),
                'svc_active' => 1,
            ]);

            DB::table('services')
                ->where('svc_id', $newSvcId)
                ->update(['svc_sa_number' => $newSvcId]);

            foreach ($originalPests as $pest) {
                DB::table('service_order_pests')->insert([
                    'svcop_uuid' => generateuuid(),
                    'svc_id' => $newSvcId,
                    'svcp_id' => $pest->svcp_id,
                    'svcop_date_created' => Carbon::now(),
                    'svcop_created_by' => session('usr_id'),
                    'svcop_active' => 1,
                ]);
            }

            foreach ($originalOrders as $order) {
                DB::table('service_orders')->insert([
                    'svco_uuid' => generateuuid(),
                    'svc_id' => $newSvcId,
                    'svcpa_id' => $order->svcpa_id,
                    'svcpat_id' => $order->svcpat_id,
                    'svco_date_created' => Carbon::now(),
                    'svco_created_by' => session('usr_id'),
                    'svco_active' => 1,
                ]);
            }

            foreach ($originalChemicals as $chem) {
                DB::table('service_chemicals')->insert([
                    'svcch_uuid' => generateuuid(),
                    'svc_id' => $newSvcId,
                    'chem_id' => $chem->chem_id,
                    'svcch_expected_quantity' => $chem->svcch_expected_quantity,
                    'svcch_unit' => $chem->svcch_unit,
                    'svcch_date_created' => Carbon::now(),
                    'svcch_created_by' => session('usr_id'),
                    'svcch_active' => 1,
                ]);
            }

            $newSvcaId = DB::table('service_appointments')->insertGetId([
                'svca_uuid' => generateuuid(),
                'svc_id' => $newSvcId,
                'uadd_id' => $originalAppointment->uadd_id,
                'svca_client_date' => $nextDate->format('Y-m-d'),
                'svca_client_time' => $clientTime,
                'svca_status' => 'UNASSIGNED',
                'svca_date_created' => Carbon::now(),
                'svca_created_by' => session('usr_id'),
                'svca_active' => 1,
            ]);

            $newServiceOrder = 'SA-' . str_pad($newSvcId, 6, '0', STR_PAD_LEFT);

            // Replicates requested_appointments_view_assess using original service's saved data
            DB::table('services')
                ->where('svc_id', $newSvcId)
                ->update([
                    'svc_property_type' => $originalService->svc_property_type,
                    'svc_fixed_price' => $originalService->svc_fixed_price,
                    'svc_assessment_recommendation' => strtoupper($originalService->svc_assessment_recommendation),
                    'svc_type_treatment' => $originalService->svc_type_treatment,
                    'svc_with_device' => $originalService->svc_with_device,
                    'svc_device_count' => $originalService->svc_device_count,
                    'svc_device_price' => $originalService->svc_device_price,
                    'svc_sqm_initial' => $originalService->svc_sqm_initial,
                    'svc_sqm_final' => $originalService->svc_sqm_final,
                    'svc_status' => 'CONFIRM ASSESSMENT',
                    'svc_infestation' => $originalService->svc_infestation,
                    'svc_location_price' => $originalService->svc_location_price,
                    'svc_initial_price' => $originalService->svc_initial_price,
                    'svc_final_price' => $originalService->svc_final_price,
                    'svc_balance' => $originalService->svc_final_price,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]);

            DB::table('service_appointments')
                ->where('svca_id', $newSvcaId)
                ->update([
                    'svca_approved_date' => $nextDate->format('Y-m-d'),
                    'svca_approved_time_from' => $originalAppointment->svca_approved_time_from,
                    'svca_approved_time_to' => $originalAppointment->svca_approved_time_to,
                    'svca_date_approved' => Carbon::now(),
                    'svca_approved_by' => session('usr_id'),
                    'svca_date_modified' => Carbon::now(),
                    'svca_modified_by' => session('usr_id'),
                ]);

            logUserActivity('Manage Appointments', 'Auto-generated & assessed contract appointment ' . $newServiceOrder);
        }

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);
        logUserActivity('Manage Appointments', 'Initiated ' . $svccType . ' contract (' . $totalYears . ' yr, ' . $totalServices . ' service/s) for ' . $serviceOrder);

        session()->flash('successMessage', 'Contract initiated. ' . $totalServices . ' appointment(s) generated.');
        return redirect()->back();
    }

    public function finished_appointments_view_complete(Request $request, $svc_id)
    {
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if (!$service) {
            session()->flash('errorMessage', 'Service order not found.');
            return redirect()->back();
        }

        $appointment = DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->first();

        if (!$appointment) {
            session()->flash('errorMessage', 'Service appointment not found.');
            return redirect()->back();
        }

        DB::table('services')
            ->where('svc_id', $svc_id)
            ->update([
                'svc_status' => 'COMPLETED',
                'svc_date_completed' => Carbon::now(),
                'svc_completed_by' => session('usr_id'),
                'svc_date_modified' => Carbon::now(),
                'svc_modified_by' => session('usr_id'),
            ]);

        DB::table('service_appointments')
            ->where('svc_id', $svc_id)
            ->update([
                'svca_status' => 'COMPLETED',
            ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Appointments',
            'Marked Service Order as Completed ' . $serviceOrder
        );

        session()->flash('successMessage', 'Service order marked as completed successfully.');

        return redirect()->action(
            [AppointmentController::class, 'completed_appointments_view'],
            ['svc_id' => $svc_id]
        );
    }
    // END FINISHED APPOINTMENTS

    // START COMPLETED APPOINTMENTS
    public function completed_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionBranchId = session('branch_id');

        $query = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'service_appointments.svc_id', '=', 'services.svc_id')
            ->leftJoin(
                'service_appointment_schedules',
                'service_appointment_schedules.svca_id',
                '=',
                'service_appointments.svca_id'
            )
            ->where('services.svc_active', 1)
            ->where('services.svc_status', 'COMPLETED');

        // Branch filter (same logic as users_active)
        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_date_completed',
            'users.usr_first_name',
            'users.usr_last_name',
            'users.usr_email',
            'users.usr_mobile',
            'branches.branch_name',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'service_appointment_schedules.svcas_start_date',
            'service_appointment_schedules.svcas_start_time',
            'service_appointment_schedules.svcas_end_date',
            'service_appointment_schedules.svcas_end_time',
            'service_appointment_schedules.svcas_time_travel',
            'service_appointment_schedules.svcas_time_finished'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_email', 'LIKE', "%$search%")
                    ->orWhere('users.usr_mobile', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('services.svc_date_created', 'asc');

        $appointments = $query->paginate(300);

        return view('service_orders.appointments.completed.completed', compact('appointments', 'search'));
    }

    public function completed_appointments_view($svc_id)
    {
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->leftJoin('users as approver', 'service_appointments.svca_approved_by', '=', 'approver.usr_id')
            ->leftJoin('service_appointment_schedules', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
            ->leftJoin('users as completer', 'services.svc_completed_by', '=', 'completer.usr_id')
            ->where('services.svc_id', $svc_id)
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_km_distance',
                'services.svc_property_type',
                'services.svc_is_package',
                'services.svcpat_id',
                'services.svc_is_termite',
                'services.svc_type_treatment',
                'services.svc_sqm_initial',
                'services.svc_sqm_final',
                'services.svc_with_device',
                'services.svc_device_count',
                'services.svc_problem_description',
                'services.svc_status',
                'services.svc_infestation',
                'services.svc_initial_price',
                'services.svc_location_price',
                'services.svc_device_price',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_payment_status',
                'services.svc_assessment_recommendation',
                'services.svc_rat_captured',
                'services.svc_snake_captured',
                'services.svc_lizard_captured',
                'services.svc_kitten_captured',
                'services.svc_other_captured',
                'services.svc_technician_recommendation',
                'services.svc_location_price',
                'services.svc_device_price',
                'services.svcc_id',
                'services.svc_date_completed',
                'services.svc_completed_by',
                'completer.usr_first_name as completed_first_name',
                'completer.usr_last_name as completed_last_name',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email',
                'users.usr_mobile',
                'branches.branch_id',
                'branches.branch_name',
                'service_appointments.svca_client_date',
                'service_appointments.svca_client_time',
                'service_appointments.svca_approved_date',
                'service_appointments.svca_approved_time_from',
                'service_appointments.svca_approved_time_to',
                'service_appointments.svca_date_approved',
                'service_appointment_schedules.svcas_id',
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_start_time',
                'service_appointment_schedules.svcas_end_date',
                'service_appointment_schedules.svcas_time_travel',
                'service_appointment_schedules.svcas_time_finished',
                'service_appointment_schedules.svcas_end_time',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'service_appointment_schedules.svcas_date_assigned',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        // Currently assigned technicians (one-to-many)
        $assignedTechs = DB::table('service_appointment_schedule_assigns')
            ->leftJoin('users as assigned_to', 'assigned_to.usr_id', '=', 'service_appointment_schedule_assigns.svcasa_assigned_to')
            ->where('service_appointment_schedule_assigns.svcas_id', $display->svcas_id)
            ->where('service_appointment_schedule_assigns.svcasa_active', 1)
            ->select(
                'assigned_to.usr_id',
                'assigned_to.usr_first_name',
                'assigned_to.usr_last_name'
            )
            ->get();

        $display->assigned_to_label = $assignedTechs->isEmpty()
            ? null
            : $assignedTechs->map(fn($t) => $t->usr_last_name . ', ' . $t->usr_first_name)->implode(', ');

        $pestTypes = DB::table('service_order_pests')
            ->leftJoin('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
            ->where('service_order_pests.svc_id', $svc_id)
            ->where('service_order_pests.svcop_active', 1)
            ->select(
                'service_order_pests.svcop_id',
                'service_packages.svcp_id',
                'service_packages.svcp_pest_type'
            )
            ->get();

        $serviceAreas = DB::table('service_orders')
            ->leftJoin('service_package_areas', 'service_orders.svcpa_id', '=', 'service_package_areas.svcpa_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_areas.svcpa_id',
                'service_package_areas.svcpa_area',
                'service_package_areas.svcpa_cost'
            )
            ->get();

        $termiteAreas = DB::table('service_orders')
            ->leftJoin('service_package_area_termites', 'service_orders.svcpat_id', '=', 'service_package_area_termites.svcpat_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNotNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_area_termites.svcpat_id',
                'service_package_area_termites.svcpat_sqm_details',
                'service_package_area_termites.svcpat_cost'
            )
            ->get();

        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        $technicianImages = DB::table('service_appointment_schedule_images')
            ->join('service_appointment_schedules', 'service_appointment_schedule_images.svcas_id', '=', 'service_appointment_schedules.svcas_id')
            ->join('service_appointments', 'service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_schedule_images.svcasp_active', 1)
            ->select('service_appointment_schedule_images.*')
            ->get();

        $mappingImages = DB::table('service_mapping_images')
            ->where('svc_id', $svc_id)
            ->where('svcmp_active', 1)
            ->orderByDesc('svcmp_date_created')
            ->get();

        $contracts = DB::table('service_contracts')
            ->where('svc_id', $svc_id)
            ->where('svcc_active', 1)
            ->orderByDesc('svcc_date_created')
            ->get();

        $hasOwnContract = $contracts->isNotEmpty();

        if (!empty($display->svcc_id) && !$contracts->pluck('svcc_id')->contains($display->svcc_id)) {
            $linkedContract = DB::table('service_contracts')
                ->where('svcc_id', $display->svcc_id)
                ->where('svcc_active', 1)
                ->first();

            if ($linkedContract) {
                $contracts->push($linkedContract);
            }
        }

        foreach ($contracts as $contract) {
            $contract->generated_services = DB::table('services')
                ->where('svcc_id', $contract->svcc_id)
                ->where('svc_active', 1)
                ->orderBy('svc_id')
                ->select('svc_id', 'svc_sa_number', 'svc_status')
                ->get();
        }

        // Chemicals used for this service
        $serviceChemicals = DB::table('service_chemicals')
            ->join('chemicals', 'service_chemicals.chem_id', '=', 'chemicals.chem_id')
            ->where('service_chemicals.svc_id', $svc_id)
            ->where('service_chemicals.svcch_active', 1)
            ->select(
                'service_chemicals.svcch_id',
                'chemicals.chem_name',
                'service_chemicals.svcch_expected_quantity',
                'service_chemicals.svcch_actual_quantity',
                'service_chemicals.svcch_unit'
            )
            ->get();

        return view('service_orders.appointments.completed.view_completed', compact(
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'appointmentImages',
            'technicianImages',
            'mappingImages',
            'contracts',
            'hasOwnContract',
            'serviceChemicals'
        ));
    }
    // END COMPLETED APPOINTMENTS

    // START DELETED APPOINTMENTS
    public function delete_appointment(Request $request, $svc_id)
    {
        $service = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->where('services.svc_id', '=', $svc_id)
            ->select(
                'services.svc_id',
                'services.svcc_id',
                'users.usr_first_name',
                'users.usr_last_name'
            )
            ->first();

        if (!$service) {
            alert()->error('Service not found.');
            return redirect()->back();
        }

        DB::transaction(function () use ($svc_id, $service) {
            $now = Carbon::now();
            $uid = session('usr_id');

            DB::table('services')
                ->where('svc_id', '=', $svc_id)
                ->update([
                    'svc_date_deleted' => $now,
                    'svc_deleted_by' => $uid,
                    'svc_date_modified' => $now,
                    'svc_modified_by' => $uid,
                    'svc_active' => 0
                ]);

            DB::table('service_order_pests')
                ->where('svc_id', $svc_id)
                ->where('svcop_active', 1)
                ->update([
                    'svcop_date_modified' => $now,
                    'svcop_modified_by' => $uid,
                    'svcop_active' => 0,
                ]);

            DB::table('service_orders')
                ->where('svc_id', $svc_id)
                ->where('svco_active', 1)
                ->update([
                    'svco_date_modified' => $now,
                    'svco_modified_by' => $uid,
                    'svco_active' => 0,
                ]);

            DB::table('service_chemicals')
                ->where('svc_id', $svc_id)
                ->where('svcch_active', 1)
                ->update([
                    'svcch_date_modified' => $now,
                    'svcch_modified_by' => $uid,
                    'svcch_active' => 0,
                ]);

            DB::table('service_appointments')
                ->where('svc_id', $svc_id)
                ->where('svca_active', 1)
                ->update([
                    'svca_date_modified' => $now,
                    'svca_modified_by' => $uid,
                    'svca_active' => 0,
                ]);

            // Contract: skipped when the service has no contract
            if (!empty($service->svcc_id)) {
                $stillActive = DB::table('services')
                    ->where('svcc_id', $service->svcc_id)
                    ->where('svc_active', 1)
                    ->exists();

                if (!$stillActive) {
                    DB::table('service_contracts')
                        ->where('svcc_id', $service->svcc_id)
                        ->where('svcc_active', 1)
                        ->update([
                            'svcc_date_modified' => $now,
                            'svcc_modified_by' => $uid,
                            'svcc_active' => 0,
                        ]);
                }
            }
        });

        logUserActivity(
            'Manage Appointments',
            'Deleted appointment of ' . $service->usr_first_name . ' ' . $service->usr_last_name
        );

        session()->flash('successMessage', 'Appointment has been deleted.');
        return redirect()->back();
    }

    public function batch_delete_appointment(Request $request)
    {
        // Same permission rule as the delete button in the view
        if (!(session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')) {
            alert()->error('You are not allowed to perform this action.');
            return redirect()->back();
        }

        $ids = array_filter((array) $request->input('svc_ids', []), 'is_numeric');

        if (empty($ids)) {
            alert()->error('No appointments selected.');
            return redirect()->back();
        }

        $sessionBranchId = session('branch_id');

        $query = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->whereIn('services.svc_id', $ids)
            ->where('services.svc_active', 1)
            ->whereIn('services.svc_status', ['REQUESTED', 'CONFIRM ASSESSMENT']);

        // Same branch rule as requested_appointments()
        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $services = $query->select(
            'services.svc_id',
            'services.svcc_id',
            'users.usr_first_name',
            'users.usr_last_name'
        )->get();

        if ($services->isEmpty()) {
            alert()->error('Selected appointments not found.');
            return redirect()->back();
        }

        $validIds = $services->pluck('svc_id')->all();
        $contractIds = $services->pluck('svcc_id')->filter()->unique()->all();

        DB::transaction(function () use ($validIds, $contractIds) {
            $now = Carbon::now();
            $uid = session('usr_id');

            DB::table('services')
                ->whereIn('svc_id', $validIds)
                ->update([
                    'svc_date_deleted' => $now,
                    'svc_deleted_by' => $uid,
                    'svc_date_modified' => $now,
                    'svc_modified_by' => $uid,
                    'svc_active' => 0
                ]);

            DB::table('service_order_pests')
                ->whereIn('svc_id', $validIds)
                ->where('svcop_active', 1)
                ->update([
                    'svcop_date_modified' => $now,
                    'svcop_modified_by' => $uid,
                    'svcop_active' => 0,
                ]);

            DB::table('service_orders')
                ->whereIn('svc_id', $validIds)
                ->where('svco_active', 1)
                ->update([
                    'svco_date_modified' => $now,
                    'svco_modified_by' => $uid,
                    'svco_active' => 0,
                ]);

            DB::table('service_chemicals')
                ->whereIn('svc_id', $validIds)
                ->where('svcch_active', 1)
                ->update([
                    'svcch_date_modified' => $now,
                    'svcch_modified_by' => $uid,
                    'svcch_active' => 0,
                ]);

            DB::table('service_appointments')
                ->whereIn('svc_id', $validIds)
                ->where('svca_active', 1)
                ->update([
                    'svca_date_modified' => $now,
                    'svca_modified_by' => $uid,
                    'svca_active' => 0,
                ]);

            // Contracts: skipped entirely when none of the services has one
            foreach ($contractIds as $svccId) {
                $stillActive = DB::table('services')
                    ->where('svcc_id', $svccId)
                    ->where('svc_active', 1)
                    ->exists();

                // Only soft delete the contract when no active service remains under it
                if (!$stillActive) {
                    DB::table('service_contracts')
                        ->where('svcc_id', $svccId)
                        ->where('svcc_active', 1)
                        ->update([
                            'svcc_date_modified' => $now,
                            'svcc_modified_by' => $uid,
                            'svcc_active' => 0,
                        ]);
                }
            }
        });

        $names = $services->map(fn($s) => $s->usr_first_name . ' ' . $s->usr_last_name)->implode(', ');

        logUserActivity(
            'Manage Appointments',
            'Batch deleted ' . count($validIds) . ' appointment(s): ' . $names
        );

        session()->flash('successMessage', count($validIds) . ' appointment(s) have been deleted.');
        return redirect()->back();
    }

    public function deleted_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionBranchId = session('branch_id');

        $query = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('users as deleted_by', 'services.svc_deleted_by', '=', 'deleted_by.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'service_appointments.svc_id', '=', 'services.svc_id')
            ->leftJoin(
                'service_appointment_schedules',
                'service_appointment_schedules.svca_id',
                '=',
                'service_appointments.svca_id'
            )
            ->where('services.svc_active', 0);

        // Branch filter (same logic as users_active)
        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_date_deleted',
            'deleted_by.usr_first_name as deleted_by_first_name',
            'deleted_by.usr_last_name as deleted_by_last_name',
            'users.usr_first_name',
            'users.usr_last_name',
            'users.usr_email',
            'users.usr_mobile',
            'branches.branch_name',
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_email', 'LIKE', "%$search%")
                    ->orWhere('users.usr_mobile', 'LIKE', "%$search%")
                    ->orWhere('branches.branch_name', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('services.svc_date_created', 'asc');

        $appointments = $query->paginate(300);

        return view('service_orders.appointments.deleted.deleted', compact('appointments', 'search'));
    }

    public function deleted_appointments_view($svc_id)
    {
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->leftJoin('user_addresses', 'service_appointments.uadd_id', '=', 'user_addresses.uadd_id')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->leftJoin('users as approver', 'service_appointments.svca_approved_by', '=', 'approver.usr_id')
            ->leftJoin('service_appointment_schedules', 'service_appointments.svca_id', '=', 'service_appointment_schedules.svca_id')
            ->leftJoin('users as completer', 'services.svc_completed_by', '=', 'completer.usr_id')
            ->where('services.svc_id', $svc_id)
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_km_distance',
                'services.svc_property_type',
                'services.svc_is_package',
                'services.svcpat_id',
                'services.svc_is_termite',
                'services.svc_type_treatment',
                'services.svc_sqm_initial',
                'services.svc_sqm_final',
                'services.svc_with_device',
                'services.svc_device_count',
                'services.svc_problem_description',
                'services.svc_status',
                'services.svc_infestation',
                'services.svc_initial_price',
                'services.svc_location_price',
                'services.svc_device_price',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_payment_status',
                'services.svc_assessment_recommendation',
                'services.svc_rat_captured',
                'services.svc_snake_captured',
                'services.svc_lizard_captured',
                'services.svc_kitten_captured',
                'services.svc_other_captured',
                'services.svc_technician_recommendation',
                'services.svc_rating',
                'services.svc_date_rated',
                'services.svcc_id',
                'services.svc_date_completed',
                'services.svc_completed_by',
                'completer.usr_first_name as completed_first_name',
                'completer.usr_last_name as completed_last_name',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email',
                'users.usr_mobile',
                'branches.branch_id',
                'branches.branch_name',
                'service_appointments.svca_client_date',
                'service_appointments.svca_client_time',
                'service_appointments.svca_approved_date',
                'service_appointments.svca_approved_time_from',
                'service_appointments.svca_approved_time_to',
                'service_appointments.svca_date_approved',
                'service_appointment_schedules.svcas_id',
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_start_time',
                'service_appointment_schedules.svcas_end_date',
                'service_appointment_schedules.svcas_time_travel',
                'service_appointment_schedules.svcas_time_finished',
                'service_appointment_schedules.svcas_end_time',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'service_appointment_schedules.svcas_date_assigned',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        // Currently assigned technicians (one-to-many)
        $assignedTechs = DB::table('service_appointment_schedule_assigns')
            ->leftJoin('users as assigned_to', 'assigned_to.usr_id', '=', 'service_appointment_schedule_assigns.svcasa_assigned_to')
            ->where('service_appointment_schedule_assigns.svcas_id', $display->svcas_id)
            ->where('service_appointment_schedule_assigns.svcasa_active', 1)
            ->select(
                'assigned_to.usr_id',
                'assigned_to.usr_first_name',
                'assigned_to.usr_last_name'
            )
            ->get();

        $display->assigned_to_label = $assignedTechs->isEmpty()
            ? null
            : $assignedTechs->map(fn($t) => $t->usr_last_name . ', ' . $t->usr_first_name)->implode(', ');

        $pestTypes = DB::table('service_order_pests')
            ->leftJoin('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
            ->where('service_order_pests.svc_id', $svc_id)
            ->where('service_order_pests.svcop_active', 1)
            ->select(
                'service_order_pests.svcop_id',
                'service_packages.svcp_id',
                'service_packages.svcp_pest_type'
            )
            ->get();

        $serviceAreas = DB::table('service_orders')
            ->leftJoin('service_package_areas', 'service_orders.svcpa_id', '=', 'service_package_areas.svcpa_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_areas.svcpa_id',
                'service_package_areas.svcpa_area',
                'service_package_areas.svcpa_cost'
            )
            ->get();

        $termiteAreas = DB::table('service_orders')
            ->leftJoin('service_package_area_termites', 'service_orders.svcpat_id', '=', 'service_package_area_termites.svcpat_id')
            ->where('service_orders.svc_id', $svc_id)
            ->whereNotNull('service_orders.svcpat_id')
            ->where('service_orders.svco_active', 1)
            ->select(
                'service_orders.svco_id',
                'service_package_area_termites.svcpat_id',
                'service_package_area_termites.svcpat_sqm_details',
                'service_package_area_termites.svcpat_cost'
            )
            ->get();

        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        $technicianImages = DB::table('service_appointment_schedule_images')
            ->join('service_appointment_schedules', 'service_appointment_schedule_images.svcas_id', '=', 'service_appointment_schedules.svcas_id')
            ->join('service_appointments', 'service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_schedule_images.svcasp_active', 1)
            ->select('service_appointment_schedule_images.*')
            ->get();

        $contracts = DB::table('service_contracts')
            ->where('svc_id', $svc_id)
            ->where('svcc_active', 1)
            ->orderByDesc('svcc_date_created')
            ->get();

        $hasOwnContract = $contracts->isNotEmpty();

        if (!empty($display->svcc_id) && !$contracts->pluck('svcc_id')->contains($display->svcc_id)) {
            $linkedContract = DB::table('service_contracts')
                ->where('svcc_id', $display->svcc_id)
                ->where('svcc_active', 1)
                ->first();

            if ($linkedContract) {
                $contracts->push($linkedContract);
            }
        }

        foreach ($contracts as $contract) {
            $contract->generated_services = DB::table('services')
                ->where('svcc_id', $contract->svcc_id)

                ->orderBy('svc_id')
                ->select('svc_id', 'svc_sa_number', 'svc_status')
                ->get();
        }

        // Chemicals used for this service
        $serviceChemicals = DB::table('service_chemicals')
            ->join('chemicals', 'service_chemicals.chem_id', '=', 'chemicals.chem_id')
            ->where('service_chemicals.svc_id', $svc_id)
            ->where('service_chemicals.svcch_active', 1)
            ->select(
                'service_chemicals.svcch_id',
                'chemicals.chem_name',
                'service_chemicals.svcch_expected_quantity',
                'service_chemicals.svcch_actual_quantity',
                'service_chemicals.svcch_unit'
            )
            ->get();

        return view('service_orders.appointments.deleted.view_deleted', compact(
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'appointmentImages',
            'technicianImages',
            'contracts',
            'hasOwnContract',
            'serviceChemicals'
        ));
    }
    // END DELETED APPOINTMENTS

    // START UPDATE PRICE APPOINTMENTS
    public function update_price_appointment(Request $request)
    {
        $request->validate([
            'svc_id' => 'required|integer',
            'svc_final_price' => 'required|numeric|min:0',
        ]);

        $svc_id = $request->svc_id;

        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->select('svc_id', 'svc_sa_number', 'svc_final_price')
            ->first();

        if (!$service) {
            session()->flash('errorMessage', 'Service order not found.');
            return redirect()->back();
        }

        $oldPrice = (float) $service->svc_final_price;
        $newPrice = round((float) $request->svc_final_price, 2);

        if ($oldPrice === $newPrice) {
            session()->flash('errorMessage', 'No changes made. The final price is the same as the current one.');
            return redirect()->back();
        }

        DB::transaction(function () use ($svc_id, $oldPrice, $newPrice) {
            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update([
                    'svc_final_price' => $newPrice,
                ]);

            DB::table('user_price_change_logs')->insert([
                'upcl_uuid' => generateuuid(),
                'svc_id' => $svc_id,
                'upcl_old_final_price' => $oldPrice,
                'upcl_new_final_price' => $newPrice,
                'upcl_date_created' => Carbon::now(),
                'upcl_created_by' => session('usr_id'),
                'upcl_active' => 1,
            ]);
        });

        $serviceOrder = 'SA-' . str_pad($service->svc_sa_number, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Appointments',
            'Updated Final Price of ' . $serviceOrder . ' from ' . number_format($oldPrice, 2) . ' to ' . number_format($newPrice, 2)
        );

        session()->flash('successMessage', 'Final price updated successfully.');
        return redirect()->back();
    }
    // END UPDATE PRICE APPOINTMENTS
}