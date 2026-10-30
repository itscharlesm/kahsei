<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // START BALANCE REPORT
    public function balance_report()
    {
        $services = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $filtered = false;

        $totalBalance = 0;
        $totalFixedPrice = 0;
        $totalFinalPrice = 0;
        $totalCollected = 0;
        $totalDiscount = 0;
        $collectionRate = 0;
        $recordCount = 0;
        $outstandingCount = 0;
        $fullyPaidCount = 0;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        $paymentStatuses = DB::table('services')
            ->whereNotNull('svc_payment_status')
            ->distinct()
            ->orderBy('svc_payment_status')
            ->pluck('svc_payment_status');

        if ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.balance', compact(
            'services',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalBalance',
            'totalFixedPrice',
            'totalFinalPrice',
            'totalCollected',
            'totalDiscount',
            'collectionRate',
            'recordCount',
            'outstandingCount',
            'fullyPaidCount',
            'branches',
            'paymentStatuses',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_balance_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $svc_payment_status = $request->input('svc_payment_status');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        $query = DB::table('services')
            ->join('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->where('services.svc_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($svc_payment_status) {
            $query->where('services.svc_payment_status', $svc_payment_status);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_appointments.svca_approved_date', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_appointments.svca_approved_date', '>=', $date_from)
                ->whereDate('service_appointments.svca_approved_date', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $weekYear, (int) $weekNumber)
                ->startOfWeek();

            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_appointments.svca_approved_date', '>=', $startOfWeek->toDateString())
                ->whereDate('service_appointments.svca_approved_date', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_appointments.svca_approved_date', $year)
                ->whereMonth('service_appointments.svca_approved_date', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_appointments.svca_approved_date', $year);
        }

        $services = $query
            ->orderBy('service_appointments.svca_approved_date', 'ASC')
            ->orderBy('services.svc_sa_number', 'ASC')
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_status',
                'services.svc_payment_status',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'service_appointments.svca_approved_date',
                'users.usr_first_name',
                'users.usr_last_name'
            )
            ->get();

        $totalBalance = $services->sum(fn($s) => $s->svc_balance ?? 0);
        $totalFixedPrice = $services->sum(fn($s) => $s->svc_fixed_price ?? 0);
        $totalFinalPrice = $services->sum(fn($s) => $s->svc_final_price ?? 0);
        $totalCollected = $services->sum(fn($s) => ($s->svc_final_price ?? 0) - ($s->svc_balance ?? 0));
        $totalDiscount = $services->sum(fn($s) => ($s->svc_fixed_price ?? 0) - ($s->svc_final_price ?? 0));
        $collectionRate = $totalFinalPrice > 0 ? ($totalCollected / $totalFinalPrice) * 100 : 0;
        $recordCount = $services->count();
        $outstandingCount = $services->filter(fn($s) => ($s->svc_balance ?? 0) > 0)->count();
        $fullyPaidCount = $services->filter(fn($s) => ($s->svc_balance ?? 0) <= 0)->count();

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        $paymentStatuses = DB::table('services')
            ->whereNotNull('svc_payment_status')
            ->distinct()
            ->orderBy('svc_payment_status')
            ->pluck('svc_payment_status');

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $branch_id)
                ->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.balance', compact(
            'services',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalBalance',
            'totalFixedPrice',
            'totalFinalPrice',
            'totalCollected',
            'totalDiscount',
            'collectionRate',
            'recordCount',
            'outstandingCount',
            'fullyPaidCount',
            'branches',
            'paymentStatuses',
            'selectedBranch',
            'filtered'
        ));
    }
    // END BALANCE REPORT

    // START INCOME REPORT
    public function income_report()
    {
        $payments = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $totalIncome = 0;
        $filtered = false;
        $totalIncome = 0;
        $totalVatable = 0;
        $totalNonVatable = 0;
        $vatableCount = 0;
        $nonVatableCount = 0;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        $paymentTypes = DB::table('payment_types')
            ->where('typ_active', 1)
            ->select('typ_id', 'typ_name')
            ->orderBy('typ_name')
            ->get();

        if ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.income', compact(
            'payments',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalIncome',
            'totalVatable',
            'totalNonVatable',
            'vatableCount',
            'nonVatableCount',
            'branches',
            'paymentTypes',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_income_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $typ_id = $request->input('typ_id');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        $query = DB::table('service_payments')
            ->leftJoin('payment_types', 'service_payments.typ_id', '=', 'payment_types.typ_id')
            ->leftJoin('services', 'service_payments.svc_id', '=', 'services.svc_id')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->where('service_payments.spay_active', 1)
            ->where('service_payments.spay_status', 'APPROVED');

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($typ_id) {
            $query->where('service_payments.typ_id', $typ_id);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_payments.spay_date_approved', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_payments.spay_date_approved', '>=', $date_from)
                ->whereDate('service_payments.spay_date_approved', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $weekYear, (int) $weekNumber)
                ->startOfWeek();

            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_payments.spay_date_approved', '>=', $startOfWeek->toDateString())
                ->whereDate('service_payments.spay_date_approved', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_payments.spay_date_approved', $year)
                ->whereMonth('service_payments.spay_date_approved', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_payments.spay_date_approved', $year);
        }

        $payments = $query
            ->orderBy('service_payments.spay_date', 'ASC')
            ->orderBy('services.svc_sa_number', 'ASC')
            ->select(
                'service_payments.spay_id',
                'service_payments.spay_date',
                'service_payments.spay_date_approved',
                'service_payments.spay_receipt_number',
                'service_payments.spay_reference_number',
                'service_payments.spay_final_amount',
                'service_payments.spay_vatable_amount',
                'service_payments.spay_is_vatable',
                'service_payments.typ_id',
                'payment_types.typ_name',
                'services.svc_id',
                'services.svc_sa_number',
                'branches.branch_name',
                'users.usr_first_name',
                'users.usr_last_name'
            )
            ->get();

        $totalIncome = $payments->sum(fn($payment) => $payment->spay_final_amount ?? 0);
        $totalVatable = $payments->where('spay_is_vatable', 1)->sum(fn($payment) => $payment->spay_vatable_amount ?? 0);
        $totalNonVatable = $totalIncome - $totalVatable;
        $vatableCount = $payments->where('spay_is_vatable', 1)->count();
        $nonVatableCount = $payments->where('spay_is_vatable', 0)->count();

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        $paymentTypes = DB::table('payment_types')
            ->where('typ_active', 1)
            ->select('typ_id', 'typ_name')
            ->orderBy('typ_name')
            ->get();

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $branch_id)
                ->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.income', compact(
            'payments',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalIncome',
            'totalVatable',
            'totalNonVatable',
            'vatableCount',
            'nonVatableCount',
            'branches',
            'paymentTypes',
            'selectedBranch',
            'filtered'
        ));
    }
    // END INCOME REPORT

    // START VOID REPORT
    public function void_report()
    {
        $payments = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $totalVoidedAmount = 0;
        $filtered = false;
        $totalIncome = 0;
        $totalVatable = 0;
        $totalNonVatable = 0;
        $vatableCount = 0;
        $nonVatableCount = 0;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        $paymentTypes = DB::table('payment_types')
            ->where('typ_active', 1)
            ->select('typ_id', 'typ_name')
            ->orderBy('typ_name')
            ->get();

        if ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.void', compact(
            'payments',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalVoidedAmount',
            'totalVatable',
            'totalNonVatable',
            'vatableCount',
            'nonVatableCount',
            'branches',
            'paymentTypes',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_void_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $typ_id = $request->input('typ_id');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        $query = DB::table('service_payments')
            ->leftJoin('payment_types', 'service_payments.typ_id', '=', 'payment_types.typ_id')
            ->leftJoin('services', 'service_payments.svc_id', '=', 'services.svc_id')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->where('service_payments.spay_active', 1)
            ->where('service_payments.spay_status', 'VOIDED');

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($typ_id) {
            $query->where('service_payments.typ_id', $typ_id);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_payments.spay_date_voided', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_payments.spay_date_voided', '>=', $date_from)
                ->whereDate('service_payments.spay_date_voided', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $weekYear, (int) $weekNumber)
                ->startOfWeek();

            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_payments.spay_date_voided', '>=', $startOfWeek->toDateString())
                ->whereDate('service_payments.spay_date_voided', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_payments.spay_date_voided', $year)
                ->whereMonth('service_payments.spay_date_voided', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_payments.spay_date_voided', $year);
        }

        $payments = $query
            ->orderBy('service_payments.spay_date', 'ASC')
            ->orderBy('services.svc_sa_number', 'ASC')
            ->select(
                'service_payments.spay_id',
                'service_payments.spay_date',
                'service_payments.spay_date_voided',
                'service_payments.spay_receipt_number',
                'service_payments.spay_reference_number',
                'service_payments.spay_final_amount',
                'service_payments.spay_vatable_amount',
                'service_payments.spay_is_vatable',
                'service_payments.typ_id',
                'payment_types.typ_name',
                'services.svc_id',
                'services.svc_sa_number',
                'branches.branch_name',
                'users.usr_first_name',
                'users.usr_last_name'
            )
            ->get();

        $totalVoidedAmount = $payments->sum(fn($payment) => $payment->spay_final_amount ?? 0);
        $totalVatable = $payments->where('spay_is_vatable', 1)->sum(fn($payment) => $payment->spay_vatable_amount ?? 0);
        $totalNonVatable = $totalVoidedAmount - $totalVatable;
        $vatableCount = $payments->where('spay_is_vatable', 1)->count();
        $nonVatableCount = $payments->where('spay_is_vatable', 0)->count();

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        $paymentTypes = DB::table('payment_types')
            ->where('typ_active', 1)
            ->select('typ_id', 'typ_name')
            ->orderBy('typ_name')
            ->get();

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $branch_id)
                ->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.void', compact(
            'payments',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalVoidedAmount',
            'totalVatable',
            'totalNonVatable',
            'vatableCount',
            'nonVatableCount',
            'branches',
            'paymentTypes',
            'selectedBranch',
            'filtered'
        ));
    }
    // END VOID REPORT

    // START CLIENT REPORT
    public function client_report()
    {
        $groupedServices = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $filtered = false;

        $totalClients = 0;
        $totalServiceOrders = 0;
        $totalActiveOrders = 0;
        $totalDeletedOrders = 0;
        $totalBalance = 0;
        $totalDiscount = 0;
        $totalEarned = 0;
        $totalRats = 0;
        $totalSnakes = 0;
        $totalLizard = 0;
        $totalKittens = 0;
        $totalOthers = 0;
        $totalPestsCaptured = 0;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.client', compact(
            'groupedServices',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalClients',
            'totalServiceOrders',
            'totalActiveOrders',
            'totalDeletedOrders',
            'totalBalance',
            'totalDiscount',
            'totalEarned',
            'totalRats',
            'totalSnakes',
            'totalLizard',
            'totalKittens',
            'totalOthers',
            'totalPestsCaptured',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_client_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        // NOTE: no svc_active filter here on purpose — we want to show
        // both active and deleted orders per client in a single pass.
        $query = DB::table('services')
            ->join('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->join('users', 'services.usr_id', '=', 'users.usr_id');

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_appointments.svca_approved_date', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_appointments.svca_approved_date', '>=', $date_from)
                ->whereDate('service_appointments.svca_approved_date', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $weekYear, (int) $weekNumber)
                ->startOfWeek();

            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_appointments.svca_approved_date', '>=', $startOfWeek->toDateString())
                ->whereDate('service_appointments.svca_approved_date', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_appointments.svca_approved_date', $year)
                ->whereMonth('service_appointments.svca_approved_date', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_appointments.svca_approved_date', $year);
        }

        $services = $query
            ->orderBy('users.usr_last_name')
            ->orderBy('users.usr_first_name')
            ->orderBy('service_appointments.svca_approved_date')
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_status',
                'services.svc_active',
                'services.svc_payment_status',
                'services.svc_fixed_price',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_rat_captured',
                'services.svc_snake_captured',
                'services.svc_lizard_captured',
                'services.svc_kitten_captured',
                'services.svc_other_captured',
                'service_appointments.svca_approved_date',
                'users.usr_id',
                'users.usr_first_name',
                'users.usr_last_name',
                'users.usr_email',
                'users.usr_mobile'
            )
            ->get();

        // Group per-SA rows under each client. Order is preserved from the
        // query above, so groups stay sorted by client name.
        $groupedServices = $services->groupBy('usr_id');

        $totalClients = $groupedServices->count();
        $totalServiceOrders = $services->count();
        $totalActiveOrders = $services->where('svc_active', 1)->count();
        $totalDeletedOrders = $services->where('svc_active', 0)->count();
        $totalBalance = $services->where('svc_active', 1)->sum('svc_balance');
        $totalDiscount = $services->where('svc_active', 1)->sum(fn($s) => $s->svc_fixed_price - $s->svc_final_price);
        $totalEarned = $services->where('svc_active', 1)->sum(fn($s) => $s->svc_final_price - $s->svc_balance);

        $totalRats = $services->where('svc_active', 1)->sum(fn($s) => $s->svc_rat_captured ?? 0);
        $totalSnakes = $services->where('svc_active', 1)->sum(fn($s) => $s->svc_snake_captured ?? 0);
        $totalLizard = $services->where('svc_active', 1)->sum(fn($s) => $s->svc_lizard_captured ?? 0);
        $totalKittens = $services->where('svc_active', 1)->sum(fn($s) => $s->svc_kitten_captured ?? 0);
        $totalOthers = $services->where('svc_active', 1)->sum(fn($s) => $s->svc_other_captured ?? 0);
        $totalPestsCaptured = $totalRats + $totalSnakes + $totalLizard + $totalKittens + $totalOthers;

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $branch_id)
                ->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.client', compact(
            'groupedServices',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalClients',
            'totalServiceOrders',
            'totalActiveOrders',
            'totalDeletedOrders',
            'totalBalance',
            'totalDiscount',
            'totalEarned',
            'totalRats',
            'totalSnakes',
            'totalLizard',
            'totalKittens',
            'totalOthers',
            'totalPestsCaptured',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }
    // END CLIENT REPORT

    // START PEST CAPTURED REPORT
    public function pest_captured_report()
    {
        $captures = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $totalRats = 0;
        $totalSnakes = 0;
        $totalLizard = 0;
        $totalKittens = 0;
        $totalOthers = 0;
        $totalPests = 0;
        $filtered = false;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.pest', compact(
            'captures',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalRats',
            'totalSnakes',
            'totalLizard',
            'totalKittens',
            'totalOthers',
            'totalPests',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_pest_captured_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        $query = DB::table('services')
            ->join('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->where('services.svc_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_appointments.svca_date_approved', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_appointments.svca_date_approved', '>=', $date_from)
                ->whereDate('service_appointments.svca_date_approved', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $weekYear, (int) $weekNumber)
                ->startOfWeek();

            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_appointments.svca_date_approved', '>=', $startOfWeek->toDateString())
                ->whereDate('service_appointments.svca_date_approved', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_appointments.svca_date_approved', $year)
                ->whereMonth('service_appointments.svca_date_approved', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_appointments.svca_date_approved', $year);
        }

        $captures = $query
            ->orderBy('service_appointments.svca_date_approved', 'ASC')
            ->orderBy('services.svc_sa_number', 'ASC')
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_rat_captured',
                'services.svc_snake_captured',
                'services.svc_lizard_captured',
                'services.svc_kitten_captured',
                'services.svc_other_captured',
                'service_appointments.svca_date_approved'
            )
            ->get();

        $totalRats = $captures->sum(fn($c) => $c->svc_rat_captured ?? 0);
        $totalSnakes = $captures->sum(fn($c) => $c->svc_snake_captured ?? 0);
        $totalLizard = $captures->sum(fn($c) => $c->svc_lizard_captured ?? 0);
        $totalKittens = $captures->sum(fn($c) => $c->svc_kitten_captured ?? 0);
        $totalOthers = $captures->sum(fn($c) => $c->svc_other_captured ?? 0);
        $totalPests = $totalRats + $totalSnakes + $totalLizard + $totalKittens + $totalOthers;

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $branch_id)
                ->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.pest', compact(
            'captures',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalRats',
            'totalSnakes',
            'totalLizard',
            'totalKittens',
            'totalOthers',
            'totalPests',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }
    // END PEST CAPTURED REPORT

    // START SERVICE PACKAGE REPORT
    public function service_package_report()
    {
        $services = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $totalServices = 0;
        $totalTermiteCases = 0;
        $totalServicePackage = 0;
        $totalWithDevice = 0;
        $totalDeviceUsed = 0;
        $termiteRate = 0;
        $packageRate = 0;
        $filtered = false;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.service_package', compact(
            'services',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalServices',
            'totalTermiteCases',
            'totalServicePackage',
            'totalWithDevice',
            'totalDeviceUsed',
            'termiteRate',
            'packageRate',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_service_package_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $is_termite = $request->input('is_termite');
        $is_package = $request->input('is_package');
        $with_device = $request->input('with_device');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        $query = DB::table('services')
            ->join('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->where('services.svc_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_appointments.svca_date_approved', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_appointments.svca_date_approved', '>=', $date_from)
                ->whereDate('service_appointments.svca_date_approved', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $weekYear, (int) $weekNumber)
                ->startOfWeek();

            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_appointments.svca_date_approved', '>=', $startOfWeek->toDateString())
                ->whereDate('service_appointments.svca_date_approved', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_appointments.svca_date_approved', $year)
                ->whereMonth('service_appointments.svca_date_approved', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_appointments.svca_date_approved', $year);
        }

        if ($is_termite !== null && $is_termite !== '') {
            $query->where('services.svc_is_termite', $is_termite);
        }

        if ($is_package !== null && $is_package !== '') {
            $query->where('services.svc_is_package', $is_package);
        }

        if ($with_device !== null && $with_device !== '') {
            $query->where('services.svc_with_device', $with_device);
        }

        $services = $query
            ->orderBy('service_appointments.svca_date_approved', 'ASC')
            ->orderBy('services.svc_sa_number', 'ASC')
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_is_termite',
                'services.svc_is_package',
                'services.svc_with_device',
                'services.svc_device_count',
                'service_appointments.svca_date_approved'
            )
            ->get();

        $totalServices = $services->count();
        $totalTermiteCases = $services->where('svc_is_termite', 1)->count();
        $totalServicePackage = $services->where('svc_is_package', 1)->count();
        $totalWithDevice = $services->where('svc_with_device', 1)->count();
        $totalDeviceUsed = $services->sum(fn($s) => $s->svc_device_count ?? 0);

        $termiteRate = $totalServices > 0 ? round(($totalTermiteCases / $totalServices) * 100, 2) : 0;
        $packageRate = $totalServices > 0 ? round(($totalServicePackage / $totalServices) * 100, 2) : 0;

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $branch_id)
                ->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.service_package', compact(
            'services',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalServices',
            'totalTermiteCases',
            'totalServicePackage',
            'totalWithDevice',
            'totalDeviceUsed',
            'termiteRate',
            'packageRate',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }
    // END SERVICE PACKAGE REPORT

    // START CHEMICAL REPORT
    public function chemical_report()
    {
        $chemicals = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $filtered = false;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.chemical', compact(
            'chemicals',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_chemical_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        $query = DB::table('service_chemicals')
            ->join('chemicals', 'service_chemicals.chem_id', '=', 'chemicals.chem_id')
            ->join('services', 'service_chemicals.svc_id', '=', 'services.svc_id')
            ->where('service_chemicals.svcch_active', 1)
            ->where('services.svc_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_chemicals.svcch_date_created', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_chemicals.svcch_date_created', '>=', $date_from)
                ->whereDate('service_chemicals.svcch_date_created', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $weekYear, (int) $weekNumber)
                ->startOfWeek();

            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_chemicals.svcch_date_created', '>=', $startOfWeek->toDateString())
                ->whereDate('service_chemicals.svcch_date_created', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_chemicals.svcch_date_created', $year)
                ->whereMonth('service_chemicals.svcch_date_created', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_chemicals.svcch_date_created', $year);
        }

        $chemicals = $query
            ->select(
                'chemicals.chem_id',
                'chemicals.chem_name',
                'service_chemicals.svcch_unit',
                DB::raw('SUM(service_chemicals.svcch_expected_quantity) as total_allocated'),
                DB::raw('SUM(service_chemicals.svcch_actual_quantity) as total_actual')
            )
            ->groupBy('chemicals.chem_id', 'chemicals.chem_name', 'service_chemicals.svcch_unit')
            ->orderBy('chemicals.chem_name', 'ASC')
            ->orderBy('service_chemicals.svcch_unit', 'ASC')
            ->get()
            ->map(function ($row) {
                $row->variance = $row->total_actual - $row->total_allocated;
                $row->status = $row->variance > 0 ? 'EXCESS' : ($row->variance < 0 ? 'LACKING' : 'BALANCED');
                return $row;
            });

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $branch_id)
                ->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.chemical', compact(
            'chemicals',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }
    // END CHEMICAL REPORT

    // START TECHNICIAN REPORT
    public function technician_report()
    {
        $technicians = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $totalCompleted = 0;
        $totalAssigned = 0;
        $totalJobs = 0;
        $totalRating1 = 0;
        $totalRating2 = 0;
        $totalRating3 = 0;
        $totalRating4 = 0;
        $totalRating5 = 0;
        $overallAvgRating = null;
        $filtered = false;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.technician', compact(
            'technicians',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalCompleted',
            'totalAssigned',
            'totalJobs',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_technician_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        $query = DB::table('service_appointment_schedule_assigns')
            ->join('service_appointment_schedules', 'service_appointment_schedule_assigns.svcas_id', '=', 'service_appointment_schedules.svcas_id')
            ->join('service_appointments', 'service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
            ->join('services', 'service_appointments.svc_id', '=', 'services.svc_id')
            ->join('users', 'service_appointment_schedule_assigns.svcasa_assigned_to', '=', 'users.usr_id')
            ->where('service_appointment_schedule_assigns.svcasa_active', 1)
            ->where('services.svc_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_appointments.svca_date_approved', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_appointments.svca_date_approved', '>=', $date_from)
                ->whereDate('service_appointments.svca_date_approved', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()
                ->setISODate((int) $weekYear, (int) $weekNumber)
                ->startOfWeek();

            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_appointments.svca_date_approved', '>=', $startOfWeek->toDateString())
                ->whereDate('service_appointments.svca_date_approved', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_appointments.svca_date_approved', $year)
                ->whereMonth('service_appointments.svca_date_approved', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_appointments.svca_date_approved', $year);
        }

        $technicians = $query
            ->select(
                'users.usr_id',
                'users.usr_first_name',
                'users.usr_last_name',
                DB::raw("SUM(CASE WHEN service_appointments.svca_status IN ('DONE REPORT','COMPLETED') THEN 1 ELSE 0 END) as completed_jobs"),
                DB::raw("SUM(CASE WHEN service_appointments.svca_status = 'ASSIGNED' THEN 1 ELSE 0 END) as assigned_jobs"),
                DB::raw("SUM(CASE WHEN services.svc_rating = 1 THEN 1 ELSE 0 END) as rating_1"),
                DB::raw("SUM(CASE WHEN services.svc_rating = 2 THEN 1 ELSE 0 END) as rating_2"),
                DB::raw("SUM(CASE WHEN services.svc_rating = 3 THEN 1 ELSE 0 END) as rating_3"),
                DB::raw("SUM(CASE WHEN services.svc_rating = 4 THEN 1 ELSE 0 END) as rating_4"),
                DB::raw("SUM(CASE WHEN services.svc_rating = 5 THEN 1 ELSE 0 END) as rating_5"),
                DB::raw("AVG(services.svc_rating) as avg_rating")
            )
            ->groupBy('users.usr_id', 'users.usr_first_name', 'users.usr_last_name')
            ->orderBy('users.usr_last_name', 'ASC')
            ->get();

        $totalCompleted = $technicians->sum('completed_jobs');
        $totalAssigned = $technicians->sum('assigned_jobs');
        $totalJobs = $totalCompleted + $totalAssigned;

        $totalRating1 = $technicians->sum('rating_1');
        $totalRating2 = $technicians->sum('rating_2');
        $totalRating3 = $technicians->sum('rating_3');
        $totalRating4 = $technicians->sum('rating_4');
        $totalRating5 = $technicians->sum('rating_5');

        $totalRatingCount = $totalRating1 + $totalRating2 + $totalRating3 + $totalRating4 + $totalRating5;

        $overallAvgRating = $totalRatingCount > 0
            ? (($totalRating1 * 1) + ($totalRating2 * 2) + ($totalRating3 * 3) + ($totalRating4 * 4) + ($totalRating5 * 5)) / $totalRatingCount
            : null;

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $branch_id)
                ->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')
                ->select('branch_id', 'branch_name')
                ->where('branch_id', $sessionBranchId)
                ->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.technician', compact(
            'technicians',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalCompleted',
            'totalAssigned',
            'totalJobs',
            'totalRating1',
            'totalRating2',
            'totalRating3',
            'totalRating4',
            'totalRating5',
            'overallAvgRating',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }
    // END TECHNICIAN REPORT

    // START TURN AROUND TIME REPORT
    public function turn_around_time_report()
    {
        $records = collect();
        $filter_type = 'daily';
        $date = now()->toDateString();
        $date_from = now()->toDateString();
        $date_to = now()->toDateString();
        $week = now()->format('o-\WW');
        $month = now()->month;
        $year = now()->year;
        $filtered = false;

        $sessionBranchId = session('branch_id');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        $selectedBranch = $sessionBranchId != 1
            ? DB::table('branches')->select('branch_id', 'branch_name')->where('branch_id', $sessionBranchId)->first()
            : null;

        return view('reports.turn_around_time', compact(
            'records',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }

    public function filter_turn_around_time_report(Request $request)
    {
        $filter_type = $request->input('filter_type', 'daily');
        $date = $request->input('date');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $week = $request->input('week');
        $month = $request->input('month');
        $year = $request->input('year');
        $branch_id = $request->input('branch_id');
        $filtered = true;

        $sessionBranchId = session('branch_id');

        $query = DB::table('service_appointment_schedules')
            ->join('service_appointments', 'service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
            ->join('services', 'service_appointments.svc_id', '=', 'services.svc_id')
            ->where('services.svc_active', 1)
            ->whereNotNull('service_appointment_schedules.svcas_time_finished')
            ->where('service_appointment_schedules.svcas_time_finished', '!=', '00:00:00');

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        } elseif ($branch_id) {
            $query->where('services.branch_id', $branch_id);
        }

        if ($filter_type == 'daily' && $date) {
            $query->whereDate('service_appointment_schedules.svcas_start_date', $date);

        } elseif ($filter_type == 'date_range' && $date_from && $date_to) {
            $query->whereDate('service_appointment_schedules.svcas_start_date', '>=', $date_from)
                ->whereDate('service_appointment_schedules.svcas_start_date', '<=', $date_to);

        } elseif ($filter_type == 'weekly' && $week) {
            [$weekYear, $weekNumber] = explode('-W', $week);

            $startOfWeek = Carbon::now()->setISODate((int) $weekYear, (int) $weekNumber)->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $query->whereDate('service_appointment_schedules.svcas_start_date', '>=', $startOfWeek->toDateString())
                ->whereDate('service_appointment_schedules.svcas_start_date', '<=', $endOfWeek->toDateString());

        } elseif ($filter_type == 'monthly' && $month && $year) {
            $query->whereYear('service_appointment_schedules.svcas_start_date', $year)
                ->whereMonth('service_appointment_schedules.svcas_start_date', $month);

        } elseif ($filter_type == 'yearly' && $year) {
            $query->whereYear('service_appointment_schedules.svcas_start_date', $year);
        }

        $records = $query
            ->orderBy('service_appointment_schedules.svcas_start_date', 'ASC')
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'service_appointments.svca_id',
                'service_appointments.svca_approved_date',
                'service_appointments.svca_approved_time_from',
                'service_appointments.svca_approved_time_to',
                'service_appointment_schedules.svcas_id',
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_time_travel',
                'service_appointment_schedules.svcas_time_finished'
            )
            ->get();

        // Batch-fetch assigned technicians for all schedules in this result set
        $svcasIds = $records->pluck('svcas_id')->unique()->values()->all();

        $assigns = DB::table('service_appointment_schedule_assigns')
            ->join('users', 'service_appointment_schedule_assigns.svcasa_assigned_to', '=', 'users.usr_id')
            ->whereIn('service_appointment_schedule_assigns.svcas_id', $svcasIds)
            ->where('service_appointment_schedule_assigns.svcasa_active', 1)
            ->select(
                'service_appointment_schedule_assigns.svcas_id',
                'users.usr_id',
                'users.usr_first_name',
                'users.usr_last_name'
            )
            ->get()
            ->groupBy('svcas_id');

        // Attach raw technician label only — all seconds/variance/status math happens in the Blade view
        foreach ($records as $row) {
            $techs = $assigns->get($row->svcas_id, collect());

            $row->technician_label = $techs->isEmpty()
                ? 'Unassigned'
                : $techs->map(fn($t) => $t->usr_last_name . ', ' . $t->usr_first_name)->implode('; ');

            $row->technicians = $techs; // raw list, used by the view to build per-technician stats
        }

        $month = $month ?: date('m');
        $year = $year ?: date('Y');
        $date = $date ?: now()->toDateString();
        $date_from = $date_from ?: now()->toDateString();
        $date_to = $date_to ?: now()->toDateString();
        $week = $week ?: now()->format('o-\WW');

        $branches = DB::table('branches')
            ->select('branch_id', 'branch_name')
            ->orderBy('branch_name')
            ->where('branch_id', '!=', 1)
            ->where('branch_active', 1)
            ->get();

        if ($sessionBranchId == 1 && $branch_id) {
            $selectedBranch = DB::table('branches')->select('branch_id', 'branch_name')->where('branch_id', $branch_id)->first();
        } elseif ($sessionBranchId != 1) {
            $selectedBranch = DB::table('branches')->select('branch_id', 'branch_name')->where('branch_id', $sessionBranchId)->first();
        } else {
            $selectedBranch = null;
        }

        return view('reports.turn_around_time', compact(
            'records',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'branches',
            'selectedBranch',
            'filtered'
        ));
    }
    // END TURN AROUND REPORT
}