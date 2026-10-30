<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\api\v1\mobile_controllers\MobileMapboxDistanceController;

class MobileServiceAppointmentController extends Controller
{
    public function clientBalance(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = DB::table('users')->where('usr_email', trim($request->email))->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        // Query services directly so multiple appointments never duplicate a balance.
        $query = DB::table('services')
            ->where('usr_id', $user->usr_id)
            ->where('svc_active', 1)
            ->whereNotNull('svc_final_price')
            ->whereNotIn('svc_status', ['CANCELLED', 'CANCELED', 'REJECTED']);
        $services = (clone $query)->where('svc_balance', '>', 0)
            ->orderByDesc('svc_id')
            ->get(['svc_id', 'svc_sa_number', 'svc_is_termite', 'svc_status', 'svc_balance',
                'svc_fixed_price', 'svc_location_price', 'svc_device_price', 'svc_final_price',
                'svc_km_distance']);
        $pendingPayments = DB::table('service_payments as p')
            ->leftJoin('payment_types as t', 'p.typ_id', '=', 't.typ_id')
            ->whereIn('p.svc_id', (clone $query)->select('svc_id'))
            ->where('spay_active', 1)
            ->where('spay_status', 'FOR APPROVAL')
            ->orderByDesc('spay_id')
            ->get(['p.svc_id', 'p.spay_id', 'p.spay_amount', 'p.spay_date', 't.typ_name']);
        foreach ($services as $service) {
            $service->has_pending_payment = $pendingPayments->contains('svc_id', $service->svc_id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'outstanding_balance' => round($services->sum('svc_balance'), 2),
                'service_count' => $services->count(),
                'pending_payment_count' => $pendingPayments->count(),
                'pending_payments' => $pendingPayments,
                'services' => $services,
            ],
        ]);
    }

    // Read-only estimate; booking submission recalculates travel using the same rules.
    public function estimateTravel(Request $request)
    {
        $request->validate(['email' => 'required|email', 'uadd_id' => 'required']);
        $user = DB::table('users')->where('usr_email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        $travel = $this->bookingTravelEstimate($user->usr_id, $request->uadd_id);
        if (isset($travel['error'])) {
            return response()->json(['success' => false, 'message' => $travel['error']], 400);
        }
        return response()->json(['success' => true, 'data' => [
            'travel_fee' => $travel['travel_fee'],
        ]]);
    }

    private function bookingTravelEstimate($userId, $addressId): array
    {
        $address = DB::table('user_addresses')
            ->where('uadd_id', $addressId)
            ->where('usr_id', $userId)
            ->first();

        if (!$address || !$address->uadd_latitude || !$address->uadd_longitude) {
            return ['error' => 'Selected address has no pinned map location.'];
        }

        $nearestBranch = null;
        $nearestDistance = null;

        $branches = DB::table('branches')
            ->whereNotNull('branch_latitude')
            ->whereNotNull('branch_longitude')
            ->get();

        foreach ($branches as $branch) {
            $distance = $this->calculateDistanceKm(
                $address->uadd_latitude,
                $address->uadd_longitude,
                $branch->branch_latitude,
                $branch->branch_longitude
            );

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestBranch = $branch;
            }
        }

        if (!$nearestBranch) {
            return ['error' => 'No available branch found for this location.'];
        }

        $drivingDistance = MobileMapboxDistanceController::getDrivingDistanceKm(
            $nearestBranch->branch_longitude,
            $nearestBranch->branch_latitude,
            $address->uadd_longitude,
            $address->uadd_latitude
        );

        $finalDistance = $drivingDistance ?? $nearestDistance;

        $locationRate = DB::table('service_package_area_locations')
            ->where('branch_id', $nearestBranch->branch_id)
            ->where('svcpal_active', 1)
            ->first();

        if (!$locationRate) {
            return ['error' => 'No location pricing found for this branch.'];
        }

        $deliveryFee = $this->travelFeeForDistance($finalDistance, (float) $locationRate->svcpal_first_cost, (float) $locationRate->svcpal_succeeding_cost);

        return ['branch' => $nearestBranch, 'distance' => $finalDistance, 'travel_fee' => $deliveryFee];
    }

    private function travelFeeForDistance($distance, float $baseCost, float $succeedingCost): float
    {
        $baseKm = 10;
        $distanceKm = (int) $distance;

        if ($distanceKm <= $baseKm) {
            $deliveryFee = $baseCost;
        } else {
            $excessKm = $distanceKm - $baseKm;
            $deliveryFee = $baseCost + ($excessKm * $succeedingCost);
        }

        return $deliveryFee;
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'uadd_id' => 'required',
            'client_date' => 'required|date',
            'client_time' => 'required',
            'initial_price' => 'nullable|numeric',
            'service_packages' => 'required',
            'service_areas' => 'nullable',
            'is_termite' => 'nullable|in:0,1',
            'termite_sqm_id' => 'nullable|integer',
            'images.*' => 'mimes:jpeg,jpg,png,webp|max:8192',
            'termite_sqm_input' => 'nullable|numeric|min:1',
        ]);

        DB::beginTransaction();

        try {
            $user = DB::table('users')
                ->where('usr_email', $request->email)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $servicePackages = json_decode($request->service_packages, true) ?? [];
            $serviceAreas = json_decode($request->service_areas, true) ?? [];
            $isTermite = $request->is_termite == 1;
            $termiteSqmId = $request->termite_sqm_id;
            $termiteSqmInput = $request->filled('termite_sqm_input')
                ? (float) $request->termite_sqm_input
                : null;

            $initialPrice = $request->filled('initial_price')
                ? (float) $request->initial_price
                : 0;

            if ($isTermite) {
                if (!$termiteSqmInput || !$termiteSqmId) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Termite property size is required.',
                    ], 400);
                }

                $termiteRate = DB::table('service_package_area_termites')
                    ->where('svcpat_id', $termiteSqmId)
                    ->where('svcpat_active', 1)
                    ->first();

                if (!$termiteRate) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid termite property size range.',
                    ], 400);
                }

                $rate = (float) $termiteRate->svcpat_cost;

                if ($termiteSqmInput >= 1 && $termiteSqmInput <= 50) {
                    $initialPrice = $rate;
                } else {
                    $initialPrice = $termiteSqmInput * $rate;
                }
            }
            $travel = $this->bookingTravelEstimate($user->usr_id, $request->uadd_id);
            if (isset($travel['error'])) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => $travel['error']], 400);
            }
            $nearestBranch = $travel['branch'];
            $finalDistance = $travel['distance'];
            $deliveryFee = $travel['travel_fee'];

            $totalInitialPrice = (float) $initialPrice + $deliveryFee;

            $serviceId = DB::table('services')->insertGetId([
                'svc_uuid' => generateuuid(),
                'branch_id' => $nearestBranch->branch_id,
                'usr_id' => $user->usr_id,
                'svc_is_package' => count($servicePackages) > 1 ? 1 : 0,
                'svcpat_id' => $isTermite ? $termiteSqmId : null,
                'svc_is_termite' => $isTermite ? 1 : 0,
                'svc_type_treatment' => null,
                'svc_sqm_initial' => $isTermite ? $termiteSqmInput : null,
                'svc_sqm_final' => null,
                'svc_with_device' => null,
                'svc_device_count' => null,
                'svc_problem_description' => strtoupper($request->problem_description),
                'svc_status' => 'REQUESTED',
                'svc_infestation' => null,
                'svc_initial_price' => $totalInitialPrice,
                'svc_km_distance' => $finalDistance,
                'svc_final_price' => null,
                'svc_balance' => $totalInitialPrice,
                'svc_payment_status' => 'NO PAYMENT',
                'svc_date_created' => now(),
                'svc_created_by' => $user->usr_id,
                'svc_date_modified' => null,
                'svc_modified_by' => null,
                'svc_active' => 1,
            ]);

            DB::table('services')
            ->where('svc_id', $serviceId)
            ->update([
                'svc_sa_number' => $serviceId,
            ]);

            foreach ($servicePackages as $package) {
                DB::table('service_order_pests')->insert([
                    'svcop_uuid' => generateuuid(),
                    'svc_id' => $serviceId,
                    'svcp_id' => $package['id'],
                    'svcop_date_created' => now(),
                    'svcop_created_by' => $user->usr_id,
                    'svcop_date_modified' => null,
                    'svcop_modified_by' => null,
                    'svcop_active' => 1,
                ]);
            }

            if ($isTermite && $termiteSqmId) {
                DB::table('service_orders')->insert([
                    'svco_uuid' => generateuuid(),
                    'svc_id' => $serviceId,
                    'svcpa_id' => null,
                    'svcpat_id' => $termiteSqmId,
                    'svco_date_created' => now(),
                    'svco_created_by' => $user->usr_id,
                    'svco_date_modified' => null,
                    'svco_modified_by' => null,
                    'svco_active' => 1,
                ]);
            } else {
                foreach ($serviceAreas as $area) {
                    DB::table('service_orders')->insert([
                        'svco_uuid' => generateuuid(),
                        'svc_id' => $serviceId,
                        'svcpa_id' => $area['id'],
                        'svcpat_id' => null,
                        'svco_date_created' => now(),
                        'svco_created_by' => $user->usr_id,
                        'svco_date_modified' => null,
                        'svco_modified_by' => null,
                        'svco_active' => 1,
                    ]);
                }
            }

            $appointmentId = DB::table('service_appointments')->insertGetId([
                'svca_uuid' => generateuuid(),
                'svc_id' => $serviceId,
                'uadd_id' => $request->uadd_id,
                'svca_client_date' => $request->client_date,
                'svca_client_time' => $request->client_time,
                'svca_status' => 'REQUESTED',
                'svca_approved_date' => null,
                'svca_approved_time_from' => null,
                'svca_approved_time_to' => null,
                'svca_date_approved' => null,
                'svca_approved_by' => null,
                'svca_date_created' => now(),
                'svca_created_by' => $user->usr_id,
                'svca_date_modified' => null,
                'svca_modified_by' => null,
                'svca_active' => 1,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $fileName = uniqid() . '_' . $image->getClientOriginalName();
                    $folderPath = public_path('images/client_images');

                    if (!file_exists($folderPath)) {
                        mkdir($folderPath, 0755, true);
                    }

                    $path = $folderPath . '/' . $fileName;
                    $ext = strtolower($image->getClientOriginalExtension());

                    if (in_array($ext, ['jpg', 'jpeg'])) {
                        $source = imagecreatefromjpeg($image->getPathname());
                        imagejpeg($source, $path, 75);
                        imagedestroy($source);
                    } elseif ($ext === 'png') {
                        $source = imagecreatefrompng($image->getPathname());

                        if ($source && imageistruecolor($source) === false) {
                            imagepalettetotruecolor($source);
                        }

                        if ($source) {
                            imagepng($source, $path, 7);
                            imagedestroy($source);
                        }
                    } elseif ($ext === 'webp') {
                        $source = imagecreatefromwebp($image->getPathname());
                        imagewebp($source, $path, 75);
                        imagedestroy($source);
                    }

                    DB::table('service_appointment_images')->insert([
                        'svcap_uuid' => generateuuid(),
                        'svca_id' => $appointmentId,
                        'svcap_image' => $fileName,
                        'svcap_date_created' => now(),
                        'svcap_created_by' => $user->usr_id,
                        'svcap_date_modified' => null,
                        'svcap_modified_by' => null,
                        'svcap_active' => 1,
                    ]);
                }
            }

            DB::commit();

            // CLIENT NOTIFICATION - REQUEST SUBMITTED
            try {
                $message =
                    'Your pest control service request has been submitted successfully.';

                DB::table('message_notifications')->insert([
                    'mesn_uuid' => generateuuid(),
                    'usr_id' => $user->usr_id,
                    'mesn_name' => strtoupper($message),
                    'mesn_date_created' => now(),
                    'mesn_created_by' => $user->usr_id,
                    'mesn_view' => '0',
                    'mesn_active' => 1,
                ]);
            } catch (\Exception $notificationException) {
                // Notification failure must not cancel a successful booking.
            }

            return response()->json([
                'success' => true,
                'message' => 'Service appointment request submitted successfully.',
                'svc_id' => $serviceId,
                'svca_id' => $appointmentId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unable to submit service appointment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTermiteAreaSizes($branchId)
    {
        $data = DB::table('service_package_area_termites')
            ->where('branch_id', $branchId)
            ->where('svcpat_active', 1)
            ->select(
                'svcpat_id',
                'svcpat_sqm_details',
                'svcpat_cost'
            )
            ->orderBy('svcpat_id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function clientAppointments(Request $request)
    {
        // ============================================================
        // VALIDATION
        // ============================================================

        $request->validate([
            'email' => 'required|email',
            'status' => 'required|in:REQUESTED,ASSESSED,SCHEDULED,ONGOING,DONE REPORT,COMPLETED',
            'page' => 'nullable|integer|min:1',
            'target_svc_id' => 'nullable|integer',
        ]);

        try {
            // ============================================================
            // CLIENT
            // ============================================================

            $user = DB::table('users')
                ->where('usr_email', trim($request->email))
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                    'data' => [],
                ], 404);
            }

            // ============================================================
            // PAGINATION
            // ============================================================

            $page = max(
                1,
                (int) ($request->page ?? 1)
            );

            $perPage = 3;

            // ============================================================
            // APPOINTMENT QUERY
            // ============================================================

            $appointmentsQuery = DB::table(
                'service_appointments as sa'
            )
                ->join(
                    'services as s',
                    'sa.svc_id',
                    '=',
                    's.svc_id'
                )
                ->leftJoin(
                    'user_addresses as ua',
                    'sa.uadd_id',
                    '=',
                    'ua.uadd_id'
                )
                ->leftJoin(
                    'branches as b',
                    's.branch_id',
                    '=',
                    'b.branch_id'
                )
                ->leftJoin(
                    'service_appointment_schedules as sched',
                    'sa.svca_id',
                    '=',
                    'sched.svca_id'
                )
                ->leftJoin(
                    'service_package_area_termites as spat',
                    's.svcpat_id',
                    '=',
                    'spat.svcpat_id'
                )
                ->leftJoin(
                    'service_payments as spay',
                    function ($join) {
                        $join
                            ->on(
                                's.svc_id',
                                '=',
                                'spay.svc_id'
                            )
                            ->where(
                                'spay.spay_active',
                                1
                            )
                            ->whereRaw(
                                'spay.spay_id = (
                                    SELECT MAX(spay2.spay_id)
                                    FROM service_payments as spay2
                                    WHERE spay2.svc_id = s.svc_id
                                    AND spay2.spay_active = 1
                                )'
                            );
                    }
                )
                ->where(
                    's.usr_id',
                    $user->usr_id
                )
                ->where(
                    'sa.svca_active',
                    1
                )
                ->where(
                    's.svc_active',
                    1
                )
                ->where(
                    's.svc_status',
                    '!=',
                    'DELETED'
                )

                // ========================================================
                // LOAD ONLY THE REQUESTED TAB
                // ========================================================

                ->when(
                    $request->status === 'COMPLETED',
                    function ($query) {
                        $query->whereIn(
                            's.svc_status',
                            [
                                'DONE SERVICE',
                                'DONE REPORT',
                            ]
                        );
                    },
                    function ($query) use ($request) {
                        $query->where(
                            's.svc_status',
                            $request->status
                        );
                    }
                )

                ->select(
                    'sa.svca_id',
                    'sa.svc_id',
                    'sa.uadd_id',

                    's.svc_status as svca_status',
                    's.svc_sa_number',

                    'sa.svca_client_date',
                    'sa.svca_client_time',

                    // Approved schedule
                    'sa.svca_approved_date',
                    'sa.svca_approved_time_from',
                    'sa.svca_approved_time_to',
                    'sa.svca_date_approved',

                    'sa.svca_date_created',

                    's.svc_is_termite',
                    's.svc_balance',
                    's.svc_fixed_price',
                    's.svc_location_price',
                    's.svc_device_price',
                    's.svc_final_price',
                    's.svc_km_distance',
                    's.svc_rating',
                    's.svc_date_rated',

                    'ua.uadd_street',
                    'ua.uadd_barangay',
                    'ua.uadd_city',
                    'ua.uadd_province',
                    'ua.uadd_latitude',
                    'ua.uadd_longitude',

                    'b.branch_id',
                    'b.branch_name',
                    'b.branch_latitude',
                    'b.branch_longitude',

                    'sched.svcas_id',
                    'sched.svcas_start_date',
                    'sched.svcas_start_time',
                    'sched.svcas_modified_by',

                    'spat.svcpat_sqm_details',

                    'spay.spay_id',
                    'spay.typ_id',
                    'spay.spay_status',
                    'spay.spay_amount',
                    'spay.spay_date'
                )
                ->when(
                    $request->status === 'COMPLETED' &&
                    $request->filled('target_svc_id'),

                    function ($query) use ($request) {
                        $targetServiceId =
                            (int) $request->target_svc_id;

                        $query
                            ->orderByRaw(
                                'CASE WHEN s.svc_id = ? THEN 0 ELSE 1 END',
                                [$targetServiceId]
                            )
                            ->orderByDesc(
                                'sa.svca_date_created'
                            );
                    },

                    function ($query) {
                        $query->orderByDesc(
                            'sa.svca_date_created'
                        );
                    }
                );

            // ============================================================
            // SERVER-SIDE PAGINATION
            // ============================================================

            $paginator = $appointmentsQuery
                ->simplePaginate(
                    $perPage,
                    ['*'],
                    'page',
                    $page
                );

            $appointments = collect(
                $paginator->items()
            );

            // ============================================================
            // GET SERVICE IDS FOR CURRENT 3 RECORDS
            // ============================================================

            $serviceIds = $appointments
                ->pluck('svc_id')
                ->filter()
                ->unique()
                ->values();

            // ============================================================
            // GET SCHEDULE IDS FOR CURRENT RECORDS
            // ============================================================

            $scheduleIds = $appointments
                ->pluck('svcas_id')
                ->filter()
                ->unique()
                ->values();

            // ============================================================
            // PRELOAD PESTS - ONE QUERY
            // ============================================================

            $pestsByService = collect();

            if ($serviceIds->isNotEmpty()) {
                $pestsByService = DB::table(
                    'service_order_pests as sop'
                )
                    ->join(
                        'service_packages as sp',
                        'sop.svcp_id',
                        '=',
                        'sp.svcp_id'
                    )
                    ->whereIn(
                        'sop.svc_id',
                        $serviceIds
                    )
                    ->where(
                        'sop.svcop_active',
                        1
                    )
                    ->where(
                        'sp.svcp_active',
                        1
                    )
                    ->select(
                        'sop.svc_id',
                        'sp.svcp_pest_type'
                    )
                    ->get()
                    ->groupBy('svc_id');
            }

            // ============================================================
            // PRELOAD AREAS - ONE QUERY
            // ============================================================

            $areasByService = collect();

            if ($serviceIds->isNotEmpty()) {
                $areasByService = DB::table(
                    'service_orders as so'
                )
                    ->join(
                        'service_package_areas as spa',
                        'so.svcpa_id',
                        '=',
                        'spa.svcpa_id'
                    )
                    ->whereIn(
                        'so.svc_id',
                        $serviceIds
                    )
                    ->where(
                        'so.svco_active',
                        1
                    )
                    ->where(
                        'spa.svcpa_active',
                        1
                    )
                    ->select(
                        'so.svc_id',
                        'spa.svcpa_area'
                    )
                    ->get()
                    ->groupBy('svc_id');
            }

            // ============================================================
            // PRELOAD TECHNICIANS - ONE QUERY
            // ============================================================

            $techniciansBySchedule = collect();

            if ($scheduleIds->isNotEmpty()) {
                $techniciansBySchedule = DB::table(
                    'service_appointment_schedule_assigns as assign'
                )
                    ->join(
                        'users as tech',
                        'assign.svcasa_assigned_to',
                        '=',
                        'tech.usr_id'
                    )
                    ->whereIn(
                        'assign.svcas_id',
                        $scheduleIds
                    )
                    ->where(
                        'assign.svcasa_active',
                        1
                    )
                    ->select(
                        'assign.svcas_id',
                        'tech.usr_id',
                        'tech.usr_first_name',
                        'tech.usr_last_name',
                        'tech.usr_image_path'
                    )
                    ->get()
                    ->groupBy('svcas_id');
            }

            // ============================================================
            // ATTACH PESTS AND AREAS
            // ============================================================

            $appointments->transform(
                function ($appointment) use (
                    $pestsByService,
                    $areasByService,
                    $techniciansBySchedule
                ) {
                    // ====================================================
                    // PEST TYPES
                    // ====================================================

                    $pests = collect(
                        $pestsByService->get(
                            $appointment->svc_id,
                            collect()
                        )
                    )
                        ->pluck('svcp_pest_type')
                        ->filter()
                        ->values()
                        ->toArray();

                    $appointment->fullPestTypes =
                        implode(', ', $pests);

                    // ====================================================
                    // SERVICE AREAS
                    // ====================================================

                    $areas = collect(
                        $areasByService->get(
                            $appointment->svc_id,
                            collect()
                        )
                    )
                        ->pluck('svcpa_area')
                        ->filter()
                        ->values()
                        ->toArray();

                    $appointment->areaTypes =
                        implode(', ', $areas);

                    // ====================================================
                    // ASSIGNED TECHNICIANS
                    // ====================================================

                    $assignedTechnicians = collect(
                        $techniciansBySchedule->get(
                            $appointment->svcas_id,
                            collect()
                        )
                    );

                    $technicianCount =
                        $assignedTechnicians->count();

                    $teamTechnicians =
                        $assignedTechnicians
                            ->map(
                                function ($tech) {
                                    return [
                                        'id' =>
                                            $tech->usr_id,

                                        'name' =>
                                            trim(
                                                ucwords(
                                                    strtolower(
                                                        $tech->usr_first_name ?? ''
                                                    )
                                                ) .
                                                ' ' .
                                                ucwords(
                                                    strtolower(
                                                        $tech->usr_last_name ?? ''
                                                    )
                                                )
                                            ),

                                        'profileImage' =>
                                            $tech->usr_image_path
                                                ?? null,
                                    ];
                                }
                            )
                            ->values();

                    $appointment->technicianCount =
                        $technicianCount;

                    $appointment->isMultipleTechnician =
                        $technicianCount > 1;

                    $appointment->teamTechnicians =
                        $teamTechnicians;

                    // The technician who successfully started travel is the
                    // team leader used by both technician and client tracking.
                    $appointment->leaderId =
                        $appointment->svcas_modified_by;

                    return $appointment;
                }
            );

            // ============================================================
            // RESPONSE
            // ============================================================

            return response()->json([
                'success' => true,

                'status' => $request->status,

                'pagination' => [
                    'current_page' =>
                        $page,

                    'per_page' =>
                        $perPage,

                    'has_more' =>
                        $paginator->hasMorePages(),

                    'next_page' =>
                        $paginator->hasMorePages()
                            ? $page + 1
                            : null,
                ],

                'count' =>
                    $appointments->count(),

                'data' =>
                    $appointments->values(),
            ]);

        } catch (\Exception $e) {
            \Log::error(
                'CLIENT APPOINTMENTS ERROR',
                [
                    'email' =>
                        $request->email ?? null,

                    'status' =>
                        $request->status ?? null,

                    'page' =>
                        $request->page ?? null,

                    'error' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to load appointments.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }
    
    public function clientAppointmentDetails(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'svc_id' => 'required|integer',
        ]);

        try {

            // ============================================================
            // CLIENT
            // ============================================================

            $user = DB::table('users')
                ->where(
                    'usr_email',
                    trim($request->email)
                )
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }


            // ============================================================
            // APPOINTMENT DETAILS
            // ============================================================

            $appointment = DB::table(
                'service_appointments as sa'
            )
                ->join(
                    'services as s',
                    'sa.svc_id',
                    '=',
                    's.svc_id'
                )
                ->leftJoin(
                    'users as client',
                    's.usr_id',
                    '=',
                    'client.usr_id'
                )
                ->leftJoin(
                    'user_addresses as ua',
                    'sa.uadd_id',
                    '=',
                    'ua.uadd_id'
                )
                ->leftJoin(
                    'branches as b',
                    's.branch_id',
                    '=',
                    'b.branch_id'
                )
                ->leftJoin(
                    'service_package_area_termites as spat',
                    's.svcpat_id',
                    '=',
                    'spat.svcpat_id'
                )

                // Make sure this service belongs to logged-in client
                ->where(
                    's.usr_id',
                    $user->usr_id
                )

                ->where(
                    's.svc_id',
                    $request->svc_id
                )

                ->where(
                    's.svc_active',
                    1
                )

                ->where(
                    'sa.svca_active',
                    1
                )

                ->select(  
                     // CLIENT
                    'client.usr_first_name',
                    'client.usr_last_name',
                    'client.usr_email',

                    // SERVICE APPOINTMENT
                    'sa.svca_id',
                    'sa.svc_id',
                    'sa.uadd_id',

                    'sa.svca_client_date',
                    'sa.svca_client_time',

                    'sa.svca_approved_date',
                    'sa.svca_approved_time_from',
                    'sa.svca_approved_time_to',

                    'sa.svca_date_created',
                    'sa.svca_date_approved',
                    'sa.svca_status',


                    // SERVICE
                    's.svc_sa_number',
                    's.svc_status',

                    's.svc_is_termite',
                    's.svc_is_package',

                    's.svc_problem_description',

                    's.svc_infestation',
                    's.svc_type_treatment',

                    's.svc_sqm_initial',
                    's.svc_sqm_final',

                    's.svc_with_device',
                    's.svc_device_count',

                    's.svc_initial_price',
                    's.svc_final_price',
                    's.svc_balance',

                    's.svc_payment_status',

                    's.svc_km_distance',

                    's.svc_date_created',
                    's.svc_date_modified',


                    // ADDRESS
                    'ua.uadd_street',
                    'ua.uadd_barangay',
                    'ua.uadd_city',
                    'ua.uadd_province',
                    'ua.uadd_latitude',
                    'ua.uadd_longitude',


                    // BRANCH
                    'b.branch_id',
                    'b.branch_name',
                    'b.branch_latitude',
                    'b.branch_longitude',


                    // TERMITE
                    'spat.svcpat_sqm_details'
                )
                ->first();


            // ============================================================
            // NOT FOUND
            // ============================================================

            if (!$appointment) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Appointment not found.',
                ], 404);
            }


            // ============================================================
            // PEST TYPES
            // ============================================================

            $pests = DB::table(
                'service_order_pests as sop'
            )
                ->join(
                    'service_packages as sp',
                    'sop.svcp_id',
                    '=',
                    'sp.svcp_id'
                )

                ->where(
                    'sop.svc_id',
                    $appointment->svc_id
                )

                ->where(
                    'sop.svcop_active',
                    1
                )

                ->where(
                    'sp.svcp_active',
                    1
                )

                ->pluck(
                    'sp.svcp_pest_type'
                )
                ->filter()
                ->values();


            // ============================================================
            // SERVICE AREAS
            // ============================================================

            $areas = DB::table(
                'service_orders as so'
            )
                ->join(
                    'service_package_areas as spa',
                    'so.svcpa_id',
                    '=',
                    'spa.svcpa_id'
                )

                ->where(
                    'so.svc_id',
                    $appointment->svc_id
                )

                ->where(
                    'so.svco_active',
                    1
                )

                ->where(
                    'spa.svcpa_active',
                    1
                )

                ->pluck(
                    'spa.svcpa_area'
                )
                ->filter()
                ->values();


            // ============================================================
            // APPOINTMENT IMAGES
            // ============================================================

            $images = DB::table(
                'service_appointment_images'
            )
                ->where(
                    'svca_id',
                    $appointment->svca_id
                )

                ->where(
                    'svcap_active',
                    1
                )

                ->select(
                    'svcap_id',
                    'svcap_image'
                )

                ->orderBy(
                    'svcap_id'
                )

                ->get();


            // ============================================================
            // ATTACH RELATED INFORMATION
            // ============================================================

            $appointment->pestTypes =
                $pests->toArray();

            $appointment->areaTypes =
                $areas->toArray();

            $appointment->images =
                $images;


            // ============================================================
            // RESPONSE
            // ============================================================

            return response()->json([
                'success' => true,
                'data' => $appointment,
            ]);


        } catch (\Exception $e) {

            \Log::error(
                'CLIENT APPOINTMENT DETAILS ERROR',
                [
                    'email' =>
                        $request->email ?? null,

                    'svc_id' =>
                        $request->svc_id ?? null,

                    'error' =>
                        $e->getMessage(),
                ]
            );


            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to load appointment details.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    // RATE COMPLETED SERVICE
    public function rateService(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'svc_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        try {
            $user = DB::table('users')
                ->where('usr_email', trim($request->email))
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            $service = DB::table('services')
                ->where('svc_id', $request->svc_id)
                ->where('usr_id', $user->usr_id)
                ->where('svc_active', 1)
                ->first();

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found.',
                ], 404);
            }

            if (!in_array(
                strtoupper((string) $service->svc_status),
                ['DONE SERVICE', 'DONE REPORT']
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'This service is not yet available for rating.',
                ], 400);
            }

            if ($service->svc_rating !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'This service has already been rated.',
                ], 400);
            }

            DB::table('services')
                ->where('svc_id', $service->svc_id)
                ->update([
                    'svc_rating' => $request->rating,
                    'svc_date_rated' => now(),
                    'svc_date_modified' => now(),
                    'svc_modified_by' => $user->usr_id,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for rating your service.',
                'rating' => (int) $request->rating,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to submit service rating.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateDistanceKm($lat1, $lon1, $lat2, $lon2)
    {
        $lat1 = (float) $lat1;
        $lon1 = (float) $lon1;
        $lat2 = (float) $lat2;
        $lon2 = (float) $lon2;

        $earthRadius = 6371;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return round($earthRadius * $angle);
    }

    public function clientTrackingLocation(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer',
        ]);

        $tracking = DB::table('service_appointment_schedules as sched')
            ->join('service_appointments as sa', 'sched.svca_id', '=', 'sa.svca_id')
            ->join('services as s', 'sa.svc_id', '=', 's.svc_id')
            ->leftJoin('service_appointment_locations as loc', function ($join) {
                $join->on('sched.svcas_id', '=', 'loc.svcas_id')
                    ->where('loc.svcal_active', 1);
            })
            ->where('sched.svcas_id', $request->schedule_id)
            ->select(
                'sched.svcas_id',
                'sa.svca_status',
                's.svc_status',
                'loc.svcal_latitude',
                'loc.svcal_longitude',
                'loc.svcal_date_modified'
            )
            ->first();

        if (!$tracking) {
            return response()->json([
                'success' => false,
                'message' => 'Tracking schedule not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'schedule_id' => $tracking->svcas_id,
            'status' => $tracking->svca_status ?? $tracking->svc_status,
            'serviceStatus' => $tracking->svc_status,
            'technicianLatitude' => $tracking->svcal_latitude,
            'technicianLongitude' => $tracking->svcal_longitude,
            'lastUpdated' => $tracking->svcal_date_modified,
        ]);
    }

    public function paymentTypes()
    {
        $paymentTypes = DB::table('payment_types')
            ->where('typ_active', 1)
            ->select('typ_id', 'typ_name')
            ->orderBy('typ_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentTypes,
        ]);
    }
}
