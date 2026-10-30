<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class MobileTechnicianJobController extends Controller
{
    public function assignedJobs(Request $request)
    {
        $request->validate([
            'email' => 'required|email',    
            'filter' => 'required|in:active,upcoming,completed',
            'page' => 'nullable|integer|min:1',
        ]);

        try {
            // ============================================================
            // TECHNICIAN
            // ============================================================

            $technician = DB::table('users')
                ->where('usr_email', trim($request->email))
                ->first();

            if (!$technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician not found.',
                ], 404);
            }

            $filter = strtolower(trim($request->filter));
            $page = max(1, (int) ($request->page ?? 1));

            // Fixed page size for mobile.
            $perPage = 3;

            $currentAt = now();
            $today = $currentAt->toDateString();
            $currentTime = $currentAt->format('H:i:s');

            // ============================================================
            // BASE QUERY
            // ============================================================

            $query = DB::table('service_appointment_schedules as sched')
                ->leftJoin(
                    'service_appointments as appt',
                    'sched.svca_id',
                    '=',
                    'appt.svca_id'
                )
                ->leftJoin(
                    'services as svc',
                    'appt.svc_id',
                    '=',
                    'svc.svc_id'
                )
                ->leftJoin(
                    'branches as branch',
                    'svc.branch_id',
                    '=',
                    'branch.branch_id'
                )
                ->leftJoin(
                    'user_addresses as addr',
                    'appt.uadd_id',
                    '=',
                    'addr.uadd_id'
                )
                ->leftJoin(
                    'users as client',
                    'addr.usr_id',
                    '=',
                    'client.usr_id'
                )
                ->join(
                    'service_appointment_schedule_assigns as assign',
                    'sched.svcas_id',
                    '=',
                    'assign.svcas_id'
                )
                ->where(
                    'assign.svcasa_assigned_to',
                    $technician->usr_id
                )
                ->where('assign.svcasa_active', 1)
                ->where('appt.svca_active', 1)
                ->where('sched.svcas_active', 1)
                ->where('svc.svc_active', 1);

            // ============================================================
            // FILTER PER TAB
            // ============================================================

            if ($filter === 'active') {
                $query
                    ->where(function ($activeQuery) use ($today, $currentTime) {
                        $activeQuery
                            ->whereIn('appt.svca_status', [
                                'ONGOING',
                                'ARRIVED',
                                'DONE SERVICE',
                            ])
                            ->orWhere(function ($assignedQuery) use ($today, $currentTime) {
                                $assignedQuery
                                    ->where('appt.svca_status', 'ASSIGNED')
                                    ->where(function ($classificationQuery) use ($today, $currentTime) {
                                        $classificationQuery
                                            ->where(function ($windowQuery) use ($today, $currentTime) {
                                                $this->applyAssignedScheduleFilter(
                                                    $windowQuery,
                                                    'service_now',
                                                    $today,
                                                    $currentTime
                                                );
                                            })
                                            ->orWhere(function ($windowQuery) use ($today, $currentTime) {
                                                $this->applyAssignedScheduleFilter(
                                                    $windowQuery,
                                                    'overdue',
                                                    $today,
                                                    $currentTime
                                                );
                                            })
                                            ->orWhere(function ($windowQuery) {
                                                $this->applyAssignedScheduleFilter(
                                                    $windowQuery,
                                                    'schedule_missing'
                                                );
                                            });
                                    });
                            });
                    })
                    ->orderByRaw("
                        CASE
                            WHEN appt.svca_status = 'ONGOING' THEN 1
                            WHEN appt.svca_status = 'ARRIVED' THEN 2
                            WHEN appt.svca_status = 'DONE SERVICE' THEN 3
                            WHEN appt.svca_status = 'ASSIGNED'
                                AND DATE(appt.svca_approved_date) = ?
                                AND TIME(appt.svca_approved_time_from) <= ?
                                AND TIME(appt.svca_approved_time_to) >= ? THEN 4
                            WHEN appt.svca_status = 'ASSIGNED' THEN 5
                            ELSE 5
                        END
                    ", [$today, $currentTime, $currentTime])
                    ->orderBy(
                        'appt.svca_approved_date',
                        'asc'
                    )
                    ->orderBy(
                        'appt.svca_approved_time_from',
                        'asc'
                    );
            }

            if ($filter === 'upcoming') {
                $query
                    ->where(function ($windowQuery) use ($today, $currentTime) {
                        $this->applyAssignedScheduleFilter(
                            $windowQuery,
                            'upcoming',
                            $today,
                            $currentTime
                        );
                    })
                    ->orderBy(
                        'appt.svca_approved_date',
                        'asc'
                    )
                    ->orderBy(
                        'appt.svca_approved_time_from',
                        'asc'
                    );
            }

            if ($filter === 'completed') {
                $query
                    ->where(
                        'appt.svca_status',
                        'DONE REPORT'
                    )
                    ->orderBy(
                        'appt.svca_approved_date',
                        'desc'
                    )
                    ->orderBy(
                        'appt.svca_approved_time_from',
                        'desc'
                    );
            }

            // ============================================================
            // SELECT
            // ============================================================

            $query->select(
                'sched.svcas_id',
                'sched.svcas_uuid',
                'sched.svca_id',
                'sched.svcas_date_assigned',
                'sched.svcas_start_date',
                'sched.svcas_start_time',
                'sched.svcas_end_date',
                'sched.svcas_end_time',
                'sched.svcas_time_travel',
                'sched.svcas_time_finished',
                'sched.svcas_modified_by',

                'appt.svca_status',
                'appt.svc_id',
                'appt.uadd_id',
                'appt.svca_approved_date',
                'appt.svca_approved_time_from',
                'appt.svca_approved_time_to',
                
                'svc.svc_sa_number',
                'svc.svc_is_termite',
                'svc.svcpat_id',
                'svc.svc_problem_description',
                'svc.svc_assessment_recommendation',

                'client.usr_id as client_id',
                'client.usr_first_name',
                'client.usr_middle_name',
                'client.usr_last_name',
                'client.usr_mobile',
                'client.usr_image_path as client_image_path',

                'addr.uadd_street',
                'addr.uadd_barangay',
                'addr.uadd_city',
                'addr.uadd_province',
                'addr.uadd_latitude',
                'addr.uadd_longitude',

                'branch.branch_id',
                'branch.branch_name',
                'branch.branch_latitude',
                'branch.branch_longitude'
            );

            // ============================================================
            // SERVER-SIDE PAGINATION
            // Loads only 3 jobs for the requested tab/page.
            // simplePaginate avoids the extra total-count query.
            // ============================================================

            $paginator = $query->simplePaginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

            $jobs = collect(
                $paginator->items()
            );

            // ============================================================
            // PRELOAD RELATED DATA FOR ONLY THESE 3 JOBS
            // ============================================================

            $serviceIds = $jobs
                ->pluck('svc_id')
                ->filter()
                ->unique()
                ->values();

            $scheduleIds = $jobs
                ->pluck('svcas_id')
                ->filter()
                ->unique()
                ->values();

            $appointmentIds = $jobs
                ->pluck('svca_id')
                ->filter()
                ->unique()
                ->values();

            // ============================================================
            // PESTS - ONE QUERY
            // ============================================================

            $pestsByService = collect();

            if ($serviceIds->isNotEmpty()) {
                $pestsByService =
                    DB::table(
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
            // CLIENT BOOKING IMAGES - ONE QUERY
            // ============================================================

            $imagesByAppointment = collect();

            if ($appointmentIds->isNotEmpty()) {
                $imagesByAppointment =
                    DB::table(
                        'service_appointment_images as img'
                    )
                    ->whereIn(
                        'img.svca_id',
                        $appointmentIds
                    )
                    ->where(
                        'img.svcap_active',
                        1
                    )
                    ->select(
                        'img.svca_id',
                        'img.svcap_image'
                    )
                    ->orderBy(
                        'img.svcap_id',
                        'asc'
                    )
                    ->get()
                    ->groupBy('svca_id');
            }

            // ============================================================
            // TECHNICIANS - ONE QUERY
            // ============================================================

            $techniciansBySchedule = collect();

            if ($scheduleIds->isNotEmpty()) {
                $techniciansBySchedule =
                    DB::table(
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
            // FORMAT ONLY CURRENT PAGE
            // ============================================================

            $formattedJobs = $jobs
                ->map(
                    function ($job) use (
                        $technician,
                        $pestsByService,
                        $techniciansBySchedule,
                        $imagesByAppointment,
                        $currentAt
                    ) {
                        // CLIENT
                        $firstName = ucwords(
                            strtolower(
                                $job->usr_first_name ?? ''
                            )
                        );

                        $lastName = ucwords(
                            strtolower(
                                $job->usr_last_name ?? ''
                            )
                        );

                        $clientName = trim(
                            $firstName . ' ' . $lastName
                        );

                        // ADDRESS
                        $address = collect([
                            $job->uadd_street ?? null,
                            $job->uadd_barangay ?? null,
                            $job->uadd_city ?? null,
                            $job->uadd_province ?? null,
                        ])
                            ->filter(
                                fn ($part) =>
                                    $part !== null &&
                                    trim($part) !== ''
                            )
                            ->implode(', ');

                        // PESTS
                        $pests = collect(
                            $pestsByService->get(
                                $job->svc_id,
                                collect()
                            )
                        )
                            ->pluck(
                                'svcp_pest_type'
                            )
                            ->toArray();

                        $fullPestTypes =
                            implode(', ', $pests);

                        // SERVICE
                        $isTermite =
                            (int) (
                                $job->svc_is_termite ?? 0
                            ) === 1;

                        if ($isTermite) {
                            $serviceName =
                                'Termite Control';
                        } elseif (
                            count($pests) === 1
                        ) {
                            $serviceName =
                                $this->formatPestName(
                                    $pests[0]
                                ) .
                                ' Control';
                        } elseif (
                            count($pests) > 1
                        ) {
                            $serviceName =
                                'General Pest Control';
                        } else {
                            $serviceName =
                                'Pest Control Service';
                        }

                        // TECHNICIANS
                        $assignedTechnicians =
                            collect(
                                $techniciansBySchedule
                                    ->get(
                                        $job->svcas_id,
                                        collect()
                                    )
                            );

                        $technicianCount =
                            $assignedTechnicians
                                ->count();

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
                                                            $tech->usr_first_name
                                                            ?? ''
                                                        )
                                                    ) .
                                                    ' ' .
                                                    ucwords(
                                                        strtolower(
                                                            $tech->usr_last_name
                                                            ?? ''
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

                        // ============================================================
                        // CLIENT BOOKING IMAGES
                        // ============================================================

                        $clientImages = collect(
                            $imagesByAppointment->get(
                                $job->svca_id,
                                collect()
                            )
                        )
                            ->pluck('svcap_image')
                            ->filter()
                            ->map(
                                function ($fileName) {
                                    return url(
                                        'images/client_images/' .
                                        $fileName
                                    );
                                }
                            )
                            ->values()
                            ->toArray();

                        return [
                            'id' =>
                                $job->svcas_id,

                            'appointment_id' =>
                                $job->svca_id,

                            'svc_sa_number' =>
                                $job->svc_sa_number ?? null,

                            'uuid' =>
                                $job->svcas_uuid,

                            'clientId' =>
                                $job->svc_id,

                            'addressId' =>
                                $job->uadd_id,

                            'clientName' =>
                                $clientName !== ''
                                    ? $clientName
                                    : 'Client',

                            'clientMobile' =>
                                $job->usr_mobile ?? '',

                            'clientProfileImage' =>
                                $job->client_image_path ?? null,

                            'service' =>
                                $serviceName,

                            'svc_assessment_recommendation' => $job->svc_assessment_recommendation ?? '',

                            'problemDescription' =>
                                $job->svc_problem_description ?? '',

                            'clientImages' =>
                                $clientImages,

                            'pestType' =>
                                count($pests) === 1
                                    ? $pests[0]
                                    : 'General Pest',

                            'fullPestTypes' =>
                                $fullPestTypes,

                            'isTermite' =>
                                $isTermite,

                            'svc_is_termite' =>
                                $job->svc_is_termite,

                            'address' =>
                                $address !== ''
                                    ? $address
                                    : 'No address',

                            'clientLatitude' =>
                                $job->uadd_latitude,

                            'clientLongitude' =>
                                $job->uadd_longitude,

                            'branchId' =>
                                $job->branch_id,

                            'branchName' =>
                                $job->branch_name,

                            'branchLatitude' =>
                                $job->branch_latitude,

                            'branchLongitude' =>
                                $job->branch_longitude,

                            'schedule' => $this->formatApprovedSchedule(
                                $job->svca_approved_date,
                                $job->svca_approved_time_from,
                                $job->svca_approved_time_to
                            ),

                            'scheduleDate' =>
                                $job->svca_approved_date,

                            'scheduleTime' =>
                                $this->formatApprovedTimeRange(
                                    $job->svca_approved_time_from,
                                    $job->svca_approved_time_to
                                ),

                            'svca_approved_date' =>
                                $job->svca_approved_date,

                            'svca_approved_time_from' =>
                                $job->svca_approved_time_from,

                            'svca_approved_time_to' =>
                                $job->svca_approved_time_to,

                            'scheduleClassification' =>
                                $this->classifyApprovedSchedule(
                                    $job->svca_status,
                                    $job->svca_approved_date,
                                    $job->svca_approved_time_from,
                                    $job->svca_approved_time_to,
                                    $currentAt
                                ),

                            'status' =>
                                $this->mapTechnicianStatus(
                                    $job->svca_status
                                ),

                            'assignedDate' =>
                                $job->svcas_date_assigned,

                            'startDate' =>
                                $job->svcas_start_date,

                            'startTime' =>
                                $job->svcas_start_time,

                            'endDate' =>
                                $job->svcas_end_date,

                            'endTime' =>
                                $job->svcas_end_time,

                            'travelTime' =>
                                $job->svcas_time_travel,

                            'totalDuration' =>
                                $job->svcas_time_finished,

                            'serviceTime' =>
                                $this->computeServiceTime(
                                    $job->svcas_time_finished,
                                    $job->svcas_time_travel
                                ),

                            'technicianCount' =>
                                $technicianCount,

                            'isMultipleTechnician' =>
                                $technicianCount > 1,

                            'teamTechnicians' =>
                                $teamTechnicians,

                            'leaderId' =>
                                $job->svcas_modified_by,

                            'isLeader' =>
                                (int) $job->svcas_modified_by ===
                                (int) $technician->usr_id,
                        ];
                    }
                )
                ->values();

            // ============================================================
            // RESPONSE
            // ============================================================

            return response()->json([
                'success' => true,

                'filter' => $filter,

                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'has_more' =>
                        $paginator->hasMorePages(),

                    'next_page' =>
                        $paginator->hasMorePages()
                            ? $page + 1
                            : null,
                ],

                'count' =>
                    $formattedJobs->count(),

                'data' =>
                    $formattedJobs,
            ]);

        } catch (\Exception $e) {
            Log::error(
                'TECHNICIAN ASSIGNED JOBS ERROR',
                [
                    'email' => $request->email,
                    'filter' => $request->filter ?? null,
                    'page' => $request->page ?? null,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to load assigned jobs.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    public function dashboard(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $technician = DB::table('users')
                ->where('usr_email', trim($request->email))
                ->first();

            if (!$technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician not found.',
                ], 404);
            }

            $currentAt = now();
            $today = $currentAt->toDateString();
            $currentTime = $currentAt->format('H:i:s');

            // Base query for this technician's assigned schedules.
            $baseQuery = DB::table('service_appointment_schedules as sched')
                ->join(
                    'service_appointments as appt',
                    'sched.svca_id',
                    '=',
                    'appt.svca_id'
                )
                ->join('services as svc', 'appt.svc_id', '=', 'svc.svc_id')
                ->join(
                    'service_appointment_schedule_assigns as assign',
                    'sched.svcas_id',
                    '=',
                    'assign.svcas_id'
                )
                ->where(
                    'assign.svcasa_assigned_to',
                    $technician->usr_id
                )
                ->where('assign.svcasa_active', 1)
                ->where('sched.svcas_active', 1)
                ->where('appt.svca_active', 1)
                ->where('svc.svc_active', 1);

            // ============================================================
            // DASHBOARD COUNTS - SINGLE QUERY
            // ============================================================

            $todaysJobs = (clone $baseQuery)
                ->where(function ($activeQuery) use ($today, $currentTime) {
                    $activeQuery
                        ->whereIn('appt.svca_status', [
                            'ONGOING',
                            'ARRIVED',
                            'DONE SERVICE',
                        ])
                        ->orWhere(function ($assignedQuery) use ($today, $currentTime) {
                            $this->applyAssignedScheduleFilter(
                                $assignedQuery,
                                'service_now',
                                $today,
                                $currentTime
                            );
                        });
                })
                ->count();

            $completedToday = (clone $baseQuery)
                ->where('appt.svca_status', 'DONE REPORT')
                ->count();

            $upcoming = (clone $baseQuery)
                ->where(function ($windowQuery) use ($today, $currentTime) {
                    $this->applyAssignedScheduleFilter(
                        $windowQuery,
                        'upcoming',
                        $today,
                        $currentTime
                    );
                })
                ->count();

            $overdue = (clone $baseQuery)
                ->where(function ($windowQuery) use ($today, $currentTime) {
                    $this->applyAssignedScheduleFilter(
                        $windowQuery,
                        'overdue',
                        $today,
                        $currentTime
                    );
                })
                ->count();

            // ============================================================
            // UPCOMING SCHEDULE - NEXT 3 JOBS
            // ============================================================

            $upcomingSchedule = DB::table('service_appointment_schedules as sched')
                ->join(
                    'service_appointments as appt',
                    'sched.svca_id',
                    '=',
                    'appt.svca_id'
                )
                ->leftJoin(
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
                ->join(
                    'service_appointment_schedule_assigns as assign',
                    'sched.svcas_id',
                    '=',
                    'assign.svcas_id'
                )
                ->where(
                    'assign.svcasa_assigned_to',
                    $technician->usr_id
                )
                ->where('assign.svcasa_active', 1)
                ->where('sched.svcas_active', 1)
                ->where('appt.svca_active', 1)
                ->where('svc.svc_active', 1)
                ->where(function ($windowQuery) use ($today, $currentTime) {
                    $this->applyAssignedScheduleFilter(
                        $windowQuery,
                        'upcoming',
                        $today,
                        $currentTime
                    );
                })
                ->select(
                    'sched.svcas_id',
                    'appt.svc_id',
                    'appt.svca_approved_date',
                    'appt.svca_approved_time_from',
                    'appt.svca_approved_time_to',
                    'svc.svc_is_termite',
                    'addr.uadd_street',
                    'addr.uadd_barangay',
                    'addr.uadd_city',
                    'addr.uadd_province'
                )
                ->orderBy('appt.svca_approved_date')
                ->orderBy('appt.svca_approved_time_from')
                ->limit(3)
                ->get();

            $formattedUpcomingSchedule = $upcomingSchedule->map(function ($job) {

            // ============================================================
            // ADDRESS
            // ============================================================

            $address = collect([
                $job->uadd_street ?? null,
                $job->uadd_barangay ?? null,
                $job->uadd_city ?? null,
                $job->uadd_province ?? null,
            ])
                ->filter(
                    fn ($part) =>
                        $part !== null &&
                        trim($part) !== ''
                )
                ->implode(', ');


            // ============================================================
            // PEST / SERVICE NAME
            // ============================================================

            $pests = DB::table('service_order_pests as sop')
            ->join(
                'service_packages as sp',
                'sop.svcp_id',
                '=',
                'sp.svcp_id'
            )
            ->where(
                'sop.svc_id',
                $job->svc_id
            )
            ->where('sop.svcop_active', 1)
            ->where('sp.svcp_active', 1)
            ->pluck('sp.svcp_pest_type')
            ->toArray();

            $isTermite =
                (int) ($job->svc_is_termite ?? 0) === 1;

            if ($isTermite) {
                $serviceName = 'Termite Control';
            } elseif (count($pests) === 1) {
                $serviceName =
                    $this->formatPestName($pests[0]) .
                    ' Control';
            } elseif (count($pests) > 1) {
                $serviceName = 'General Pest Control';
            } else {
                $serviceName = 'Pest Control Service';
            }


            // ============================================================
            // RETURN
            // ============================================================

            return [
                'id' => $job->svcas_id,

                'service' => $serviceName,

                'date' => $this->formatDate(
                    $job->svca_approved_date
                ),

                'time' => $this->formatApprovedTimeRange(
                    $job->svca_approved_time_from,
                    $job->svca_approved_time_to
                ),

                'scheduleDate' =>
                    $job->svca_approved_date,

                'scheduleTime' =>
                    $this->formatApprovedTimeRange(
                        $job->svca_approved_time_from,
                        $job->svca_approved_time_to
                    ),

                'svca_approved_date' =>
                    $job->svca_approved_date,

                'svca_approved_time_from' =>
                    $job->svca_approved_time_from,

                'svca_approved_time_to' =>
                    $job->svca_approved_time_to,

                'address' =>
                    $address !== ''
                        ? $address
                        : 'No address',
            ];
        })->values();

            // ============================================================
            // TODAY'S CURRENT / NEXT ASSIGNMENT
            // ============================================================

            $assignment = DB::table('service_appointment_schedules as sched')
                ->join(
                    'service_appointments as appt',
                    'sched.svca_id',
                    '=',
                    'appt.svca_id'
                )
                ->leftJoin(
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
                ->leftJoin(
                    'users as client',
                    'addr.usr_id',
                    '=',
                    'client.usr_id'
                )
                ->join(
                    'service_appointment_schedule_assigns as assign',
                    'sched.svcas_id',
                    '=',
                    'assign.svcas_id'
                )
                ->where(
                    'assign.svcasa_assigned_to',
                    $technician->usr_id
                )
                ->where('assign.svcasa_active', 1)
                ->where('sched.svcas_active', 1)
                ->where('appt.svca_active', 1)
                ->where('svc.svc_active', 1)
                ->where(function ($activeQuery) use ($today, $currentTime) {
                    $activeQuery
                        ->whereIn('appt.svca_status', [
                            'ONGOING',
                            'ARRIVED',
                            'DONE SERVICE',
                        ])
                        ->orWhere(function ($assignedQuery) use ($today, $currentTime) {
                            $this->applyAssignedScheduleFilter(
                                $assignedQuery,
                                'service_now',
                                $today,
                                $currentTime
                            );
                        });
                })
                ->select(
                    'sched.svcas_id',
                    'sched.svca_id',
                    'appt.svc_id',
                    'appt.svca_status',
                    'appt.svca_approved_date',
                    'appt.svca_approved_time_from',
                    'appt.svca_approved_time_to',
                    'appt.uadd_id',

                    'svc.svc_is_termite',
                    'svc.svc_assessment_recommendation',

                    'client.usr_first_name',
                    'client.usr_last_name',

                    'addr.uadd_street',
                    'addr.uadd_barangay',
                    'addr.uadd_city',
                    'addr.uadd_province'
                )
                ->orderByRaw("
                    CASE
                        WHEN appt.svca_status = 'ONGOING' THEN 1
                        WHEN appt.svca_status = 'ARRIVED' THEN 2
                        WHEN appt.svca_status = 'DONE SERVICE' THEN 3
                        ELSE 4
                    END
                ")
                ->orderBy('appt.svca_approved_time_from')
                ->first();

            $formattedAssignment = null;

            if ($assignment) {
                $firstName = ucwords(
                    strtolower($assignment->usr_first_name ?? '')
                );

                $lastName = ucwords(
                    strtolower($assignment->usr_last_name ?? '')
                );

                $clientName = trim(
                    $firstName . ' ' . $lastName
                );

                $address = collect([
                    $assignment->uadd_street ?? null,
                    $assignment->uadd_barangay ?? null,
                    $assignment->uadd_city ?? null,
                    $assignment->uadd_province ?? null,
                ])
                    ->filter(
                        fn ($part) =>
                            $part !== null &&
                            trim($part) !== ''
                    )
                    ->implode(', ');

                // Only one pest query because Home displays only one assignment.
                $pests = DB::table('service_order_pests as sop')
                    ->join(
                        'service_packages as sp',
                        'sop.svcp_id',
                        '=',
                        'sp.svcp_id'
                    )
                    ->where(
                        'sop.svc_id',
                        $assignment->svc_id
                    )
                    ->where('sop.svcop_active', 1)
                    ->where('sp.svcp_active', 1)
                    ->pluck('sp.svcp_pest_type')
                    ->toArray();

                $isTermite =
                    (int) ($assignment->svc_is_termite ?? 0) === 1;

                if ($isTermite) {
                    $serviceName = 'Termite Control';
                } elseif (count($pests) === 1) {
                    $serviceName =
                        $this->formatPestName($pests[0]) .
                        ' Control';
                } elseif (count($pests) > 1) {
                    $serviceName = 'General Pest Control';
                } else {
                    $serviceName = 'Pest Control Service';
                }

                $formattedAssignment = [
                    'id' => $assignment->svcas_id,
                    'appointment_id' => $assignment->svca_id,

                    'clientName' =>
                        $clientName !== ''
                            ? $clientName
                            : 'Client',

                    'service' => $serviceName,
                    'svc_assessment_recommendation' => $assignment->svc_assessment_recommendation ?? '',

                    'pestType' =>
                        count($pests) === 1
                            ? $pests[0]
                            : 'General Pest',

                    'address' =>
                        $address !== ''
                            ? $address
                            : 'No address',

                    'schedule' => $this->formatApprovedSchedule(
                        $assignment->svca_approved_date,
                        $assignment->svca_approved_time_from,
                        $assignment->svca_approved_time_to
                    ),

                    'scheduleDate' =>
                        $assignment->svca_approved_date,

                    'scheduleTime' =>
                        $this->formatApprovedTimeRange(
                            $assignment->svca_approved_time_from,
                            $assignment->svca_approved_time_to
                        ),

                    'svca_approved_date' =>
                        $assignment->svca_approved_date,

                    'svca_approved_time_from' =>
                        $assignment->svca_approved_time_from,

                    'svca_approved_time_to' =>
                        $assignment->svca_approved_time_to,

                    'status' => $this->mapTechnicianStatus(
                        $assignment->svca_status
                    ),

                    'scheduleClassification' =>
                        $this->classifyApprovedSchedule(
                            $assignment->svca_status,
                            $assignment->svca_approved_date,
                            $assignment->svca_approved_time_from,
                            $assignment->svca_approved_time_to,
                            $currentAt
                        ),
                ];
            }

            return response()->json([
                'success' => true,

                'data' => [
                    'todaysJobs' => $todaysJobs,
                    'completedToday' => $completedToday,
                    'upcoming' => $upcoming,
                    'overdue' => $overdue,
                    'assignment' => $formattedAssignment,
                    'upcomingSchedule' => $formattedUpcomingSchedule,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('TECHNICIAN DASHBOARD ERROR', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load technician dashboard.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function applyAssignedScheduleFilter(
        $query,
        string $classification,
        ?string $today = null,
        ?string $currentTime = null
    ) {
        $query->where('appt.svca_status', 'ASSIGNED');

        if ($classification === 'schedule_missing') {
            return $query->where(function ($missingQuery) {
                $missingQuery
                    ->whereNull('appt.svca_approved_date')
                    ->orWhereNull('appt.svca_approved_time_from')
                    ->orWhereNull('appt.svca_approved_time_to');
            });
        }

        $query
            ->whereNotNull('appt.svca_approved_date')
            ->whereNotNull('appt.svca_approved_time_from')
            ->whereNotNull('appt.svca_approved_time_to');

        if ($classification === 'upcoming') {
            return $query->where(function ($windowQuery) use ($today, $currentTime) {
                $windowQuery
                    ->whereDate('appt.svca_approved_date', '>', $today)
                    ->orWhere(function ($todayQuery) use ($today, $currentTime) {
                        $todayQuery
                            ->whereDate('appt.svca_approved_date', $today)
                            ->whereTime('appt.svca_approved_time_from', '>', $currentTime);
                    });
            });
        }

        if ($classification === 'service_now') {
            return $query
                ->whereDate('appt.svca_approved_date', $today)
                ->whereTime('appt.svca_approved_time_from', '<=', $currentTime)
                ->whereTime('appt.svca_approved_time_to', '>=', $currentTime);
        }

        if ($classification === 'overdue') {
            return $query->where(function ($windowQuery) use ($today, $currentTime) {
                $windowQuery
                    ->whereDate('appt.svca_approved_date', '<', $today)
                    ->orWhere(function ($todayQuery) use ($today, $currentTime) {
                        $todayQuery
                            ->whereDate('appt.svca_approved_date', $today)
                            ->whereTime('appt.svca_approved_time_to', '<', $currentTime);
                    });
            });
        }

        throw new \InvalidArgumentException(
            "Unknown approved schedule classification: {$classification}"
        );
    }

    private function classifyApprovedSchedule(
        $status,
        $approvedDate,
        $approvedTimeFrom,
        $approvedTimeTo,
        Carbon $currentAt
    ): ?string {
        if (strtoupper(trim($status ?? '')) !== 'ASSIGNED') {
            return null;
        }

        if (
            !$approvedDate ||
            !$approvedTimeFrom ||
            !$approvedTimeTo
        ) {
            return 'SCHEDULE_MISSING';
        }

        $approvedStart = Carbon::parse(
            $approvedDate . ' ' . $approvedTimeFrom,
            config('app.timezone')
        );
        $approvedEnd = Carbon::parse(
            $approvedDate . ' ' . $approvedTimeTo,
            config('app.timezone')
        );

        if ($currentAt->lt($approvedStart)) {
            return 'UPCOMING';
        }

        if ($currentAt->lte($approvedEnd)) {
            return 'SERVICE_NOW';
        }

        return 'OVERDUE';
    }

    private function formatApprovedSchedule($date, $timeFrom, $timeTo): string
    {
        if (!$date || !$timeFrom || !$timeTo) {
            return 'Approved schedule unavailable';
        }

        return $this->formatDate($date) . ' • ' .
            $this->formatApprovedTimeRange($timeFrom, $timeTo);
    }

    private function formatApprovedTimeRange($timeFrom, $timeTo): string
    {
        if (!$timeFrom || !$timeTo) {
            return 'Approved time unavailable';
        }

        return $this->formatTime($timeFrom) . ' - ' .
            $this->formatTime($timeTo);
    }

    private function computeServiceTime($total, $travel)
    {
        if (!$total || !$travel) {
            return 'N/A';
        }

        $totalSeconds = strtotime("1970-01-01 $total UTC") - strtotime("1970-01-01 00:00:00 UTC");
        $travelSeconds = strtotime("1970-01-01 $travel UTC") - strtotime("1970-01-01 00:00:00 UTC");

        $serviceSeconds = max(0, $totalSeconds - $travelSeconds);

        return gmdate('H:i:s', $serviceSeconds);
    }

    private function mapTechnicianStatus($status)
    {
        $status = strtoupper(trim($status ?? ''));

        if (in_array($status, [
            'ASSIGNED',
            'ONGOING',
            'ARRIVED',
            'DONE SERVICE',
            'DONE REPORT'
        ])) {
            return $status;
        }

        return 'ASSIGNED';
    }

    private function formatDate($value)
    {
        if (!$value) return 'No date';

        $timestamp = strtotime($value);

        if (!$timestamp) return $value;

        return date('F j, Y', $timestamp);
    }

    private function formatTime($value)
    {
        if (!$value) return 'No time';

        try {
            return Carbon::parse($value)->format('g:i A');
        } catch (\Exception $e) {
            return $value;
        }
    }

    private function formatPestName($pest)
    {
        $normalized = strtoupper(trim($pest));

        return match ($normalized) {
            'RATS/MICE' => 'Rodent',
            'BED BUGS' => 'Bed Bug',
            'COCKROACHES' => 'Cockroach',
            'ANTS' => 'Ant',
            'TERMITES' => 'Termite',
            default => ucwords(strtolower($normalized)),
        };
    }

    private function getAssignedTechnicianId($scheduleId)
    {
        return DB::table('service_appointment_schedule_assigns')
            ->where('svcas_id', $scheduleId)
            ->where('svcasa_active', 1)
            ->value('svcasa_assigned_to');
    }

    public function routeToClient(Request $request)
    {
        $request->validate([
            'startLatitude' => 'required|numeric',
            'startLongitude' => 'required|numeric',
            'clientLatitude' => 'required|numeric',
            'clientLongitude' => 'required|numeric',
        ]);

        $mapboxToken = config('services.mapbox.token');

        if (!$mapboxToken) {
            return response()->json([
                'success' => false,
                'message' => 'Mapbox access token is not configured.',
            ], 500);
        }

        $coordinates = implode(';', [
            $request->startLongitude . ',' . $request->startLatitude,
            $request->clientLongitude . ',' . $request->clientLatitude,
        ]);

        $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$coordinates}";

        $response = Http::timeout(12)->get($url, [
            'access_token' => $mapboxToken,
            'geometries' => 'polyline6',
            'overview' => 'full',
            'steps' => 'false',
        ]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to get route from Mapbox.',
                'mapboxStatus' => $response->status(),
            ], 502);
        }

        $data = $response->json();

        if (
            !isset($data['routes']) ||
            count($data['routes']) === 0 ||
            !isset($data['routes'][0]['geometry'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No route found.',
            ], 404);
        }

        $route = $data['routes'][0];

        return response()->json([
            'success' => true,
            'geometry' => $route['geometry'],
            'distance' => $route['distance'] ?? null,
            'duration' => $route['duration'] ?? null,
        ]);
    }

    public function startTravel(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'appointment_id' => 'required|integer',
            'schedule_id' => 'required|integer',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            $technician = DB::table('users')
                ->where('usr_email', trim($request->email))
                ->first();

            if (!$technician) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Technician not found.',
                ], 404);
            }

            $technicianId = $technician->usr_id;

            $appointment = DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->first();

            if (!$appointment) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment not found.',
                ], 404);
            }

            $schedule = DB::table('service_appointment_schedules')
                ->where('svcas_id', $request->schedule_id)
                ->where('svca_id', $request->appointment_id)
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment schedule not found.',
                ], 404);
            }

            $isAssigned = DB::table('service_appointment_schedule_assigns')
                ->where('svcas_id', $request->schedule_id)
                ->where('svcasa_assigned_to', $technicianId)
                ->where('svcasa_active', 1)
                ->exists();

            if (!$isAssigned) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this service.',
                ], 403);
            }

            if (
                $schedule->svcas_start_date &&
                $schedule->svcas_start_time &&
                $schedule->svcas_modified_by &&
                (int) $schedule->svcas_modified_by !== (int) $technicianId
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'This service has already been started by another technician.',
                ], 409);
            }

            $clientAddress = DB::table('user_addresses')
                ->where('uadd_id', $appointment->uadd_id)
                ->first();

            if (
                !$clientAddress ||
                !$clientAddress->uadd_latitude ||
                !$clientAddress->uadd_longitude
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Client address has no pinned map location.',
                ], 422);
            }

            $now = now();

            DB::table('services')
                ->where('svc_id', $appointment->svc_id)
                ->update([
                    'svc_status' => 'ONGOING',
                ]);

            DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->update([
                    'svca_status' => 'ONGOING',
                ]);

            DB::table('service_appointment_schedules')
                ->where('svcas_id', $request->schedule_id)
                ->update([
                    'svcas_start_date' => $now->toDateString(),
                    'svcas_start_time' => $now->format('H:i:s'),
                    'svcas_date_modified' => $now,
                    'svcas_modified_by' => $technicianId,
                ]);

            $existingLocation = DB::table('service_appointment_locations')
                ->where('svcas_id', $request->schedule_id)
                ->first();

            if ($existingLocation) {
                DB::table('service_appointment_locations')
                    ->where('svcas_id', $request->schedule_id)
                    ->update([
                        'usr_id' => $technicianId,
                        'svcal_latitude' => $request->latitude,
                        'svcal_longitude' => $request->longitude,
                        'svcal_active' => 1,
                        'svcal_date_modified' => $now,
                        'svcal_modified_by' => $technicianId,
                    ]);
            } else {
                DB::table('service_appointment_locations')
                    ->insert([
                        'svcal_uuid' => generateuuid(),
                        'svcas_id' => $request->schedule_id,
                        'usr_id' => $technicianId,
                        'svcal_latitude' => $request->latitude,
                        'svcal_longitude' => $request->longitude,
                        'svcal_active' => 1,
                        'svcal_date_created' => $now,
                        'svcal_created_by' => $technicianId,
                        'svcal_date_modified' => $now,
                        'svcal_modified_by' => $technicianId,
                    ]);
            }

            $routeData = null;
            $mapboxToken = config('services.mapbox.token');

            if ($mapboxToken) {
                $coordinates = implode(';', [
                    $request->longitude . ',' . $request->latitude,
                    $clientAddress->uadd_longitude . ',' . $clientAddress->uadd_latitude,
                ]);

                $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$coordinates}";

                $routeResponse = Http::timeout(12)->get($url, [
                    'access_token' => $mapboxToken,
                    'geometries' => 'polyline6',
                    'overview' => 'full',
                    'steps' => 'false',
                ]);

                if ($routeResponse->successful()) {
                    $mapboxData = $routeResponse->json();

                    if (
                        isset($mapboxData['routes']) &&
                        count($mapboxData['routes']) > 0 &&
                        isset($mapboxData['routes'][0]['geometry'])
                    ) {
                        $route = $mapboxData['routes'][0];

                        $routeData = [
                            'success' => true,
                            'geometry' => $route['geometry'],
                            'distance' => $route['distance'] ?? null,
                            'duration' => $route['duration'] ?? null,
                        ];

                        Cache::put(
                            'tracking_route_' . $request->schedule_id,
                            $routeData,
                            now()->addHours(2)
                        );

                        $routeFromCache = Cache::get(
                            'tracking_route_' . $request->schedule_id
                        );

                        Log::info('TRACKING ROUTE CACHED SUCCESSFULLY', [
                            'schedule_id' => $request->schedule_id,
                            'cache_key' => 'tracking_route_' . $request->schedule_id,
                            'cache_found' => $routeFromCache !== null,
                            'geometry_length' => strlen($routeFromCache['geometry'] ?? ''),
                            'distance' => $routeFromCache['distance'] ?? null,
                            'duration' => $routeFromCache['duration'] ?? null,
                        ]);
                    }
                } else {
                    Log::warning('MAPBOX ROUTE REQUEST FAILED', [
                        'schedule_id' => $request->schedule_id,
                        'status' => $routeResponse->status(),
                        'body' => $routeResponse->body(),
                    ]);
                }
            }

            DB::commit();

            // ============================================================
            // CLIENT NOTIFICATION - TECHNICIAN ON THE WAY
            // ============================================================

            try {
                $clientUserId = $clientAddress->usr_id ?? null;

                if ($clientUserId) {
                    
                    $message = 'Your technician is now traveling to your service location.';

                    DB::table('message_notifications')->insert([
                        'mesn_uuid' => generateuuid(),
                        'usr_id' => $clientUserId,
                        'mesn_name' => strtoupper($message),
                        'mesn_date_created' => $now,
                        'mesn_created_by' => $technicianId,
                        'mesn_view' => '0',
                        'mesn_active' => 1,
                    ]);

                    Log::info('CLIENT NOTIFICATION CREATED', [
                        'type' => 'TECHNICIAN_ON_THE_WAY',
                        'client_user_id' => $clientUserId,
                        'appointment_id' => $request->appointment_id,
                        'schedule_id' => $request->schedule_id,
                    ]);
                } else {
                    Log::warning('CLIENT NOTIFICATION NOT CREATED - CLIENT USER ID MISSING', [
                        'appointment_id' => $request->appointment_id,
                        'schedule_id' => $request->schedule_id,
                    ]);
                }
            } catch (\Exception $notificationException) {
                // Notification failure must not cancel successful travel.
                Log::warning('CLIENT NOTIFICATION CREATE FAILED', [
                    'appointment_id' => $request->appointment_id,
                    'schedule_id' => $request->schedule_id,
                    'error' => $notificationException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Travel started successfully.',
                'status' => 'ONGOING',
                'startDate' => $now->toDateString(),
                'startTime' => $now->format('H:i:s'),
                'leaderId' => $technicianId,
                'isLeader' => true,
                'route' => $routeData,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unable to start travel.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $schedule = DB::table('service_appointment_schedules')
                ->where('svcas_id', $request->schedule_id)
                ->first();

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment schedule not found.',
                ], 404);
            }


            $assignedTechnicianId = $this->getAssignedTechnicianId($request->schedule_id);

            if (!$assignedTechnicianId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assigned technician not found for this schedule.',
                ], 404);
            }

            DB::table('service_appointment_locations')
                ->where('svcas_id', $request->schedule_id)
                ->update([
                    'svcal_latitude' => $request->latitude,
                    'svcal_longitude' => $request->longitude,
                    'svcal_active' => 1,
                    'svcal_date_modified' => now(),
                    'svcal_modified_by' => $assignedTechnicianId,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Technician location updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to update technician location.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markArrived(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
            'schedule_id' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $appointment = DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->first();

            if (!$appointment) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment not found.',
                ], 404);
            }

            // ============================================================
            // GET CLIENT ADDRESS / USER
            // ============================================================

            $clientAddress = DB::table('user_addresses')
                ->where('uadd_id', $appointment->uadd_id)
                ->first();

            $clientUserId = $clientAddress->usr_id ?? null;

            $schedule = DB::table('service_appointment_schedules')
                ->where('svcas_id', $request->schedule_id)
                ->where('svca_id', $request->appointment_id)
                ->first();

            if (!$schedule) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment schedule not found.',
                ], 404);
            }

            $assignedTechnicianId = $this->getAssignedTechnicianId($request->schedule_id);

            if (!$assignedTechnicianId) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Assigned technician not found for this schedule.',
                ], 404);
            }

            // The technician who first started travel remains the leader for
            // the entire service. Do not replace them with the first member
            // returned from the assignment table.
            $leaderTechnicianId = $schedule->svcas_modified_by
                ?: $assignedTechnicianId;

            if (!$schedule->svcas_start_date || !$schedule->svcas_start_time) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Start travel time is missing. Unable to compute travel time.',
                ], 422);
            }

            $now = now();

            $startedTravelAt = \Carbon\Carbon::parse(
                $schedule->svcas_start_date . ' ' . $schedule->svcas_start_time
            );

            $totalSeconds = $startedTravelAt->diffInSeconds($now);

            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;

            $travelTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->update([
                    'svca_status' => 'ARRIVED',
                ]);

            DB::table('service_appointment_schedules')
                ->where('svcas_id', $request->schedule_id)
                ->update([
                    'svcas_time_travel' => $travelTime,
                    'svcas_date_modified' => $now,
                    'svcas_modified_by' => $leaderTechnicianId,
                ]);

            DB::commit();

            // ============================================================
            // CLIENT NOTIFICATION - TECHNICIAN ARRIVED
            // ============================================================

            try {
                if ($clientUserId) {
                    $message =
                        'Your technician has arrived at your service location.';

                    DB::table('message_notifications')->insert([
                        'mesn_uuid' => generateuuid(),
                        'usr_id' => $clientUserId,
                        'mesn_name' => strtoupper($message),
                        'mesn_date_created' => $now,
                        'mesn_created_by' => $assignedTechnicianId,
                        'mesn_view' => '0',
                        'mesn_active' => 1,
                    ]);

                    Log::info('CLIENT NOTIFICATION CREATED', [
                        'type' => 'TECHNICIAN_ARRIVED',
                        'client_user_id' => $clientUserId,
                        'appointment_id' => $request->appointment_id,
                        'schedule_id' => $request->schedule_id,
                    ]);
                }
            } catch (\Exception $notificationException) {
                // Notification failure must not cancel successful arrival.
                Log::warning('CLIENT ARRIVED NOTIFICATION CREATE FAILED', [
                    'appointment_id' => $request->appointment_id,
                    'schedule_id' => $request->schedule_id,
                    'error' => $notificationException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Technician marked as arrived successfully.',
                'status' => 'ARRIVED',
                'travelTime' => $travelTime,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unable to mark technician as arrived.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function doneService(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
            'schedule_id' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $appointment = DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->first();

            if (!$appointment) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment not found.',
                ], 404);
            }

            // ============================================================
            // GET CLIENT ADDRESS / USER
            // ============================================================

            $clientAddress = DB::table('user_addresses')
                ->where('uadd_id', $appointment->uadd_id)
                ->first();

            $clientUserId = $clientAddress->usr_id ?? null;

            $schedule = DB::table('service_appointment_schedules')
                ->where('svcas_id', $request->schedule_id)
                ->where('svca_id', $request->appointment_id)
                ->first();

            if (!$schedule) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment schedule not found.',
                ], 404);
            }

            $assignedTechnicianId = $this->getAssignedTechnicianId($request->schedule_id);

            if (!$assignedTechnicianId) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Assigned technician not found for this schedule.',
                ], 404);
            }

            // Preserve the original Start Travel leader through completion.
            $leaderTechnicianId = $schedule->svcas_modified_by
                ?: $assignedTechnicianId;

            if (!$schedule->svcas_start_date || !$schedule->svcas_start_time) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Start time is missing. Unable to complete service.',
                ], 422);
            }

            $now = now();

            $startedAt = \Carbon\Carbon::parse(
                $schedule->svcas_start_date . ' ' . $schedule->svcas_start_time
            );

            $totalSeconds = $startedAt->diffInSeconds($now);

            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;

            $timeFinished = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            DB::table('services')
                ->where('svc_id', $appointment->svc_id)
                ->update([
                    'svc_status' => 'DONE SERVICE',
                ]);

            DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->update([
                    'svca_status' => 'DONE SERVICE',
                ]);

            DB::table('service_appointment_schedules')
                ->where('svcas_id', $request->schedule_id)
                ->update([
                    'svcas_end_date' => $now->toDateString(),
                    'svcas_end_time' => $now->format('H:i:s'),
                    'svcas_time_finished' => $timeFinished,
                    'svcas_date_modified' => $now,
                    'svcas_modified_by' => $leaderTechnicianId,
                ]);

            DB::table('service_appointment_locations')
                ->where('svcas_id', $request->schedule_id)
                ->update([
                    'svcal_active' => 0,
                    'svcal_date_modified' => $now,
                    'svcal_modified_by' => $assignedTechnicianId,
                ]);

            $cacheKey = 'tracking_route_' . $request->schedule_id;

            Log::info('TRACKING ROUTE CLEAR REQUEST', [
                'schedule_id' => $request->schedule_id,
                'cache_key' => $cacheKey,
                'cache_existed_before_clear' => Cache::has($cacheKey),
            ]);

            Cache::forget($cacheKey);

            Log::info('TRACKING ROUTE CLEARED', [
                'schedule_id' => $request->schedule_id,
                'cache_key' => $cacheKey,
                'cache_exists_after_clear' => Cache::has($cacheKey),
            ]);

            DB::commit();

            // ============================================================
            // CLIENT NOTIFICATION - SERVICE COMPLETED
            // ============================================================

            try {
                if ($clientUserId) {
                    $message =
                        'Your technician has completed the pest control service.';

                    DB::table('message_notifications')->insert([
                        'mesn_uuid' => generateuuid(),
                        'usr_id' => $clientUserId,
                        'mesn_name' => strtoupper($message),
                        'mesn_date_created' => $now,
                        'mesn_created_by' => $assignedTechnicianId,
                        'mesn_view' => '0',
                        'mesn_active' => 1,
                    ]);

                    Log::info('CLIENT NOTIFICATION CREATED', [
                        'type' => 'SERVICE_COMPLETED',
                        'client_user_id' => $clientUserId,
                        'appointment_id' => $request->appointment_id,
                        'schedule_id' => $request->schedule_id,
                    ]);
                } else {
                    Log::warning(
                        'CLIENT SERVICE COMPLETED NOTIFICATION NOT CREATED - CLIENT USER ID MISSING',
                        [
                            'appointment_id' => $request->appointment_id,
                            'schedule_id' => $request->schedule_id,
                        ]
                    );
                }
            } catch (\Exception $notificationException) {
                // Notification failure must not cancel successful service completion.
                Log::warning('CLIENT SERVICE COMPLETED NOTIFICATION CREATE FAILED', [
                    'appointment_id' => $request->appointment_id,
                    'schedule_id' => $request->schedule_id,
                    'error' => $notificationException->getMessage(),
                ]);
            }

            // TECHNICIAN NOTIFICATION - SERVICE COMPLETED

            try {
                $message =
                    'The pest control service has been completed. Please submit your service report.';

                DB::table('message_notifications')->insert([
                    'mesn_uuid' => generateuuid(),
                    'usr_id' => $assignedTechnicianId,
                    'mesn_name' => strtoupper($message),
                    'mesn_date_created' => $now,
                    'mesn_created_by' => $assignedTechnicianId,
                    'mesn_view' => '0',
                    'mesn_active' => 1,
                ]);

                Log::info('TECHNICIAN NOTIFICATION CREATED', [
                    'type' => 'SERVICE_COMPLETED',
                    'technician_user_id' => $assignedTechnicianId,
                    'appointment_id' => $request->appointment_id,
                    'schedule_id' => $request->schedule_id,
                ]);
            } catch (\Exception $notificationException) {
                // Notification failure must not cancel successful service completion.
                Log::warning('TECHNICIAN SERVICE COMPLETED NOTIFICATION CREATE FAILED', [
                    'technician_user_id' => $assignedTechnicianId,
                    'appointment_id' => $request->appointment_id,
                    'schedule_id' => $request->schedule_id,
                    'error' => $notificationException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Service marked as done successfully.',
                'status' => 'DONE SERVICE',
                'endDate' => $now->toDateString(),
                'endTime' => $now->format('H:i:s'),
                'timeFinished' => $timeFinished,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unable to complete service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rerouteCachedRoute(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $schedule = DB::table('service_appointment_schedules as sched')
                ->leftJoin('service_appointments as appt', 'sched.svca_id', '=', 'appt.svca_id')
                ->leftJoin('user_addresses as addr', 'appt.uadd_id', '=', 'addr.uadd_id')
                ->where('sched.svcas_id', $request->schedule_id)
                ->select(
                    'sched.svcas_id',
                    'sched.svca_id',
                    'appt.uadd_id',
                    'addr.uadd_latitude',
                    'addr.uadd_longitude'
                )
                ->first();

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment schedule not found.',
                ], 404);
            }

            if (!$schedule->uadd_latitude || !$schedule->uadd_longitude) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client address has no pinned map location.',
                ], 422);
            }

            $mapboxToken = config('services.mapbox.token');

            if (!$mapboxToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mapbox access token is not configured.',
                ], 500);
            }

            $coordinates = implode(';', [
                $request->longitude . ',' . $request->latitude,
                $schedule->uadd_longitude . ',' . $schedule->uadd_latitude,
            ]);

            $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$coordinates}";

            $routeResponse = Http::timeout(12)->get($url, [
                'access_token' => $mapboxToken,
                'geometries' => 'polyline6',
                'overview' => 'full',
                'steps' => 'false',
            ]);

            if (!$routeResponse->successful()) {
                Log::warning('TRACKING REROUTE MAPBOX FAILED', [
                    'schedule_id' => $request->schedule_id,
                    'status' => $routeResponse->status(),
                    'body' => $routeResponse->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to reroute from Mapbox.',
                    'mapboxStatus' => $routeResponse->status(),
                ], 502);
            }

            $mapboxData = $routeResponse->json();

            if (
                !isset($mapboxData['routes']) ||
                count($mapboxData['routes']) === 0 ||
                !isset($mapboxData['routes'][0]['geometry'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reroute found.',
                ], 404);
            }

            $route = $mapboxData['routes'][0];

            $routeData = [
                'success' => true,
                'geometry' => $route['geometry'],
                'distance' => $route['distance'] ?? null,
                'duration' => $route['duration'] ?? null,
            ];

            $cacheKey = 'tracking_route_' . $request->schedule_id;

            Cache::put(
                $cacheKey,
                $routeData,
                now()->addHours(2)
            );

            $routeFromCache = Cache::get($cacheKey);

            Log::info('TRACKING ROUTE REROUTED AND CACHE UPDATED', [
                'schedule_id' => $request->schedule_id,
                'cache_key' => $cacheKey,
                'cache_found' => $routeFromCache !== null,
                'geometry_length' => strlen($routeFromCache['geometry'] ?? ''),
                'distance' => $routeFromCache['distance'] ?? null,
                'duration' => $routeFromCache['duration'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Route rerouted and cache updated successfully.',
                'route' => $routeData,
            ]);
        } catch (\Exception $e) {
            Log::error('TRACKING REROUTE ERROR', [
                'schedule_id' => $request->schedule_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to reroute tracking route.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCachedRoute(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer',
        ]);

        $cacheKey = 'tracking_route_' . $request->schedule_id;

        $route = Cache::get($cacheKey);

        Log::info('TECHNICIAN/CLIENT SHARED ROUTE REQUEST', [
            'schedule_id' => $request->schedule_id,
        ]);

        Log::info('TRACKING ROUTE CACHE CHECK', [
            'schedule_id' => $request->schedule_id,
            'cache_key' => $cacheKey,
            'cache_hit' => $route !== null,
            'geometry_length' => strlen($route['geometry'] ?? ''),
            'distance' => $route['distance'] ?? null,
            'duration' => $route['duration'] ?? null,
        ]);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found.',
                'cache_key' => $cacheKey,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'route' => $route,
        ]);
    }

    public function getServiceChemicals(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
        ]);

        try {
            $appointment = DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->first();

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment not found.',
                ], 404);
            }

            $chemicals = DB::table('service_chemicals as svcch')
                ->join('chemicals as chem', 'svcch.chem_id', '=', 'chem.chem_id')
                ->where('svcch.svc_id', $appointment->svc_id)
                ->where('svcch.svcch_active', 1)
                ->where('chem.chem_active', 1)
                ->select(
                    'svcch.svcch_id',
                    'svcch.svc_id',
                    'svcch.chem_id',
                    'chem.chem_name',
                    'svcch.svcch_expected_quantity',
                    'svcch.svcch_actual_quantity',
                    'svcch.svcch_unit'
                )
                ->orderBy('svcch.svcch_id')
                ->get();

            return response()->json([
                'success' => true,
                'service_id' => $appointment->svc_id,
                'count' => $chemicals->count(),
                'chemicals' => $chemicals,
            ]);
        } catch (\Exception $e) {
            Log::error('GET SERVICE CHEMICALS ERROR', [
                'appointment_id' => $request->appointment_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load service chemicals.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function serviceReports(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'status' => 'required|in:DONE SERVICE,DONE REPORT',
            'page' => 'nullable|integer|min:1',
        ]);

        try {
            // ============================================================
            // TECHNICIAN
            // ============================================================

            $technician = DB::table('users')
                ->where('usr_email', trim($request->email))
                ->first();

            if (!$technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician not found.',
                ], 404);
            }

            // ============================================================
            // PAGINATION
            // ============================================================

            $page = max(
                1,
                (int) ($request->page ?? 1)
            );

            // Mobile loads 3 reports at a time.
            $perPage = 3;

            // ============================================================
            // REPORT QUERY
            // ============================================================

            $reportsQuery = DB::table(
                'service_appointment_schedules as sched'
            )
                ->join(
                    'service_appointments as appt',
                    'sched.svca_id',
                    '=',
                    'appt.svca_id'
                )
                ->leftJoin(
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
                ->leftJoin(
                    'users as client',
                    'addr.usr_id',
                    '=',
                    'client.usr_id'
                )
                ->join(
                    'service_appointment_schedule_assigns as assign',
                    'sched.svcas_id',
                    '=',
                    'assign.svcas_id'
                )
                ->where(
                    'assign.svcasa_assigned_to',
                    $technician->usr_id
                )
                ->where(
                    'assign.svcasa_active',
                    1
                )
                ->where(
                    'appt.svca_status',
                    $request->status
                )
                ->select(
                    'sched.svcas_id',
                    'sched.svca_id',
                    'sched.svcas_end_date',
                    'sched.svcas_end_time',
                    'sched.svcas_time_travel',
                    'sched.svcas_time_finished',

                    'appt.svc_id',
                    'appt.svca_status',
                    'appt.svca_approved_date',
                    'appt.svca_approved_time_from',
                    'appt.svca_approved_time_to',

                    'svc.svc_sa_number',
                    'svc.svc_is_termite',
                    'svc.svc_technician_recommendation',
                    'svc.svc_assessment_recommendation',
                    'svc.svc_rat_captured',
                    'svc.svc_snake_captured',
                    'svc.svc_lizard_captured',
                    'svc.svc_kitten_captured',
                    'svc.svc_other_captured',
                    'svc.svc_date_modified',

                    'client.usr_first_name',
                    'client.usr_last_name',

                    'addr.uadd_street',
                    'addr.uadd_barangay',
                    'addr.uadd_city',
                    'addr.uadd_province'
                )
                ->orderByDesc(
                    'sched.svcas_end_date'
                )
                ->orderByDesc(
                    'sched.svcas_end_time'
                );

            // ============================================================
            // SERVER-SIDE PAGINATION
            // ============================================================

            $paginator = $reportsQuery->simplePaginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

            $reports = collect(
                $paginator->items()
            );

            // ============================================================
            // PRELOAD PESTS FOR CURRENT PAGE ONLY
            // ============================================================

            $serviceIds = $reports
                ->pluck('svc_id')
                ->filter()
                ->unique()
                ->values();

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
            // FORMAT REPORTS
            // ============================================================

            $formattedReports = $reports
                ->map(
                    function ($report) use (
                        $pestsByService
                    ) {
                        // CLIENT
                        $firstName = ucwords(
                            strtolower(
                                $report->usr_first_name ?? ''
                            )
                        );

                        $lastName = ucwords(
                            strtolower(
                                $report->usr_last_name ?? ''
                            )
                        );

                        $clientName = trim(
                            $firstName . ' ' . $lastName
                        );

                        // ADDRESS
                        $address = collect([
                            $report->uadd_street ?? null,
                            $report->uadd_barangay ?? null,
                            $report->uadd_city ?? null,
                            $report->uadd_province ?? null,
                        ])
                            ->filter(
                                fn ($part) =>
                                    $part !== null &&
                                    trim($part) !== ''
                            )
                            ->implode(', ');

                        // PESTS
                        $pests = collect(
                            $pestsByService->get(
                                $report->svc_id,
                                collect()
                            )
                        )
                            ->pluck(
                                'svcp_pest_type'
                            )
                            ->toArray();

                        // SERVICE NAME
                        $isTermite =
                            (int) (
                                $report->svc_is_termite ?? 0
                            ) === 1;

                        if ($isTermite) {
                            $serviceName =
                                'Termite Control';
                        } elseif (
                            count($pests) === 1
                        ) {
                            $serviceName =
                                $this->formatPestName(
                                    $pests[0]
                                ) .
                                ' Control';
                        } elseif (
                            count($pests) > 1
                        ) {
                            $serviceName =
                                'General Pest Control';
                        } else {
                            $serviceName =
                                'Pest Control Service';
                        }

                        // ============================================================
                        // RETURN REPORT
                        // ============================================================

                        return [
                            'id' =>
                                $report->svcas_id,

                            'appointment_id' =>
                                $report->svca_id,

                            'svc_sa_number' =>
                                $report->svc_sa_number ?? null,

                            'clientName' =>
                                $clientName !== ''
                                    ? $clientName
                                    : 'Client',

                            'service' =>
                                $serviceName,

                            'pestType' =>
                                !empty($pests)
                                    ? implode(
                                        ', ',
                                        $pests
                                    )
                                    : 'General Pest',

                            'address' =>
                                $address !== ''
                                    ? $address
                                    : 'No address',

                            'schedule' =>
                                $this->formatApprovedSchedule(
                                    $report->svca_approved_date,
                                    $report->svca_approved_time_from,
                                    $report->svca_approved_time_to
                                ),

                            'svca_approved_date' =>
                                $report->svca_approved_date,

                            'svca_approved_time_from' =>
                                $report->svca_approved_time_from,

                            'svca_approved_time_to' =>
                                $report->svca_approved_time_to,

                            'travelTime' =>
                                $report->svcas_time_travel
                                    ?? 'N/A',

                            'serviceTime' =>
                                $this->computeServiceTime(
                                    $report->svcas_time_finished,
                                    $report->svcas_time_travel
                                ),

                            'totalDuration' =>
                                $report->svcas_time_finished
                                    ?? 'N/A',

                            'timeFinished' =>
                                $report->svcas_time_finished
                                    ?? 'N/A',

                            'submittedDate' =>
                                $report->svc_date_modified
                                    ? $this->formatDate(
                                        $report->svc_date_modified
                                    )
                                    : '-',

                            'status' =>
                                $report->svca_status,

                            'svc_assessment_recommendation' => $report->svc_assessment_recommendation ?? '',

                            'recommendation' =>
                                $report
                                    ->svc_technician_recommendation
                                    ?? '',

                            'ratCaptured' =>
                                $report
                                    ->svc_rat_captured
                                    ?? 0,

                            'snakeCaptured' =>
                                $report
                                    ->svc_snake_captured
                                    ?? 0,

                            'lizardCaptured' =>
                                $report
                                    ->svc_lizard_captured
                                    ?? 0,

                            'kittenCaptured' =>
                                $report
                                    ->svc_kitten_captured
                                    ?? 0,

                            'otherCaptured' =>
                                $report
                                    ->svc_other_captured
                                    ?? 0,
                        ];
                    }
                )
                ->values();

            // ============================================================
            // RESPONSE
            // ============================================================

            return response()->json([
                'success' => true,

                'status' =>
                    $request->status,

                'pagination' => [
                    'current_page' =>
                        $page,

                    'per_page' =>
                        $perPage,

                    'has_more' =>
                        $paginator
                            ->hasMorePages(),

                    'next_page' =>
                        $paginator
                            ->hasMorePages()
                            ? $page + 1
                            : null,
                ],

                'count' =>
                    $formattedReports
                        ->count(),

                'data' =>
                    $formattedReports,
            ]);

        } catch (\Exception $e) {
            Log::error(
                'TECHNICIAN SERVICE REPORTS ERROR',
                [
                    'email' =>
                        $request->email,

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
                    'Unable to load service reports.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    public function serviceReportDetails(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'schedule_id' => 'required|integer',
        ]);

        try {
            // ============================================================
            // TECHNICIAN
            // ============================================================

            $technician = DB::table('users')
                ->where('usr_email', trim($request->email))
                ->first();

            if (!$technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician not found.',
                ], 404);
            }

            // ============================================================
            // MAIN REPORT
            // Only load ONE selected schedule.
            // Also verify that this technician is assigned to it.
            // ============================================================

            $report = DB::table('service_appointment_schedules as sched')
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
                ->leftJoin(
                    'users as client',
                    'addr.usr_id',
                    '=',
                    'client.usr_id'
                )
                ->join(
                    'service_appointment_schedule_assigns as assign',
                    'sched.svcas_id',
                    '=',
                    'assign.svcas_id'
                )
                ->where(
                    'sched.svcas_id',
                    $request->schedule_id
                )
                ->where(
                    'assign.svcasa_assigned_to',
                    $technician->usr_id
                )
                ->where('assign.svcasa_active', 1)
                ->where('appt.svca_status', 'DONE REPORT')
                ->select(
                    'sched.svcas_id',
                    'sched.svca_id',
                    'sched.svcas_time_travel',
                    'sched.svcas_time_finished',
                    'sched.svcas_start_date',
                    'sched.svcas_start_time',
                    'sched.svcas_end_date',
                    'sched.svcas_end_time',

                    'appt.svc_id',
                    'appt.svca_status',
                    'appt.svca_approved_date',
                    'appt.svca_approved_time_from',
                    'appt.svca_approved_time_to',
                    
                    'svc.svc_sa_number',
                    'svc.svc_is_termite',
                    'svc.svc_technician_recommendation',
                    'svc.svc_assessment_recommendation',
                    'svc.svc_rat_captured',
                    'svc.svc_snake_captured',
                    'svc.svc_lizard_captured',
                    'svc.svc_kitten_captured',
                    'svc.svc_other_captured',
                    'svc.svc_date_modified',

                    'client.usr_first_name',
                    'client.usr_last_name',

                    'addr.uadd_street',
                    'addr.uadd_barangay',
                    'addr.uadd_city',
                    'addr.uadd_province'
                )
                ->first();

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Completed service report not found.',
                ], 404);
            }

            // ============================================================
            // CLIENT NAME
            // ============================================================

            $firstName = ucwords(
                strtolower($report->usr_first_name ?? '')
            );

            $lastName = ucwords(
                strtolower($report->usr_last_name ?? '')
            );

            $clientName = trim(
                $firstName . ' ' . $lastName
            );

            // ============================================================
            // ADDRESS
            // ============================================================

            $address = collect([
                $report->uadd_street ?? null,
                $report->uadd_barangay ?? null,
                $report->uadd_city ?? null,
                $report->uadd_province ?? null,
            ])
                ->filter(
                    fn ($part) =>
                        $part !== null &&
                        trim($part) !== ''
                )
                ->implode(', ');

            // ============================================================
            // PESTS
            // ============================================================

            $pests = DB::table('service_order_pests as sop')
                ->join(
                    'service_packages as sp',
                    'sop.svcp_id',
                    '=',
                    'sp.svcp_id'
                )
                ->where(
                    'sop.svc_id',
                    $report->svc_id
                )
                ->where('sop.svcop_active', 1)
                ->where('sp.svcp_active', 1)
                ->pluck('sp.svcp_pest_type')
                ->toArray();

            $isTermite =
                (int) ($report->svc_is_termite ?? 0) === 1;

            if ($isTermite) {
                $serviceName = 'Termite Control';
            } elseif (count($pests) === 1) {
                $serviceName =
                    $this->formatPestName($pests[0]) .
                    ' Control';
            } elseif (count($pests) > 1) {
                $serviceName = 'General Pest Control';
            } else {
                $serviceName = 'Pest Control Service';
            }

            // ============================================================
            // CHEMICALS
            // ============================================================

            $chemicals = DB::table('service_chemicals as svcch')
                ->join(
                    'chemicals as chem',
                    'svcch.chem_id',
                    '=',
                    'chem.chem_id'
                )
                ->where(
                    'svcch.svc_id',
                    $report->svc_id
                )
                ->where('svcch.svcch_active', 1)
                ->where('chem.chem_active', 1)
                ->select(
                    'svcch.svcch_id',
                    'chem.chem_name',
                    'svcch.svcch_expected_quantity',
                    'svcch.svcch_actual_quantity',
                    'svcch.svcch_unit'
                )
                ->orderBy('svcch.svcch_id')
                ->get()
                ->map(function ($chemical) {
                    return [
                        'id' =>
                            $chemical->svcch_id,

                        'name' =>
                            $chemical->chem_name,

                        'expectedQuantity' =>
                            $chemical->svcch_expected_quantity,

                        'actualQuantity' =>
                            $chemical->svcch_actual_quantity,

                        'unit' =>
                            $chemical->svcch_unit ?? '',
                    ];
                })
                ->values();

            // ============================================================
            // SERVICE EVIDENCE
            // ============================================================

            $baseUrl = $request->getSchemeAndHttpHost();

            $images = DB::table(
                'service_appointment_schedule_images'
            )
                ->where(
                    'svcas_id',
                    $request->schedule_id
                )
                ->where('svcasp_active', 1)
                ->orderBy('svcasp_date_created')
                ->pluck('svcasp_image')
                ->map(function ($image) use ($baseUrl) {
                    return
                        $baseUrl .
                        '/images/technician_images/' .
                        rawurlencode($image);
                })
                ->values();

            // ============================================================
            // RESPONSE
            // ============================================================

            return response()->json([
                'success' => true,

                'data' => [
                    'id' =>
                        $report->svcas_id,

                    'appointment_id' =>
                        $report->svca_id,

                    'svc_sa_number' =>
                        $report->svc_sa_number ?? null,

                    'clientName' =>
                        $clientName !== ''
                            ? $clientName
                            : 'Client',

                    'service' =>
                        $serviceName,

                    'pestType' =>
                        !empty($pests)
                            ? implode(', ', $pests)
                            : 'General Pest',

                    'address' =>
                        $address !== ''
                            ? $address
                            : 'No address',

                    'schedule' =>
                        $this->formatApprovedSchedule(
                            $report->svca_approved_date,
                            $report->svca_approved_time_from,
                            $report->svca_approved_time_to
                        ),

                    'svca_approved_date' =>
                        $report->svca_approved_date,

                    'svca_approved_time_from' =>
                        $report->svca_approved_time_from,

                    'svca_approved_time_to' =>
                        $report->svca_approved_time_to,

                    'travelTime' =>
                        $report->svcas_time_travel
                            ?? 'N/A',

                    'serviceTime' =>
                        $this->computeServiceTime(
                            $report->svcas_time_finished,
                            $report->svcas_time_travel
                        ),

                    'totalDuration' =>
                        $report->svcas_time_finished
                            ?? 'N/A',

                    'submittedDate' =>
                        $report->svc_date_modified
                            ? $this->formatDate(
                                $report->svc_date_modified
                            )
                            : '-',

                    'status' =>
                        $report->svca_status,

                    'chemicals' =>
                        $chemicals,

                    'ratCaptured' =>
                        $report->svc_rat_captured ?? 0,

                    'snakeCaptured' =>
                        $report->svc_snake_captured ?? 0,

                    'lizardCaptured' =>
                        $report->svc_lizard_captured ?? 0,

                    'kittenCaptured' =>
                        $report->svc_kitten_captured ?? 0,

                    'otherCaptured' =>
                        $report->svc_other_captured ?? 0,

                    'images' =>
                        $images,

                    'svc_assessment_recommendation' => $report->svc_assessment_recommendation ?? '',

                    'recommendation' =>
                        $report->svc_technician_recommendation
                            ?? '',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error(
                'TECHNICIAN REPORT DETAILS ERROR',
                [
                    'email' =>
                        $request->email,

                    'schedule_id' =>
                        $request->schedule_id,

                    'error' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to load service report details.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    public function submitReport(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
            'schedule_id' => 'required|integer',
            'area_photos' => 'required|array|min:1|max:10',
            'area_photos.*' => 'mimes:jpeg,jpg,png,webp|max:8192',
            'recommendation' => 'nullable|string|max:250',
            'chemicals' => 'required|string',

            'rat_captured' => 'nullable|integer|min:0',
            'snake_captured' => 'nullable|integer|min:0',
            'lizard_captured' => 'nullable|integer|min:0',
            'kitten_captured' => 'nullable|integer|min:0',
            'other_captured' => 'nullable|integer|min:0',
        ]);
        
        $chemicals = json_decode($request->chemicals, true);

        if (!is_array($chemicals) || count($chemicals) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'At least one chemical entry is required.',
            ], 422);
        }

        $chemicalValidator = validator(
            ['chemicals' => $chemicals],
            [
                'chemicals' => 'required|array|min:1',
                'chemicals.*.svcch_id' => 'required|integer',
                'chemicals.*.svcch_actual_quantity' => 'required|numeric|min:0',
            ]
        );

        if ($chemicalValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid chemical information.',
                'errors' => $chemicalValidator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $appointment = DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->first();

            if (!$appointment) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment not found.',
                ], 404);
            }

            $schedule = DB::table('service_appointment_schedules')
                ->where('svcas_id', $request->schedule_id)
                ->where('svca_id', $request->appointment_id)
                ->first();

            if (!$schedule) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Service appointment schedule not found.',
                ], 404);
            }

            $assignedTechnicianId = $this->getAssignedTechnicianId($request->schedule_id);

            if (!$assignedTechnicianId) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Assigned technician not found for this schedule.',
                ], 404);
            }

            foreach ($chemicals as $chemical) {
                $serviceChemical = DB::table('service_chemicals')
                    ->where('svcch_id', $chemical['svcch_id'])
                    ->where('svc_id', $appointment->svc_id)
                    ->where('svcch_active', 1)
                    ->first();

                if (!$serviceChemical) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'An assigned chemical record was not found.',
                    ], 404);
                }

                DB::table('service_chemicals')
                    ->where('svcch_id', $chemical['svcch_id'])
                    ->where('svc_id', $appointment->svc_id)
                    ->update([
                        'svcch_actual_quantity' =>
                            $chemical['svcch_actual_quantity'],
                        'svcch_date_modified' => now(),
                        'svcch_modified_by' => $assignedTechnicianId,
                    ]);
            }

            if ($request->hasFile('area_photos')) {
                foreach ($request->file('area_photos') as $image) {
                    $fileName = uniqid() . '_' . $image->getClientOriginalName();
                    $folderPath = public_path('images/technician_images');

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

                    DB::table('service_appointment_schedule_images')->insert([
                        'svcasp_uuid' => generateuuid(),
                        'svcas_id' => $request->schedule_id,
                        'svcasp_image' => $fileName,
                        'svcasp_date_created' => now(),
                        'svcasp_created_by' => $assignedTechnicianId,
                        'svcasp_date_modified' => null,
                        'svcasp_modified_by' => null,
                        'svcasp_active' => 1,
                    ]);
                }
            }

            DB::table('services')
                ->where('svc_id', $appointment->svc_id)
                ->update([
                    'svc_status' => 'DONE REPORT',

                    'svc_rat_captured' => $request->rat_captured ?? 0,
                    'svc_snake_captured' => $request->snake_captured ?? 0,
                    'svc_lizard_captured' => $request->lizard_captured ?? 0,
                    'svc_kitten_captured' => $request->kitten_captured ?? 0,
                    'svc_other_captured' => $request->other_captured ?? 0,

                    'svc_technician_recommendation' => strtoupper(trim($request->recommendation ?? '')),
                    'svc_date_modified' => now(),
                    'svc_modified_by' => $assignedTechnicianId,
                ]);

            DB::table('service_appointments')
                ->where('svca_id', $request->appointment_id)
                ->update([
                    'svca_status' => 'DONE REPORT',
                ]);

            DB::commit();

            // TECHNICIAN NOTIFICATION - REPORT SUBMITTED

            try {
                $message =
                    'Your service report has been submitted successfully.';

                DB::table('message_notifications')->insert([
                    'mesn_uuid' => generateuuid(),
                    'usr_id' => $assignedTechnicianId,
                    'mesn_name' => strtoupper($message),
                    'mesn_date_created' => now(),
                    'mesn_created_by' => $assignedTechnicianId,
                    'mesn_view' => '0',
                    'mesn_active' => 1,
                ]);

                Log::info('TECHNICIAN NOTIFICATION CREATED', [
                    'type' => 'REPORT_SUBMITTED',
                    'technician_user_id' => $assignedTechnicianId,
                    'appointment_id' => $request->appointment_id,
                    'schedule_id' => $request->schedule_id,
                ]);
            } catch (\Exception $notificationException) {
                Log::warning(
                    'TECHNICIAN REPORT SUBMITTED NOTIFICATION CREATE FAILED',
                    [
                        'technician_user_id' => $assignedTechnicianId,
                        'appointment_id' => $request->appointment_id,
                        'schedule_id' => $request->schedule_id,
                        'error' => $notificationException->getMessage(),
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully.',
                'status' => 'DONE REPORT',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unable to submit report.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
