<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function home()
    {
        $logins = DB::table('user_login_logs')
            ->select('users.usr_last_name', 'users.usr_first_name', 'users.usr_image_path', 'user_login_logs.*', DB::raw('max(user_login_logs.log_date) as log_date_max'))
            ->join('users', 'users.usr_id', 'user_login_logs.usr_id')
            ->orderBy('log_date_max', 'desc')
            ->groupby('user_login_logs.usr_id')
            ->limit(3)
            ->get();

        $announcements = DB::table('announcements')
            ->join('users', 'users.usr_id', 'announcements.ann_created_by')
            ->where('ann_active', '=', '1')
            ->orderBy('ann_date_created', 'DESC')
            ->limit(6)
            ->get();

        $sessionBranchId = session('branch_id');

        // Branch dropdown options (super admin only)
        $branchOptions = collect();
        if ($sessionBranchId == 1) {
            $branchOptions = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', '!=', 1)
                ->where('branch_active', 1)
                ->orderBy('branch_name')
                ->get();
        }

        // Initial dashboard payload: default range = 7 days, default branch = all (or own branch)
        $dashboard = $this->buildDashboardData(7, 'all');

        return view('home.main', array_merge(
            compact('logins', 'announcements', 'branchOptions', 'sessionBranchId'),
            $dashboard
        ));
    }

    public function dashboard_data(Request $request)
    {
        $range = (int) ($request->get('range', 7));
        if (!in_array($range, [7, 30, 90])) {
            $range = 7;
        }

        $branch = $request->get('branch', 'all'); // 'all' or a specific branch_id

        $data = $this->buildDashboardData($range, $branch);

        return response()->json($data);
    }

    /**
     * Shared builder for all dashboard data: pipeline counts, profiles,
     * payment rate, and graph datasets. Used by both home() and dashboard_data().
     *
     * @param int    $range  7 | 30 | 90
     * @param string $branch 'all' or a branch_id (only meaningful for session branch_id == 1)
     */

    private function buildDashboardData(int $range, $branch = 'all')
    {
        $sessionBranchId = session('branch_id');

        $dateFrom = Carbon::now()->subDays($range - 1)->startOfDay();
        $dateTo = Carbon::now()->endOfDay();

        // Resolve effective branch filter
        $effectiveBranchId = null; // null = no filter (all branches)

        if ($sessionBranchId != 1) {
            $effectiveBranchId = $sessionBranchId;
        } elseif ($branch !== 'all' && $branch !== null && $branch !== '') {
            $effectiveBranchId = $branch;
        }

        // 1. SERVICE ORDERS PIPELINE COUNTS
        $pipelineQuery = DB::table('services')
            ->where('services.svc_active', 1)
            ->whereBetween('services.svc_date_created', [$dateFrom, $dateTo]);

        if ($effectiveBranchId !== null) {
            $pipelineQuery->where('services.branch_id', $effectiveBranchId);
        }

        $statusCounts = (clone $pipelineQuery)
            ->select('services.svc_status', DB::raw('COUNT(*) as total'))
            ->groupBy('services.svc_status')
            ->pluck('total', 'svc_status');

        // Unassigned = SCHEDULED services with an ACTIVE schedule and NO active technician assigned
        // (same rule as the scheduling timeline)
        $unassignedQuery = DB::table('services')
            ->join('service_appointments', 'service_appointments.svc_id', '=', 'services.svc_id')
            ->join('service_appointment_schedules', function ($join) {
                $join->on('service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
                    ->where('service_appointment_schedules.svcas_active', 1);
            })
            ->where('services.svc_active', 1)
            ->where('services.svc_status', 'SCHEDULED')
            ->whereBetween('services.svc_date_created', [$dateFrom, $dateTo])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('service_appointment_schedule_assigns as a')
                    ->whereColumn('a.svcas_id', 'service_appointment_schedules.svcas_id')
                    ->where('a.svcasa_active', 1);
            });

        if ($effectiveBranchId !== null) {
            $unassignedQuery->where('services.branch_id', $effectiveBranchId);
        }

        $unassignedCount = $unassignedQuery->distinct()->count('services.svc_id');

        $pipeline = [
            'requested' => ($statusCounts['REQUESTED'] ?? 0) + ($statusCounts['CONFIRM ASSESSMENT'] ?? 0),
            'unassigned' => $unassignedCount,
            'scheduled' => $statusCounts['SCHEDULED'] ?? 0,
            'ongoing' => $statusCounts['ONGOING'] ?? 0,
            'with_report' => $statusCounts['DONE REPORT'] ?? 0,
            'completed' => $statusCounts['COMPLETED'] ?? 0,
        ];

        // 1b. CAPTURES SUMMARY (Rats, Snakes, Lizards, Kittens, Others)
        $capturesQuery = DB::table('services')
            ->where('services.svc_active', 1)
            ->whereBetween('services.svc_date_created', [$dateFrom, $dateTo]);

        if ($effectiveBranchId !== null) {
            $capturesQuery->where('services.branch_id', $effectiveBranchId);
        }

        $capturesRow = (clone $capturesQuery)
            ->select(
                DB::raw('SUM(services.svc_rat_captured) as rats'),
                DB::raw('SUM(services.svc_snake_captured) as snakes'),
                DB::raw('SUM(services.svc_lizard_captured) as lizards'),
                DB::raw('SUM(services.svc_kitten_captured) as kittens'),
                DB::raw('SUM(services.svc_other_captured) as others')
            )
            ->first();

        $captures = [
            'rats' => $capturesRow->rats ?? 0,
            'snakes' => $capturesRow->snakes ?? 0,
            'lizards' => $capturesRow->lizards ?? 0,
            'kittens' => $capturesRow->kittens ?? 0,
            'others' => $capturesRow->others ?? 0,
        ];

        // 2. ACTIVE PROFILES SUMMARY (Admins, Technicians, Clients, Branches)
        $adminsQuery = DB::table('users')
            ->where('users.utyp_id', '=', '1')
            ->where('users.usr_active', '=', '1');
        if ($effectiveBranchId !== null) {
            $adminsQuery->where('users.branch_id', $effectiveBranchId);
        }
        $adminsCount = $adminsQuery->count();

        $techniciansQuery = DB::table('users')
            ->where('users.utyp_id', '=', '2')
            ->where('users.usr_active', '=', '1');
        if ($effectiveBranchId !== null) {
            $techniciansQuery->where('users.branch_id', $effectiveBranchId);
        }
        $techniciansCount = $techniciansQuery->count();

        $clientsQuery = DB::table('users')
            ->where('users.utyp_id', '=', '3')
            ->where('users.usr_active', '=', '1');
        if ($effectiveBranchId !== null) {
            $clientsQuery->where('users.branch_id', $effectiveBranchId);
        }
        $clientsCount = $clientsQuery->count();

        if ($effectiveBranchId !== null) {
            $branchesCount = DB::table('branches')
                ->where('branch_id', $effectiveBranchId)
                ->where('branch_active', 1)
                ->count();
        } else {
            $branchesCount = DB::table('branches')
                ->where('branch_active', 1)
                ->count();
        }

        $profiles = [
            'admins' => $adminsCount,
            'technicians' => $techniciansCount,
            'clients' => $clientsCount,
            'branches' => $branchesCount,
        ];

        // 3. PAYMENT RATE / COLLECTIONS
        $collectedQuery = DB::table('service_payments')
            ->join('services', 'service_payments.svc_id', '=', 'services.svc_id')
            ->where('services.svc_active', 1)
            ->where('service_payments.spay_active', 1)
            ->where('service_payments.spay_status', 'APPROVED')
            ->whereBetween('service_payments.spay_date', [$dateFrom, $dateTo]);

        if ($effectiveBranchId !== null) {
            $collectedQuery->where('services.branch_id', $effectiveBranchId);
        }

        $collectedAmount = $collectedQuery->sum('service_payments.spay_amount');

        // Pending = outstanding balance on active services created within the selected range
        $pendingQuery = DB::table('services')
            ->where('services.svc_active', 1)
            ->whereBetween('services.svc_date_created', [$dateFrom, $dateTo]);

        if ($effectiveBranchId !== null) {
            $pendingQuery->where('services.branch_id', $effectiveBranchId);
        }

        $pendingAmount = $pendingQuery->sum('services.svc_balance');

        $totalPaymentAmount = $collectedAmount + $pendingAmount;

        $collectionRate = $totalPaymentAmount > 0
            ? round(($collectedAmount / $totalPaymentAmount) * 100, 1)
            : 0;

        $payments = [
            'total' => $totalPaymentAmount,
            'collected' => $collectedAmount,
            'pending' => $pendingAmount,
            'collection_rate' => $collectionRate,
        ];

        // 4. GRAPH DATA
        // 4a. Appointments trend (daily count across the selected range)
        $trendRaw = (clone $pipelineQuery)
            ->select(DB::raw('DATE(services.svc_date_created) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('DATE(services.svc_date_created)'))
            ->orderBy('day')
            ->pluck('total', 'day');

        $trendLabels = [];
        $trendData = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->format('Y-m-d');
            // Use a compact label for longer ranges to avoid x-axis crowding
            $trendLabels[] = $range > 30
                ? Carbon::now()->subDays($i)->format('M d')
                : Carbon::now()->subDays($i)->format('M d');
            $trendData[] = $trendRaw[$day] ?? 0;
        }

        // 4b. Service status breakdown (bar chart)
        $statusBreakdown = [
            'labels' => ['Requested', 'Unassigned', 'Scheduled', 'Ongoing', 'With Report', 'Completed'],
            'data' => [
                $pipeline['requested'],
                $pipeline['unassigned'],
                $pipeline['scheduled'],
                $pipeline['ongoing'],
                $pipeline['with_report'],
                $pipeline['completed'],
            ],
        ];

        // 4c. Top branches by appointment volume
        $topBranchesQuery = DB::table('services')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->where('services.svc_active', 1)
            ->whereBetween('services.svc_date_created', [$dateFrom, $dateTo]);

        if ($effectiveBranchId !== null) {
            $topBranchesQuery->where('services.branch_id', $effectiveBranchId);
        }

        $topBranchesRaw = $topBranchesQuery
            ->select('branches.branch_name', DB::raw('COUNT(*) as total'))
            ->groupBy('branches.branch_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topBranches = [
            'labels' => $topBranchesRaw->pluck('branch_name'),
            'data' => $topBranchesRaw->pluck('total'),
        ];

        // 4d. Payment collected vs pending (donut chart)
        $paymentBreakdown = [
            'labels' => ['Collected', 'Pending'],
            'data' => [$payments['collected'], $payments['pending']],
        ];

        return [
            'pipeline' => $pipeline,
            'captures' => $captures,
            'profiles' => $profiles,
            'payments' => $payments,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData,
            'statusBreakdown' => $statusBreakdown,
            'topBranches' => $topBranches,
            'paymentBreakdown' => $paymentBreakdown,
            'activeRange' => $range,
            'activeBranch' => $effectiveBranchId === null ? 'all' : (string) $effectiveBranchId,
        ];
    }
}