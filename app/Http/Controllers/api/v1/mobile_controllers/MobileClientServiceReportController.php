<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileClientServiceReportController extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'svc_id' => 'required|integer',
        ]);

        try {
            // ============================================================
            // CLIENT
            // ============================================================

            $client = DB::table('users')
                ->where('usr_email', trim($request->email))
                ->where('usr_active', 1)
                ->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found.',
                ], 404);
            }

            // ============================================================
            // SERVICE
            // Verify that this service belongs to the requesting client.
            // ============================================================

            $service = DB::table('services as svc')
                ->leftJoin(
                    'branches as branch',
                    'svc.branch_id',
                    '=',
                    'branch.branch_id'
                )
                ->where('svc.svc_id', $request->svc_id)
                ->where('svc.usr_id', $client->usr_id)
                ->where('svc.svc_active', 1)
                ->select(
                    'svc.svc_id',
                    'svc.svc_sa_number',
                    'svc.branch_id',
                    'svc.usr_id',
                    'svc.svc_is_package',
                    'svc.svcpat_id',
                    'svc.svc_is_termite',
                    'svc.svc_property_type',
                    'svc.svc_problem_description',
                    'svc.svc_status',
                    'svc.svc_infestation',
                    'svc.svc_type_treatment',
                    'svc.svc_sqm_initial',
                    'svc.svc_sqm_final',
                    'svc.svc_with_device',
                    'svc.svc_device_count',
                    'svc.svc_initial_price',
                    'svc.svc_final_price',
                    'svc.svc_balance',
                    'svc.svc_payment_status',
                    'svc.svc_assessment_recommendation',
                    'svc.svc_rat_captured',
                    'svc.svc_snake_captured',
                    'svc.svc_lizard_captured',
                    'svc.svc_kitten_captured',
                    'svc.svc_other_captured',
                    'svc.svc_technician_recommendation',
                    'svc.svc_rating',
                    'svc.svc_date_rated',
                    'svc.svc_date_completed',
                    'svc.svc_date_created',
                    'svc.svc_date_modified',
                    'svc.svc_modified_by',

                    'branch.branch_name',
                    'branch.branch_latitude',
                    'branch.branch_longitude'
                )
                ->first();

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service report not found.',
                ], 404);
            }

            if (
                strtoupper(
                    trim((string) $service->svc_status)
                ) !== 'DONE REPORT'
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'The technician has not submitted the service report yet.',
                ], 400);
            }

            // ============================================================
            // COMPLETED SCHEDULE + ITS REAL APPOINTMENT
            //
            // IMPORTANT:
            // Start from service_appointment_schedules, exactly like the
            // working technician job/report queries.
            //
            // Relationship:
            // sched.svca_id -> appt.svca_id -> appt.svc_id -> svc.svc_id
            //
            // This prevents us from first selecting an appointment that
            // may not be the appointment attached to the completed schedule.
            // ============================================================

            $reportJob = DB::table(
                'service_appointment_schedules as sched'
            )
                ->join(
                    'service_appointments as appt',
                    'sched.svca_id',
                    '=',
                    'appt.svca_id'
                )
                ->join(
                    'services as svc',
                    'appt.svc_id',
                    '=',
                    'svc.svc_id'
                )
                ->leftJoin(
                    'user_addresses as addr',
                    'appt.uadd_id',
                    '=',
                    'addr.uadd_id'
                )
                ->where(
                    'svc.svc_id',
                    $service->svc_id
                )
                ->where(
                    'svc.usr_id',
                    $client->usr_id
                )
                ->select(
                    // SCHEDULE
                    'sched.svcas_id',
                    'sched.svca_id',
                    'sched.svcas_uuid',
                    'sched.svcas_date_assigned',

                    'sched.svcas_start_date',
                    'sched.svcas_start_time',

                    'sched.svcas_end_date',
                    'sched.svcas_end_time',

                    'sched.svcas_time_travel',
                    'sched.svcas_time_finished',

                    'sched.svcas_date_created',
                    'sched.svcas_date_modified',
                    'sched.svcas_modified_by',
                    'sched.svcas_active',

                    // APPOINTMENT
                    'appt.svc_id',
                    'appt.uadd_id',

                    'appt.svca_client_date',
                    'appt.svca_client_time',

                    'appt.svca_approved_date',
                    'appt.svca_approved_time_from',
                    'appt.svca_approved_time_to',
                    'appt.svca_date_approved',

                    'appt.svca_status',
                    'appt.svca_date_created',
                    'appt.svca_date_modified',
                    'appt.svca_active',

                    // ADDRESS
                    'addr.uadd_street',
                    'addr.uadd_barangay',
                    'addr.uadd_city',
                    'addr.uadd_province',
                    'addr.uadd_region',
                    'addr.uadd_latitude',
                    'addr.uadd_longitude'
                )

                // First priority:
                // schedule that actually reached DONE SERVICE
                ->orderByRaw(
                    '
                    CASE
                        WHEN sched.svcas_time_finished IS NOT NULL
                        AND TRIM(sched.svcas_time_finished) <> ""
                        THEN 0
                        ELSE 1
                    END
                    '
                )

                // Second priority:
                // actual completion timestamp
                ->orderByDesc(
                    'sched.svcas_end_date'
                )
                ->orderByDesc(
                    'sched.svcas_end_time'
                )

                // Final fallback:
                // newest schedule
                ->orderByDesc(
                    'sched.svcas_id'
                )

                ->first();

            if (!$reportJob) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Completed service schedule not found.',
                ], 404);
            }

            // ============================================================
            // PEST TYPES
            // ============================================================

            $pests = DB::table(
                'service_order_pests as order_pest'
            )
                ->join(
                    'service_packages as package',
                    'order_pest.svcp_id',
                    '=',
                    'package.svcp_id'
                )
                ->where(
                    'order_pest.svc_id',
                    $service->svc_id
                )
                ->where(
                    'order_pest.svcop_active',
                    1
                )
                ->where(
                    'package.svcp_active',
                    1
                )
                ->pluck(
                    'package.svcp_pest_type'
                )
                ->filter()
                ->values();

            // ============================================================
            // SERVICE AREAS
            // ============================================================

            $areas = DB::table(
                'service_orders as service_order'
            )
                ->leftJoin(
                    'service_package_areas as area',
                    'service_order.svcpa_id',
                    '=',
                    'area.svcpa_id'
                )
                ->where(
                    'service_order.svc_id',
                    $service->svc_id
                )
                ->where(
                    'service_order.svco_active',
                    1
                )
                ->whereNotNull(
                    'service_order.svcpa_id'
                )
                ->where(
                    'area.svcpa_active',
                    1
                )
                ->pluck(
                    'area.svcpa_area'
                )
                ->filter()
                ->values();

            // ============================================================
            // ASSIGNED TECHNICIANS
            // Use the exact schedule that completed the service.
            // ============================================================

            $technicians = DB::table(
                'service_appointment_schedule_assigns as assign'
            )
                ->join(
                    'users as technician',
                    'assign.svcasa_assigned_to',
                    '=',
                    'technician.usr_id'
                )
                ->where(
                    'assign.svcas_id',
                    $reportJob->svcas_id
                )
                ->where(
                    'assign.svcasa_active',
                    1
                )
                ->where(
                    'technician.usr_active',
                    1
                )
                ->select(
                    'technician.usr_id',
                    'technician.usr_first_name',
                    'technician.usr_middle_name',
                    'technician.usr_last_name',
                    'technician.usr_mobile',
                    'technician.usr_email'
                )
                ->get();

            $teamTechnicians = $technicians
                ->map(function ($technician) {
                    return [
                        'id' =>
                            $technician->usr_id,

                        'name' =>
                            $this->buildFullName(
                                $technician->usr_first_name,
                                $technician->usr_middle_name,
                                $technician->usr_last_name
                            ),

                        'mobile' =>
                            $technician->usr_mobile,

                        'email' =>
                            $technician->usr_email,
                    ];
                })
                ->values();

            $technicianName = $teamTechnicians
                ->pluck('name')
                ->filter()
                ->implode(', ');

            // ============================================================
            // CHEMICALS
            // ============================================================

            $chemicals = DB::table(
                'service_chemicals as service_chemical'
            )
                ->leftJoin(
                    'chemicals as chemical',
                    'service_chemical.chem_id',
                    '=',
                    'chemical.chem_id'
                )
                ->where(
                    'service_chemical.svc_id',
                    $service->svc_id
                )
                ->where(
                    'service_chemical.svcch_active',
                    1
                )
                ->select(
                    'service_chemical.svcch_id',
                    'service_chemical.chem_id',
                    'chemical.chem_name',
                    'service_chemical.svcch_expected_quantity',
                    'service_chemical.svcch_actual_quantity',
                    'service_chemical.svcch_unit'
                )
                ->orderBy(
                    'service_chemical.svcch_id'
                )
                ->get()
                ->map(function ($chemical) {
                    return [
                        'id' =>
                            $chemical->svcch_id,

                        'chemicalId' =>
                            $chemical->chem_id,

                        'name' =>
                            $chemical->chem_name ??
                            'Chemical',

                        'expectedQuantity' =>
                            $chemical->svcch_expected_quantity,

                        'actualQuantity' =>
                            $chemical->svcch_actual_quantity,

                        'unit' =>
                            $chemical->svcch_unit,
                    ];
                })
                ->values();

            // ============================================================
            // TECHNICIAN SERVICE EVIDENCE
            // ============================================================

            $baseUrl =
                $request->getSchemeAndHttpHost();

            $images = DB::table(
                'service_appointment_schedule_images'
            )
                ->where(
                    'svcas_id',
                    $reportJob->svcas_id
                )
                ->where(function ($query) {
                    $query
                        ->where(
                            'svcasp_active',
                            1
                        )
                        ->orWhereNull(
                            'svcasp_active'
                        );
                })
                ->orderBy(
                    'svcasp_date_created'
                )
                ->pluck(
                    'svcasp_image'
                )
                ->filter()
                ->map(
                    function ($image) use (
                        $baseUrl
                    ) {
                        return
                            $baseUrl .
                            '/images/technician_images/' .
                            rawurlencode($image);
                    }
                )
                ->values();

            // ============================================================
            // LATEST ACTIVE PAYMENT
            // ============================================================

            $payment = DB::table(
                'service_payments as payment'
            )
                ->leftJoin(
                    'payment_types as type',
                    'payment.typ_id',
                    '=',
                    'type.typ_id'
                )
                ->where(
                    'payment.svc_id',
                    $service->svc_id
                )
                ->where(
                    'payment.spay_active',
                    1
                )
                ->select(
                    'payment.spay_id',
                    'payment.typ_id',
                    'type.typ_name',
                    'payment.spay_receipt_number',
                    'payment.spay_reference_number',
                    'payment.spay_date',
                    'payment.spay_amount',
                    'payment.spay_final_amount',
                    'payment.spay_status',
                    'payment.spay_details',
                    'payment.spay_admin_details',
                    'payment.spay_date_approved',
                    'payment.spay_image_proof',
                    'payment.spay_date_created',
                    'payment.spay_date_modified'
                )
                ->orderByDesc(
                    'payment.spay_id'
                )
                ->first();

            // ============================================================
            // FORMATTED VALUES
            // ============================================================

            $clientName =
                $this->buildFullName(
                    $client->usr_first_name,
                    $client->usr_middle_name,
                    $client->usr_last_name
                );

            $address =
                collect([
                    $reportJob->uadd_street ?? null,
                    $reportJob->uadd_barangay ?? null,
                    $reportJob->uadd_city ?? null,
                    $reportJob->uadd_province ?? null,
                ])
                    ->filter(function ($part) {
                        return
                            $part !== null &&
                            trim((string) $part) !== '';
                    })
                    ->implode(', ');

            $serviceName =
                $this->buildServiceName(
                    $service->svc_is_termite,
                    $pests
                );

            $fullPestTypes =
                $pests->implode(', ');

            $scheduleText =
                $this->formatSchedule(
                    $reportJob->svca_approved_date ??
                    $reportJob->svca_client_date,

                    $reportJob->svca_approved_time_from ??
                    $reportJob->svca_client_time,

                    $reportJob->svca_approved_time_to
                );

            // ============================================================
            // SERVICE DURATION
            //
            // Start Travel:
            // sched.svcas_start_date + sched.svcas_start_time
            //
            // Start Service:
            // Controller stores elapsed travel duration in
            // sched.svcas_time_travel.
            //
            // Done Service:
            // Controller stores:
            // sched.svcas_end_date
            // sched.svcas_end_time
            // sched.svcas_time_finished
            //
            // Service Time:
            // total duration - travel duration
            // ============================================================

            $travelTime =
                $reportJob->svcas_time_travel;

            $totalDuration =
                $reportJob->svcas_time_finished;

            $serviceTime =
                $this->computeServiceTime(
                    $totalDuration,
                    $travelTime
                );

            // submitReport() changes the service to DONE REPORT and writes
            // svc_date_modified = now(), so this is the current persisted
            // report-submission timestamp in the existing schema.
            $submittedDate =
                $service->svc_date_modified;

            // ============================================================
            // RESPONSE
            // ============================================================

            return response()->json([
                'success' => true,

                'data' => [
                    // IDs
                    'id' =>
                        $reportJob->svcas_id,

                    'serviceId' =>
                        $service->svc_id,

                    'svc_id' =>
                        $service->svc_id,

                    'appointment_id' =>
                        $reportJob->svca_id,

                    'appointmentId' =>
                        $reportJob->svca_id,

                    'scheduleId' =>
                        $reportJob->svcas_id,

                    'schedule_id' =>
                        $reportJob->svcas_id,

                    'svcas_id' =>
                        $reportJob->svcas_id,

                    // Client
                    'clientName' =>
                        $clientName !== ''
                            ? $clientName
                            : 'Client',

                    'usr_first_name' =>
                        $client->usr_first_name,

                    'usr_middle_name' =>
                        $client->usr_middle_name,

                    'usr_last_name' =>
                        $client->usr_last_name,

                    'usr_email' =>
                        $client->usr_email,

                    // Technician
                    'technicianName' =>
                        $technicianName !== ''
                            ? $technicianName
                            : null,

                    'technicianCount' =>
                        $teamTechnicians->count(),

                    'teamTechnicians' =>
                        $teamTechnicians,

                    // Service
                    'svc_sa_number' =>
                        $service->svc_sa_number,

                    'service' =>
                        $serviceName,

                    'serviceName' =>
                        $serviceName,

                    'pestType' =>
                        $fullPestTypes !== ''
                            ? $fullPestTypes
                            : null,

                    'fullPestTypes' =>
                        $fullPestTypes,

                    'pestTypes' =>
                        $pests,

                    'areaTypes' =>
                        $areas,

                    'svc_is_termite' =>
                        $service->svc_is_termite,

                    'svc_is_package' =>
                        $service->svc_is_package,

                    'svc_problem_description' =>
                        $service->svc_problem_description,

                    'svc_infestation' =>
                        $service->svc_infestation,

                    'svc_type_treatment' =>
                        $service->svc_type_treatment,

                    // Address
                    'address' =>
                        $address !== ''
                            ? $address
                            : null,

                    'uadd_street' =>
                        $reportJob->uadd_street,

                    'uadd_barangay' =>
                        $reportJob->uadd_barangay,

                    'uadd_city' =>
                        $reportJob->uadd_city,

                    'uadd_province' =>
                        $reportJob->uadd_province,

                    // Approved/client schedule
                    'schedule' =>
                        $scheduleText,

                    'scheduleDate' =>
                        $reportJob->svca_approved_date ??
                        $reportJob->svca_client_date,

                    'scheduleTime' =>
                        $reportJob->svca_approved_time_from ??
                        $reportJob->svca_client_time,

                    'svca_approved_date' =>
                        $reportJob->svca_approved_date,

                    'svca_approved_time_from' =>
                        $reportJob->svca_approved_time_from,

                    'svca_approved_time_to' =>
                        $reportJob->svca_approved_time_to,

                    // Actual technician timestamps
                    'startDate' =>
                        $reportJob->svcas_start_date,

                    'startTime' =>
                        $reportJob->svcas_start_time,

                    'endDate' =>
                        $reportJob->svcas_end_date,

                    'endTime' =>
                        $reportJob->svcas_end_time,

                    'svcas_start_date' =>
                        $reportJob->svcas_start_date,

                    'svcas_start_time' =>
                        $reportJob->svcas_start_time,

                    'svcas_end_date' =>
                        $reportJob->svcas_end_date,

                    'svcas_end_time' =>
                        $reportJob->svcas_end_time,

                    // Duration
                    'travelTime' =>
                        $travelTime,

                    'serviceTime' =>
                        $serviceTime,

                    'totalDuration' =>
                        $totalDuration,

                    'timeFinished' =>
                        $totalDuration,

                    'svcas_time_travel' =>
                        $travelTime,

                    'svcas_time_finished' =>
                        $totalDuration,

                    // Report
                    'submittedDate' =>
                        $submittedDate,

                    'status' =>
                        $service->svc_status,

                    'svc_status' =>
                        $service->svc_status,

                    'svca_status' =>
                        $reportJob->svca_status,

                    'ratCaptured' =>
                        (int) (
                            $service->svc_rat_captured ??
                            0
                        ),

                    'snakeCaptured' =>
                        (int) (
                            $service->svc_snake_captured ??
                            0
                        ),

                    'lizardCaptured' =>
                        (int) (
                            $service->svc_lizard_captured ??
                            0
                        ),

                    'kittenCaptured' =>
                        (int) (
                            $service->svc_kitten_captured ??
                            0
                        ),

                    'otherCaptured' =>
                        (int) (
                            $service->svc_other_captured ??
                            0
                        ),

                    'svc_rat_captured' =>
                        (int) (
                            $service->svc_rat_captured ??
                            0
                        ),

                    'svc_snake_captured' =>
                        (int) (
                            $service->svc_snake_captured ??
                            0
                        ),

                    'svc_lizard_captured' =>
                        (int) (
                            $service->svc_lizard_captured ??
                            0
                        ),

                    'svc_kitten_captured' =>
                        (int) (
                            $service->svc_kitten_captured ??
                            0
                        ),

                    'svc_other_captured' =>
                        (int) (
                            $service->svc_other_captured ??
                            0
                        ),

                    'recommendation' =>
                        $service
                            ->svc_technician_recommendation,

                    'svc_technician_recommendation' =>
                        $service
                            ->svc_technician_recommendation,

                    'chemicals' =>
                        $chemicals,

                    'images' =>
                        $images,

                    // Pricing/payment
                    'finalCost' =>
                        $service->svc_balance,

                    'svc_initial_price' =>
                        $service->svc_initial_price,

                    'svc_final_price' =>
                        $service->svc_final_price,

                    'svc_balance' =>
                        $service->svc_balance,

                    'svc_payment_status' =>
                        $service->svc_payment_status,

                    'paymentStatus' =>
                        $payment->spay_status ??
                        $service->svc_payment_status,

                    'paymentDate' =>
                        $payment->spay_date ??
                        null,

                    'spay_id' =>
                        $payment->spay_id ??
                        null,

                    'spay_status' =>
                        $payment->spay_status ??
                        null,

                    'spay_date' =>
                        $payment->spay_date ??
                        null,

                    'spay_amount' =>
                        $payment->spay_amount ??
                        null,

                    'spay_final_amount' =>
                        $payment->spay_final_amount ??
                        null,

                    'paymentType' =>
                        $payment->typ_name ??
                        null,

                    'payment' =>
                        $payment
                            ? [
                                'id' =>
                                    $payment->spay_id,

                                'typeId' =>
                                    $payment->typ_id,

                                'typeName' =>
                                    $payment->typ_name,

                                'receiptNumber' =>
                                    $payment
                                        ->spay_receipt_number,

                                'referenceNumber' =>
                                    $payment
                                        ->spay_reference_number,

                                'date' =>
                                    $payment->spay_date,

                                'amount' =>
                                    $payment->spay_amount,

                                'finalAmount' =>
                                    $payment
                                        ->spay_final_amount,

                                'status' =>
                                    $payment->spay_status,

                                'details' =>
                                    $payment->spay_details,

                                'adminDetails' =>
                                    $payment
                                        ->spay_admin_details,

                                'dateApproved' =>
                                    $payment
                                        ->spay_date_approved,

                                'proofImage' =>
                                    $payment
                                        ->spay_image_proof,
                            ]
                            : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error(
                'CLIENT SERVICE REPORT ERROR',
                [
                    'email' =>
                        $request->email ?? null,

                    'svc_id' =>
                        $request->svc_id ?? null,

                    'error' =>
                        $e->getMessage(),

                    'line' =>
                        $e->getLine(),

                    'file' =>
                        $e->getFile(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to load the client service report.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    // ================================================================
    // BUILD FULL NAME
    // ================================================================

    private function buildFullName(
        $firstName,
        $middleName,
        $lastName
    ) {
        return collect([
            $firstName,
            $middleName,
            $lastName,
        ])
            ->filter(function ($name) {
                return
                    $name !== null &&
                    trim((string) $name) !== '';
            })
            ->map(function ($name) {
                return ucwords(
                    strtolower(
                        trim((string) $name)
                    )
                );
            })
            ->implode(' ');
    }

    // ================================================================
    // BUILD SERVICE NAME
    // ================================================================

    private function buildServiceName(
        $isTermite,
        $pests
    ) {
        if ((int) $isTermite === 1) {
            return 'Termite Control';
        }

        $pestCount =
            collect($pests)->count();

        if ($pestCount === 1) {
            $pestName =
                collect($pests)->first();

            if (preg_match('/^others?(?:\s+pests?)?(?:\s+control)?$/i', trim((string) $pestName))) {
                return 'General Pest Control';
            }

            return
                $this->formatPestName(
                    $pestName
                ) .
                ' Control';
        }

        if ($pestCount > 1) {
            return 'General Pest Control';
        }

        return 'Pest Control Service';
    }

    // ================================================================
    // FORMAT PEST NAME
    // ================================================================

    private function formatPestName($pest)
    {
        if (!$pest) {
            return 'Pest';
        }

        $name =
            ucwords(
                strtolower(
                    trim((string) $pest)
                )
            );

        if (
            str_ends_with(
                strtolower($name),
                's'
            ) &&
            strlen($name) > 1
        ) {
            $name =
                substr(
                    $name,
                    0,
                    -1
                );
        }

        return $name;
    }

    // ================================================================
    // FORMAT SCHEDULE
    // ================================================================

    private function formatSchedule(
        $date,
        $timeFrom,
        $timeTo = null
    ) {
        $formattedDate =
            $this->formatDate($date);

        $formattedFrom =
            $this->formatTime($timeFrom);

        $formattedTo =
            $this->formatTime($timeTo);

        if (
            !$formattedDate &&
            !$formattedFrom
        ) {
            return null;
        }

        if (
            $formattedFrom &&
            $formattedTo
        ) {
            return
                $formattedDate .
                ' • ' .
                $formattedFrom .
                ' - ' .
                $formattedTo;
        }

        if ($formattedFrom) {
            return
                $formattedDate .
                ' • ' .
                $formattedFrom;
        }

        return $formattedDate;
    }

    // ================================================================
    // FORMAT DATE
    // ================================================================

    private function formatDate($date)
    {
        if (!$date) {
            return null;
        }

        $timestamp =
            strtotime((string) $date);

        if ($timestamp === false) {
            return (string) $date;
        }

        return date(
            'F j, Y',
            $timestamp
        );
    }

    // ================================================================
    // FORMAT TIME
    // ================================================================

    private function formatTime($time)
    {
        if (!$time) {
            return null;
        }

        $timestamp =
            strtotime((string) $time);

        if ($timestamp === false) {
            return (string) $time;
        }

        return date(
            'g:i A',
            $timestamp
        );
    }

    // ================================================================
    // COMPUTE SERVICE TIME
    //
    // Total duration includes travel time:
    // Service Time = Total Duration - Travel Time
    // ================================================================

    private function computeServiceTime(
        $totalDuration,
        $travelDuration
    ) {
        if (
            !$totalDuration ||
            !$travelDuration
        ) {
            return null;
        }

        $totalSeconds =
            $this->durationToSeconds(
                $totalDuration
            );

        $travelSeconds =
            $this->durationToSeconds(
                $travelDuration
            );

        if (
            $totalSeconds === null ||
            $travelSeconds === null
        ) {
            return null;
        }

        $serviceSeconds =
            max(
                0,
                $totalSeconds -
                $travelSeconds
            );

        return $this->secondsToDuration(
            $serviceSeconds
        );
    }

    // ================================================================
    // DURATION TO SECONDS
    // ================================================================

    private function durationToSeconds($duration)
    {
        if (!$duration) {
            return null;
        }

        $value =
            trim(
                (string) $duration
            );

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) round(
                (float) $value
            );
        }

        $parts =
            explode(
                ':',
                $value
            );

        if (count($parts) === 3) {
            return
                ((int) $parts[0] * 3600) +
                ((int) $parts[1] * 60) +
                (int) round(
                    (float) $parts[2]
                );
        }

        if (count($parts) === 2) {
            return
                ((int) $parts[0] * 3600) +
                ((int) $parts[1] * 60);
        }

        return null;
    }

    // ================================================================
    // SECONDS TO HH:MM:SS
    // ================================================================

    private function secondsToDuration(
        $totalSeconds
    ) {
        $totalSeconds =
            max(
                0,
                (int) $totalSeconds
            );

        $hours =
            intdiv(
                $totalSeconds,
                3600
            );

        $minutes =
            intdiv(
                $totalSeconds % 3600,
                60
            );

        $seconds =
            $totalSeconds % 60;

        return sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $seconds
        );
    }
}