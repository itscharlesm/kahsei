<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    // SERVICE ORDER PAYMENT VIEW
    public function service_order_payment_view($svc_id)
    {
        $sessionBranchId = session('branch_id');

        // 1. Service / Service Order details
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->where('services.svc_id', $svc_id);

        if ($sessionBranchId != 1) {
            $display->where('services.branch_id', $sessionBranchId);
        }

        $display = $display->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_type_treatment',
            'services.svc_with_device',
            'services.svc_device_count',
            'services.svc_device_price',
            'services.svc_fixed_price',
            'services.svc_final_price',
            'services.svc_balance',
            'services.svc_payment_status',
            'users.usr_first_name',
            'users.usr_last_name',
            'branches.branch_id',
            'branches.branch_name'
        )->first();

        if (! $display) {
            abort(404, 'Service order not found.');
        }

        // 2. Pest types (package can have 1 or many)
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

        // 3. Service areas (non-termite) OR termite areas — never both
        $serviceAreas = collect();
        $termiteAreas = collect();

        if (! $display->svc_is_termite) {
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
        } else {
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
        }

        // 4. Payment history (svc_id, spay_active = 1)
        $payments = DB::table('service_payments')
            ->leftJoin('payment_types', 'service_payments.typ_id', '=', 'payment_types.typ_id')
            ->leftJoin('users as approved_user', 'service_payments.spay_approved_by', '=', 'approved_user.usr_id')
            ->leftJoin('users as voided_user', 'service_payments.spay_voided_by', '=', 'voided_user.usr_id')
            ->where('service_payments.svc_id', $svc_id)
            ->where('service_payments.spay_active', 1)
            ->select(
                'service_payments.spay_id',
                'service_payments.spay_date',
                'service_payments.spay_receipt_number',
                'service_payments.spay_reference_number',
                'service_payments.spay_amount',
                'service_payments.spay_final_amount',
                'service_payments.spay_is_vatable',
                'service_payments.spay_vatable_type',
                'service_payments.spay_vatable_percentage',
                'service_payments.spay_vatable_amount',
                'service_payments.spay_image_proof',
                'service_payments.spay_details',
                'service_payments.spay_status',
                'service_payments.typ_id',
                'payment_types.typ_name',
                'service_payments.spay_date_approved',
                'service_payments.spay_admin_details',
                'service_payments.spay_date_voided',
                'service_payments.spay_void_details',
                'approved_user.usr_first_name as approved_first_name',
                'approved_user.usr_last_name as approved_last_name',
                'voided_user.usr_first_name as voided_first_name',
                'voided_user.usr_last_name as voided_last_name'
            )
            ->orderBy('service_payments.spay_date', 'desc')
            ->get();

        // 5. Split payments by status for separate table display
        $paymentsForApproval = $payments->where('spay_status', 'FOR APPROVAL')->values();
        $paymentsApproved = $payments->where('spay_status', 'APPROVED')->values();
        $paymentsVoided = $payments->where('spay_status', 'VOIDED')->values();

        // 6. Fetch payment types for approve payment
        $paymentTypes = DB::table('payment_types')->get();

        return view('service_orders.accounting.appointments.payments', compact(
            'svc_id',
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'paymentsForApproval',
            'paymentsApproved',
            'paymentsVoided',
            'paymentTypes'
        ));
    }

    public function service_order_payment_view_add_payment(Request $request, $svc_id)
    {
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if (! $service) {
            session()->flash('errorMessage', 'Service order not found.');
            return redirect()->back();
        }

        $isVatable = $request->boolean('spay_is_vatable');

        $request->validate([
            'typ_id' => 'required',
            'spay_receipt_number' => 'required',
            'spay_date' => 'required|date',
            'spay_amount' => 'required|numeric|min:0.1|max:' . $service->svc_balance,
            'spay_reference_number' => 'nullable',
            'spay_admin_details' => 'nullable',
            'spay_vatable_type' => $isVatable ? 'required|in:WHOLE AMOUNT,PERCENTAGE' : 'nullable',
            'spay_vatable_percentage' => $isVatable && $request->spay_vatable_type === 'PERCENTAGE'
                ? 'required|numeric|min:0.01|max:100'
                : 'nullable|numeric',
            'spay_vatable_amount' => $isVatable && $request->spay_vatable_type === 'WHOLE AMOUNT'
                ? 'required|numeric|min:0'
                : 'nullable|numeric',
        ]);

        // Re-fetch to avoid acting on stale data
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if (! $service) {
            session()->flash('errorMessage', 'Service order not found.');
            return redirect()->back();
        }

        // Resolve VAT fields server-side — authoritative, ignores stray
        // submitted values if the checkbox wasn't actually checked
        $vatableType = null;
        $vatablePercentage = null;
        $vatableAmount = null;

        if ($isVatable) {
            $vatableType = $request->spay_vatable_type;

            if ($vatableType === 'PERCENTAGE') {
                $vatablePercentage = $request->spay_vatable_percentage;
                $vatableAmount = round($request->spay_amount * ($vatablePercentage / 100), 2);
            } elseif ($vatableType === 'WHOLE AMOUNT') {
                $vatableAmount = $request->spay_vatable_amount;
                $vatablePercentage = null;
            }
        }

        DB::table('service_payments')->insert([
            'spay_uuid' => generateuuid(),
            'svc_id' => $svc_id,
            'typ_id' => $request->typ_id,
            'spay_receipt_number' => $request->spay_receipt_number,
            'spay_reference_number' => $request->spay_reference_number,
            'spay_date' => $request->spay_date,
            'spay_amount' => $request->spay_amount,
            'spay_final_amount' => $request->spay_amount,
            'spay_details' => 'PROCESSED BY ADMIN',
            'spay_status' => 'APPROVED',
            'spay_admin_details' => $request->spay_admin_details ? strtoupper($request->spay_admin_details) : null,
            'spay_is_vatable' => $isVatable ? 1 : 0,
            'spay_vatable_type' => $vatableType,
            'spay_vatable_percentage' => $vatablePercentage,
            'spay_vatable_amount' => $vatableAmount,
            'spay_date_approved' => Carbon::now(),
            'spay_approved_by' => session('usr_id'),
            'spay_date_created' => Carbon::now(),
            'spay_created_by' => session('usr_id'),
            'spay_active' => 1,
        ]);

        // Deduct from service balance
        $newBalance = $service->svc_balance - $request->spay_amount;

        if ($newBalance == 0) {
            $paymentStatus = 'PAID';
        } elseif ($newBalance > 0) {
            $paymentStatus = 'INCOMPLETE PAYMENT';
        } else {
            $paymentStatus = 'EXCESS PAYMENT';
        }

        DB::table('services')
            ->where('svc_id', $svc_id)
            ->update([
                'svc_balance' => $newBalance,
                'svc_payment_status' => $paymentStatus,
                'svc_date_modified' => Carbon::now(),
                'svc_modified_by' => session('usr_id'),
            ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Payments',
            'Added Payment ' . $serviceOrder
        );

        session()->flash('successMessage', 'Payment added successfully.');

        return redirect()->back();
    }

    public function service_order_payment_view_approve(Request $request, $spay_id)
    {
        $isVatable = $request->boolean('spay_is_vatable');

        $request->validate([
            'typ_id' => 'required',
            'spay_receipt_number' => 'required',
            'spay_final_amount' => 'required|numeric',
            'spay_reference_number' => 'nullable',
            'spay_admin_details' => 'nullable',
            'spay_vatable_type' => $isVatable ? 'required|in:WHOLE AMOUNT,PERCENTAGE' : 'nullable',
            'spay_vatable_percentage' => $isVatable && $request->spay_vatable_type === 'PERCENTAGE'
                ? 'required|numeric|min:0.01|max:100'
                : 'nullable|numeric',
            'svc_vatable_amount' => $isVatable && $request->spay_vatable_type === 'WHOLE AMOUNT'
                ? 'required|numeric|min:0'
                : 'nullable|numeric',
        ]);

        $payment = DB::table('service_payments')
            ->where('spay_id', $spay_id)
            ->where('spay_status', 'FOR APPROVAL')
            ->where('spay_active', 1)
            ->first();

        if (! $payment) {
            session()->flash('errorMessage', 'Payment not found.');
            return redirect()->back();
        }

        $svc_id = $payment->svc_id;

        // Resolve VAT fields server-side — authoritative
        $vatableType = null;
        $vatablePercentage = null;
        $vatableAmount = null;

        if ($isVatable) {
            $vatableType = $request->spay_vatable_type;

            if ($vatableType === 'PERCENTAGE') {
                $vatablePercentage = $request->spay_vatable_percentage;
                $vatableAmount = round($request->spay_final_amount * ($vatablePercentage / 100), 2);
            } elseif ($vatableType === 'WHOLE AMOUNT') {
                $vatableAmount = $request->svc_vatable_amount;
                $vatablePercentage = null;
            }
        }

        DB::table('service_payments')
            ->where('spay_id', $spay_id)
            ->update([
                'typ_id' => $request->typ_id,
                'spay_receipt_number' => $request->spay_receipt_number,
                'spay_reference_number' => $request->spay_reference_number,
                'spay_final_amount' => $request->spay_final_amount,
                'spay_admin_details' => strtoupper($request->spay_admin_details),
                'spay_is_vatable' => $isVatable ? 1 : 0,
                'spay_vatable_type' => $vatableType,
                'spay_vatable_percentage' => $vatablePercentage,
                'spay_vatable_amount' => $vatableAmount,
                'spay_status' => 'APPROVED',
                'spay_date_approved' => Carbon::now(),
                'spay_approved_by' => session('usr_id'),
                'spay_date_modified' => Carbon::now(),
                'spay_modified_by' => session('usr_id'),
            ]);

        // Deduct from service balance
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if ($service) {
            $newBalance = $service->svc_balance - $request->spay_final_amount;

            if ($newBalance == 0) {
                $paymentStatus = 'PAID';
            } elseif ($newBalance > 0) {
                $paymentStatus = 'INCOMPLETE PAYMENT';
            } else {
                $paymentStatus = 'EXCESS PAYMENT';
            }

            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update([
                    'svc_balance' => $newBalance,
                    'svc_payment_status' => $paymentStatus,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]);
        }

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Payments',
            'Approved Payment ' . $serviceOrder
        );

        session()->flash('successMessage', 'Payment approved successfully.');

        return redirect()->back();
    }

    public function service_order_payment_view_void(Request $request, $spay_id)
    {
        $request->validate([
            'spay_void_details' => 'nullable|string|max:1000',
        ]);

        $payment = DB::table('service_payments')
            ->where('spay_id', $spay_id)
            ->where('spay_status', 'APPROVED')
            ->where('spay_active', 1)
            ->first();

        if (! $payment) {
            session()->flash('errorMessage', 'Approved payment not found.');
            return redirect()->back();
        }

        $svc_id = $payment->svc_id;

        // Void payment
        DB::table('service_payments')
            ->where('spay_id', $spay_id)
            ->update([
                'spay_status' => 'VOIDED',
                'spay_void_details' => strtoupper($request->spay_void_details),
                'spay_date_voided' => Carbon::now(),
                'spay_voided_by' => session('usr_id'),
                'spay_date_modified' => Carbon::now(),
                'spay_modified_by' => session('usr_id'),
            ]);

        // Add amount back to service balance
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if ($service) {
            $newBalance = $service->svc_balance + $payment->spay_final_amount;

            // Determine payment status
            if ($newBalance == 0) {
                $paymentStatus = 'PAID';
            } elseif ($newBalance > 0) {
                $paymentStatus = 'INCOMPLETE PAYMENT';
            } else {
                $paymentStatus = 'EXCESS PAYMENT';
            }

            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update([
                    'svc_balance' => $newBalance,
                    'svc_payment_status' => $paymentStatus,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]);
        }

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Payments',
            'Voided Payment ' . $serviceOrder
        );

        session()->flash('successMessage', 'Payment voided successfully.');

        return redirect()->back();
    }
    // END SERVICE PAYMENT VIEW

    // START CLIENT PAYMENT
    public function accounting_payments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionBranchId = session('branch_id');

        $query = DB::table('service_payments')
            ->leftJoin('services', 'service_payments.svc_id', '=', 'services.svc_id')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('payment_types', 'service_payments.typ_id', '=', 'payment_types.typ_id')
            ->where('service_payments.spay_status', 'FOR APPROVAL')
            ->where('service_payments.spay_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'service_payments.spay_id',
            'service_payments.svc_id',
            'service_payments.spay_date',
            'service_payments.spay_reference_number',
            'service_payments.spay_amount',
            'services.svc_sa_number',
            'users.usr_first_name',
            'users.usr_last_name',
            'payment_types.typ_name'
        );

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('payment_types.typ_name', 'LIKE', "%$search%")
                    ->orWhere('service_payments.spay_reference_number', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_payments.spay_date', 'desc');

        $payments = $query->paginate(500);

        return view('service_orders.accounting.payments.payments', compact('payments', 'search'));
    }

    public function accounting_payment_details($spay_id)
    {
        // 1. Get exact payment FIRST (FIXED)
        $payment = DB::table('service_payments')
            ->where('spay_id', $spay_id)
            ->where('spay_status', 'FOR APPROVAL')
            ->where('spay_active', 1)
            ->first();

        if (! $payment) {
            return response('<p class="text-center text-muted py-4">Payment not found.</p>');
        }

        $svc_id = $payment->svc_id;

        // 2. Service details
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
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_start_time',
                'service_appointment_schedules.svcas_end_date',
                'service_appointment_schedules.svcas_time_travel',
                'service_appointment_schedules.svcas_time_finished',
                'service_appointment_schedules.svcas_end_time',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'service_appointment_schedules.svcas_date_assigned',
                'service_appointment_schedules.svcas_id',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        if (! $display) {
            return response('<p class="text-center text-muted py-4">Service order not found.</p>');
        }

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

        // 3. Payment types
        $paymentTypes = DB::table('payment_types')->get();

        // 4. Pest types
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

        // 5. Service areas
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

        // 6. Termite areas
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

        // 7. Appointment images
        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        // 8. Technician images
        $technicianImages = DB::table('service_appointment_schedule_images')
            ->join('service_appointment_schedules', 'service_appointment_schedule_images.svcas_id', '=', 'service_appointment_schedules.svcas_id')
            ->join('service_appointments', 'service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_schedule_images.svcasp_active', 1)
            ->select('service_appointment_schedule_images.*')
            ->get();

        // 9. Mapping Images
        $mappingImages = DB::table('service_mapping_images')
            ->where('svc_id', $svc_id)
            ->where('svcmp_active', 1)
            ->orderByDesc('svcmp_date_created')
            ->get();

        // 10. Mapping Images
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

        // 11. Chemicals used for this service
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

        return view('service_orders.accounting.payments.payment_details', compact(
            'display',
            'payment',
            'paymentTypes',
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

    public function accounting_payment_approve(Request $request, $svc_id)
    {
        $isVatable = $request->boolean('spay_is_vatable');

        $request->validate([
            'spay_id' => 'required',
            'typ_id' => 'required',
            'spay_receipt_number' => 'required',
            'spay_final_amount' => 'required|numeric',
            'spay_reference_number' => 'nullable',
            'spay_admin_details' => 'nullable',
            'spay_vatable_type' => $isVatable ? 'required|in:WHOLE AMOUNT,PERCENTAGE' : 'nullable',
            'spay_vatable_percentage' => $isVatable && $request->spay_vatable_type === 'PERCENTAGE'
                ? 'required|numeric|min:0.01|max:100'
                : 'nullable|numeric',
            'svc_vatable_amount' => $isVatable && $request->spay_vatable_type === 'WHOLE AMOUNT'
                ? 'required|numeric|min:0'
                : 'nullable|numeric',
        ]);

        $payment = DB::table('service_payments')
            ->where('spay_id', $request->spay_id)
            ->where('svc_id', $svc_id)
            ->where('spay_status', 'FOR APPROVAL')
            ->where('spay_active', 1)
            ->first();

        if (! $payment) {
            session()->flash('errorMessage', 'Payment not found.');
            return redirect()->back();
        }

        // Resolve VAT fields server-side — authoritative, ignores stray
        // submitted values if the checkbox wasn't actually checked
        $vatableType = null;
        $vatablePercentage = null;
        $vatableAmount = null;

        if ($isVatable) {
            $vatableType = $request->spay_vatable_type;

            if ($vatableType === 'PERCENTAGE') {
                $vatablePercentage = $request->spay_vatable_percentage;
                $vatableAmount = round($request->spay_final_amount * ($vatablePercentage / 100), 2);
            } elseif ($vatableType === 'WHOLE AMOUNT') {
                $vatableAmount = $request->svc_vatable_amount;
                $vatablePercentage = null;
            }
        }

        // Update payment
        DB::table('service_payments')
            ->where('spay_id', $request->spay_id)
            ->update([
                'typ_id' => $request->typ_id,
                'spay_receipt_number' => $request->spay_receipt_number,
                'spay_reference_number' => $request->spay_reference_number,
                'spay_final_amount' => $request->spay_final_amount,
                'spay_admin_details' => strtoupper($request->spay_admin_details),
                'spay_is_vatable' => $isVatable ? 1 : 0,
                'spay_vatable_type' => $vatableType,
                'spay_vatable_percentage' => $vatablePercentage,
                'spay_vatable_amount' => $vatableAmount,
                'spay_status' => 'APPROVED',
                'spay_date_approved' => Carbon::now(),
                'spay_approved_by' => session('usr_id'),
                'spay_date_modified' => Carbon::now(),
                'spay_modified_by' => session('usr_id'),
            ]);

        // Deduct from service balance
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if ($service) {
            $newBalance = $service->svc_balance - $request->spay_final_amount;

            // Determine payment status
            if ($newBalance == 0) {
                $paymentStatus = 'PAID';
            } elseif ($newBalance > 0) {
                $paymentStatus = 'INCOMPLETE PAYMENT';
            } else {
                $paymentStatus = 'EXCESS PAYMENT';
            }

            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update([
                    'svc_balance' => $newBalance,
                    'svc_payment_status' => $paymentStatus,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]);
        }

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Payments',
            'Approved Payment ' . $serviceOrder
        );

        session()->flash('successMessage', 'Payment approved successfully.');

        return redirect()->back();
    }

    public function accounting_history(Request $request)
    {
        $search = $request->search ?? '';
        $sessionBranchId = session('branch_id');

        $query = DB::table('service_payments')
            ->leftJoin('services', 'service_payments.svc_id', '=', 'services.svc_id')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('payment_types', 'service_payments.typ_id', '=', 'payment_types.typ_id')
            ->where('service_payments.spay_status', 'APPROVED')
            ->where('service_payments.spay_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'service_payments.spay_receipt_number',
            'service_payments.spay_id',
            'service_payments.svc_id',
            'service_payments.spay_date',
            'service_payments.spay_reference_number',
            'service_payments.spay_final_amount',
            'service_payments.spay_vatable_amount',
            'service_payments.spay_details',
            'services.svc_sa_number',
            'users.usr_first_name',
            'users.usr_last_name',
            'payment_types.typ_name'
        );

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('payment_types.typ_name', 'LIKE', "%$search%")
                    ->orWhere('service_payments.spay_reference_number', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_payments.spay_date', 'desc');

        $payments = $query->paginate(500);

        return view('service_orders.accounting.history.history', compact('payments', 'search'));
    }

    public function accounting_history_details($spay_id)
    {
        // 1. Get exact payment FIRST (FIXED)
        $payment = DB::table('service_payments')
            ->leftJoin('users as approved_by_user', 'service_payments.spay_approved_by', '=', 'approved_by_user.usr_id')
            ->where('service_payments.spay_id', $spay_id)
            ->where('service_payments.spay_status', 'APPROVED')
            ->where('service_payments.spay_active', 1)
            ->select(
                'service_payments.*',
                'approved_by_user.usr_first_name as approved_first_name',
                'approved_by_user.usr_last_name as approved_last_name'
            )
            ->first();

        if (! $payment) {
            return response('<p class="text-center text-muted py-4">Payment not found.</p>');
        }

        $svc_id = $payment->svc_id;

        // 2. Service details
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
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_start_time',
                'service_appointment_schedules.svcas_end_date',
                'service_appointment_schedules.svcas_time_travel',
                'service_appointment_schedules.svcas_time_finished',
                'service_appointment_schedules.svcas_end_time',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'service_appointment_schedules.svcas_date_assigned',
                'service_appointment_schedules.svcas_id',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        if (! $display) {
            return response('<p class="text-center text-muted py-4">Service order not found.</p>');
        }

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

        // 3. Payment types
        $paymentTypes = DB::table('payment_types')->get();

        // 4. Pest types
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

        // 5. Service areas
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

        // 6. Termite areas
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

        // 7. Appointment images
        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        // 8. Technician images
        $technicianImages = DB::table('service_appointment_schedule_images')
            ->join('service_appointment_schedules', 'service_appointment_schedule_images.svcas_id', '=', 'service_appointment_schedules.svcas_id')
            ->join('service_appointments', 'service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_schedule_images.svcasp_active', 1)
            ->select('service_appointment_schedule_images.*')
            ->get();

        // 9. Mapping Images
        $mappingImages = DB::table('service_mapping_images')
            ->where('svc_id', $svc_id)
            ->where('svcmp_active', 1)
            ->orderByDesc('svcmp_date_created')
            ->get();

        // 10. Mapping Images
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

        // 11. Chemicals used for this service
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

        return view('service_orders.accounting.history.payment_details', compact(
            'display',
            'payment',
            'paymentTypes',
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

    public function accounting_payment_void(Request $request, $svc_id)
    {
        $request->validate([
            'spay_id' => 'required',
            'spay_void_details' => 'nullable|string|max:1000',
        ]);

        $payment = DB::table('service_payments')
            ->where('spay_id', $request->spay_id)
            ->where('svc_id', $svc_id)
            ->where('spay_status', 'APPROVED')
            ->where('spay_active', 1)
            ->first();

        if (! $payment) {
            session()->flash('errorMessage', 'Approved payment not found.');
            return redirect()->back();
        }

        // Void payment
        DB::table('service_payments')
            ->where('spay_id', $request->spay_id)
            ->update([
                'spay_status' => 'VOIDED',
                'spay_void_details' => strtoupper($request->spay_void_details),
                'spay_date_voided' => Carbon::now(),
                'spay_voided_by' => session('usr_id'),
                'spay_date_modified' => Carbon::now(),
                'spay_modified_by' => session('usr_id'),
            ]);

        // Add amount back to service balance
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->first();

        if ($service) {
            $newBalance = $service->svc_balance + $payment->spay_final_amount;

            // Determine payment status
            if ($newBalance == 0) {
                $paymentStatus = 'PAID';
            } elseif ($newBalance > 0) {
                $paymentStatus = 'INCOMPLETE PAYMENT';
            } else {
                $paymentStatus = 'EXCESS PAYMENT';
            }

            DB::table('services')
                ->where('svc_id', $svc_id)
                ->update([
                    'svc_balance' => $newBalance,
                    'svc_payment_status' => $paymentStatus,
                    'svc_date_modified' => Carbon::now(),
                    'svc_modified_by' => session('usr_id'),
                ]);
        }

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Payments',
            'Voided Payment ' . $serviceOrder
        );

        session()->flash('successMessage', 'Payment voided successfully.');

        return redirect()->back();
    }

    public function accounting_voided(Request $request)
    {
        $search = $request->search ?? '';
        $sessionBranchId = session('branch_id');

        $query = DB::table('service_payments')
            ->leftJoin('services', 'service_payments.svc_id', '=', 'services.svc_id')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('payment_types', 'service_payments.typ_id', '=', 'payment_types.typ_id')
            ->where('service_payments.spay_status', 'VOIDED')
            ->where('service_payments.spay_active', 1);

        if ($sessionBranchId != 1) {
            $query->where('services.branch_id', $sessionBranchId);
        }

        $query->select(
            'service_payments.spay_receipt_number',
            'service_payments.spay_id',
            'service_payments.svc_id',
            'service_payments.spay_date_voided',
            'service_payments.spay_reference_number',
            'service_payments.spay_final_amount',
            'service_payments.spay_vatable_amount',
            'service_payments.spay_details',
            'services.svc_sa_number',
            'users.usr_first_name',
            'users.usr_last_name',
            'payment_types.typ_name',
        );

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.usr_first_name', 'LIKE', "%$search%")
                    ->orWhere('users.usr_last_name', 'LIKE', "%$search%")
                    ->orWhere('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('payment_types.typ_name', 'LIKE', "%$search%")
                    ->orWhere('service_payments.spay_reference_number', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_payments.spay_date', 'desc');

        $payments = $query->paginate(500);

        return view('service_orders.accounting.voided.voided', compact('payments', 'search'));
    }

    public function accounting_voided_details($spay_id)
    {
        // 1. Get exact payment FIRST (FIXED)
        $payment = DB::table('service_payments')
            ->leftJoin('users as voided_by_user', 'service_payments.spay_voided_by', '=', 'voided_by_user.usr_id')
            ->where('service_payments.spay_id', $spay_id)
            ->where('service_payments.spay_status', 'VOIDED')
            ->where('service_payments.spay_active', 1)
            ->select(
                'service_payments.*',
                'voided_by_user.usr_first_name as voided_first_name',
                'voided_by_user.usr_last_name as voided_last_name'
            )
            ->first();

        if (! $payment) {
            return response('<p class="text-center text-muted py-4">Payment not found.</p>');
        }

        $svc_id = $payment->svc_id;

        // 2. Service details
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
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_start_time',
                'service_appointment_schedules.svcas_end_date',
                'service_appointment_schedules.svcas_time_travel',
                'service_appointment_schedules.svcas_time_finished',
                'service_appointment_schedules.svcas_end_time',
                'approver.usr_first_name as approved_first_name',
                'approver.usr_last_name as approved_last_name',
                'service_appointment_schedules.svcas_date_assigned',
                'service_appointment_schedules.svcas_id',
                'user_addresses.uadd_street',
                'user_addresses.uadd_barangay',
                'user_addresses.uadd_city',
                'user_addresses.uadd_province',
                'user_addresses.uadd_region',
                'addresses.add_name'
            )
            ->first();

        if (! $display) {
            return response('<p class="text-center text-muted py-4">Service order not found.</p>');
        }

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

        // 3. Payment types
        $paymentTypes = DB::table('payment_types')->get();

        // 4. Pest types
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

        // 5. Service areas
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

        // 6. Termite areas
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

        // 7. Appointment images
        $appointmentImages = DB::table('service_appointment_images')
            ->join('service_appointments', 'service_appointment_images.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_images.svcap_active', 1)
            ->select('service_appointment_images.*')
            ->get();

        // 8. Technician images
        $technicianImages = DB::table('service_appointment_schedule_images')
            ->join('service_appointment_schedules', 'service_appointment_schedule_images.svcas_id', '=', 'service_appointment_schedules.svcas_id')
            ->join('service_appointments', 'service_appointment_schedules.svca_id', '=', 'service_appointments.svca_id')
            ->where('service_appointments.svc_id', $svc_id)
            ->where('service_appointment_schedule_images.svcasp_active', 1)
            ->select('service_appointment_schedule_images.*')
            ->get();

        // 9. Mapping Images
        $mappingImages = DB::table('service_mapping_images')
            ->where('svc_id', $svc_id)
            ->where('svcmp_active', 1)
            ->orderByDesc('svcmp_date_created')
            ->get();

        // 10. Mapping Images
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

        // 11. Chemicals used for this service
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

        return view('service_orders.accounting.voided.payment_details', compact(
            'display',
            'payment',
            'paymentTypes',
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
    // END CLIENT PAYMENT
}