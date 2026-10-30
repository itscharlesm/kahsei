<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use App\Http\Controllers\api\v1\mobile_controllers\MobileMapboxDistanceController;

class ClientController extends Controller
{
    // START REGISTRATION
    public function registration()
    {
        $branches = DB::table('branches')
            ->where('branch_active', 1)
            ->where('branch_id', '!=', 1)
            ->get();

        return view('client.registration', compact('branches'));
    }

    public function registration_store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'usr_first_name' => 'required|string|max:255',
            'usr_middle_name' => 'nullable|string|max:255',
            'usr_last_name' => 'required|string|max:255',
            'usr_mobile' => ['required', 'regex:/^9[0-9]{9}$/'],
            'usr_email' => 'required|email|max:255|unique:users,usr_email',
            'usr_password' => 'required|string|min:6',
            'usr_password_confirmation' => 'required|same:usr_password',
        ], [
            'branch_id.required' => 'Please select a branch.',
            'usr_mobile.regex' => 'Mobile number must be 10 digits and start with 9 (e.g. 9123456789).',
            'usr_email.unique' => 'This email address is already registered. Please use a different email or log in.',
            'usr_password_confirmation.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            alert()->error('Invalid Input', $validator->errors()->first());
            return redirect()->back()->withInput();
        }

        DB::table('users')->insert([
            'usr_uuid' => generateuuid(),
            'branch_id' => $request->branch_id,
            'usr_first_name' => strtoupper($request->usr_first_name),
            'usr_middle_name' => $request->usr_middle_name ? strtoupper($request->usr_middle_name) : null,
            'usr_last_name' => strtoupper($request->usr_last_name),
            'usr_mobile' => $request->usr_mobile,
            'usr_email' => $request->usr_email,
            'usr_password' => md5($request->usr_password),
            'utyp_id' => 3,
            'usr_date_created' => Carbon::now(),
            'usr_created_by' => session('usr_id'),
            'usr_active' => 1,
        ]);

        logUserActivity('Client Registration', 'New client account registered: ' . $request->usr_email);

        alert()->success('Account Created', 'Account created successfully. You may now log in.');
        return redirect('/');
    }
    // END REGISTRATION

    // START HOME
    public function home(Request $request)
    {
        $sessionUsrId = session('usr_id');
        $allowedPeriods = ['all', '30', '90', 'year'];
        $period = (string) $request->query('period', 'all');

        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'all';
        }

        $dateFrom = match ($period) {
            '30' => Carbon::now()->subDays(29)->startOfDay(),
            '90' => Carbon::now()->subDays(89)->startOfDay(),
            'year' => Carbon::now()->startOfYear(),
            default => null,
        };

        $dateTo = $dateFrom ? Carbon::now()->endOfDay() : null;

        $servicesQuery = DB::table('services')
            ->where('services.svc_active', 1)
            ->where('services.usr_id', $sessionUsrId);

        if ($dateFrom && $dateTo) {
            $servicesQuery->whereBetween('services.svc_date_created', [$dateFrom, $dateTo]);
        }

        $servicesAvailed = (clone $servicesQuery)->count();

        $statusCounts = (clone $servicesQuery)
            ->select('services.svc_status', DB::raw('COUNT(*) as total'))
            ->groupBy('services.svc_status')
            ->pluck('total', 'svc_status');

        $counts = [
            'requested' => ($statusCounts['REQUESTED'] ?? 0) + ($statusCounts['CONFIRM ASSESSMENT'] ?? 0),
            'assessed' => $statusCounts['ASSESSED'] ?? 0,
            'scheduled' => ($statusCounts['SCHEDULED'] ?? 0) + ($statusCounts['ASSIGNED'] ?? 0),
            'ongoing' => ($statusCounts['ONGOING'] ?? 0) + ($statusCounts['DONE SERVICE'] ?? 0),
            'finished' => $statusCounts['DONE REPORT'] ?? 0,
            'completed' => $statusCounts['COMPLETED'] ?? 0,
        ];

        $financialSummary = (clone $servicesQuery)
            ->whereNotNull('services.svc_final_price')
            ->selectRaw(
                'COALESCE(SUM(COALESCE(services.svc_fixed_price, services.svc_final_price)), 0) as total_fixed_price,
                COALESCE(SUM(services.svc_final_price), 0) as total_final_price,
                COALESCE(SUM(
                    CASE
                        WHEN COALESCE(services.svc_balance, 0) <= 0 THEN services.svc_final_price
                        WHEN services.svc_balance >= services.svc_final_price THEN 0
                        ELSE services.svc_final_price - services.svc_balance
                    END
                ), 0) as amount_paid,
                COALESCE(SUM(
                    CASE WHEN services.svc_balance > 0 THEN services.svc_balance ELSE 0 END
                ), 0) as outstanding_balance,
                COALESCE(SUM(
                    CASE WHEN services.svc_balance < 0 THEN ABS(services.svc_balance) ELSE 0 END
                ), 0) as client_credit'
            )
            ->first();

        $outstandingServices = (clone $servicesQuery)
            ->whereNotNull('services.svc_final_price')
            ->where('services.svc_balance', '>', 0)
            ->select(
                'services.svc_id',
                'services.svc_sa_number',
                'services.svc_is_termite',
                'services.svc_is_package',
                'services.svc_type_treatment',
                'services.svc_final_price',
                'services.svc_balance',
                'services.svc_payment_status',
                'services.svc_date_created'
            )
            ->orderByDesc('services.svc_balance')
            ->orderByDesc('services.svc_date_created')
            ->limit(5)
            ->get();

        $pestLabels = collect();
        $outstandingServiceIds = $outstandingServices->pluck('svc_id');

        if ($outstandingServiceIds->isNotEmpty()) {
            $pestLabels = DB::table('service_order_pests')
                ->join('service_packages', 'service_order_pests.svcp_id', '=', 'service_packages.svcp_id')
                ->whereIn('service_order_pests.svc_id', $outstandingServiceIds)
                ->where('service_order_pests.svcop_active', 1)
                ->where('service_packages.svcp_active', 1)
                ->orderBy('service_packages.svcp_pest_type')
                ->get([
                    'service_order_pests.svc_id',
                    'service_packages.svcp_pest_type',
                ])
                ->groupBy('svc_id')
                ->map(fn($items) => $items->pluck('svcp_pest_type')->filter()->unique()->implode(', '));
        }

        $outstandingServices->transform(function ($service) use ($pestLabels) {
            $positiveBalance = max((float) $service->svc_balance, 0);
            $service->paid_amount = max(
                min((float) $service->svc_final_price - $positiveBalance, (float) $service->svc_final_price),
                0
            );

            $service->service_label = $pestLabels->get($service->svc_id);

            if (!$service->service_label) {
                $service->service_label = match (true) {
                    (bool) $service->svc_is_termite => 'Termite Treatment',
                    !empty($service->svc_type_treatment) => $service->svc_type_treatment,
                    (bool) $service->svc_is_package => 'Pest Control Package',
                    default => 'Pest Control Service',
                };
            }

            return $service;
        });

        $paymentQuery = DB::table('service_payments')
            ->join('services', 'service_payments.svc_id', '=', 'services.svc_id')
            ->leftJoin('payment_types', 'service_payments.typ_id', '=', 'payment_types.typ_id')
            ->where('service_payments.spay_active', 1)
            ->where('services.svc_active', 1)
            ->where('services.usr_id', $sessionUsrId);

        if ($dateFrom && $dateTo) {
            $paymentQuery->whereBetween('services.svc_date_created', [$dateFrom, $dateTo]);
        }

        $pendingPayments = (clone $paymentQuery)
            ->where('service_payments.spay_status', 'FOR APPROVAL')
            ->selectRaw(
                'COUNT(*) as total_count,
                COALESCE(SUM(service_payments.spay_amount), 0) as total_amount'
            )
            ->first();

        $paymentActivity = (clone $paymentQuery)
            ->select(
                'service_payments.spay_id',
                'service_payments.spay_date',
                'service_payments.spay_date_created',
                'service_payments.spay_amount',
                'service_payments.spay_final_amount',
                'service_payments.spay_status',
                'services.svc_id',
                'services.svc_sa_number',
                'payment_types.typ_name'
            )
            ->orderByDesc('service_payments.spay_date_created')
            ->orderByDesc('service_payments.spay_id')
            ->limit(5)
            ->get()
            ->each(function ($payment) {
                $payment->display_amount = $payment->spay_final_amount ?? $payment->spay_amount;
            });

        $periodLabels = [
            'all' => 'All Time',
            '30' => 'Last 30 Days',
            '90' => 'Last 90 Days',
            'year' => 'This Year',
        ];

        return view('client.home', compact(
            'period',
            'periodLabels',
            'servicesAvailed',
            'counts',
            'financialSummary',
            'outstandingServices',
            'pendingPayments',
            'paymentActivity'
        ));
    }
    // END HOME

    // START ACCOUNT
    public function account(Request $request)
    {
        $user = DB::table('users')
            ->where('usr_id', session('usr_id'))
            ->first();

        $regions = DB::table('location_regions')
            ->where('reg_active', 1)
            ->orderBy('reg_name')
            ->get(['reg_id', 'reg_name']);

        $addresses = DB::table('user_addresses as ua')
            ->leftJoin('addresses as a', 'ua.add_id', '=', 'a.add_id')
            ->where('ua.usr_id', session('usr_id'))
            ->select('ua.*', 'a.add_name')
            ->get();

        $address_labels = DB::table('addresses')
            ->orderBy('add_name')
            ->get(['add_id', 'add_name']);

        return view('client.account', compact('user', 'regions', 'addresses', 'address_labels'));
    }

    public function account_update(Request $request)
    {
        $updateData = [
            'usr_first_name' => strtoupper($request->usr_first_name),
            'usr_middle_name' => strtoupper($request->usr_middle_name),
            'usr_last_name' => strtoupper($request->usr_last_name),
            'usr_birth_date' => $request->usr_birth_date,
        ];

        // AVATAR UPLOAD
        if ($request->hasFile('avatar')) {

            $request->validate([
                'avatar' => 'mimes:jpeg,jpg,png,webp|max:8192'
            ]);

            $file = $request->file('avatar');
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $path = public_path('images/users/' . $fileName);
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

            $updateData['usr_image_path'] = $fileName;
        }

        DB::table('users')
            ->where('usr_id', session('usr_id'))
            ->update($updateData);

        return back()->with('successMessage', 'Account updated successfully.');
    }

    public function account_address_add(Request $request)
    {
        $request->validate([
            'add_id' => 'nullable|integer|exists:addresses,add_id',
            'street' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
        ]);

        DB::table('user_addresses')->insert([
            'uadd_uuid' => generateuuid(),
            'usr_id' => session('usr_id'),
            'add_id' => $request->add_id ?: null,
            'uadd_street' => normalizeAddressStreet($request->street),
            'uadd_barangay' => strtoupper($request->barangay),
            'uadd_city' => strtoupper($request->municipality),
            'uadd_province' => strtoupper($request->province),
            'uadd_region' => strtoupper($request->region),
            'uadd_latitude' => $request->latitude,
            'uadd_longitude' => $request->longitude,
            'uadd_active' => 1,
        ]);

        return back()->with('successMessage', 'Address added successfully.');
    }

    public function account_address_edit(Request $request)
    {
        $request->validate([
            'uadd_id' => 'required|integer',
            'street' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
        ]);

        $updateData = [
            'uadd_street' => normalizeAddressStreet($request->street),
            'uadd_active' => $request->has('uadd_active') ? 1 : 0,
        ];

        if ($request->region)
            $updateData['uadd_region'] = strtoupper($request->region);
        if ($request->province)
            $updateData['uadd_province'] = strtoupper($request->province);
        if ($request->municipality)
            $updateData['uadd_city'] = strtoupper($request->municipality);
        if ($request->barangay)
            $updateData['uadd_barangay'] = strtoupper($request->barangay);

        // Only overwrite coordinates if the user actually repinned
        if ($request->filled('latitude'))
            $updateData['uadd_latitude'] = $request->latitude;
        if ($request->filled('longitude'))
            $updateData['uadd_longitude'] = $request->longitude;

        DB::table('user_addresses')
            ->where('uadd_id', $request->uadd_id)
            ->where('usr_id', session('usr_id'))
            ->update($updateData);

        return back()->with('successMessage', 'Address updated successfully.');
    }

    public function account_update_password(Request $request)
    {
        $current_password = $request->current_password;
        $new_password1 = $request->new_password1;
        $new_password2 = $request->new_password2;

        $user = DB::table('users')
            ->where('usr_id', '=', session('usr_id'))
            ->first();

        if (md5($current_password) == $user->usr_password) {
            if ($new_password1 == $new_password2) {

                DB::table('users')
                    ->where('usr_id', '=', session('usr_id'))
                    ->update([
                        'usr_password' => md5($new_password1)
                    ]);

                alert()->success('Success', 'User password has been changed.');
            } else {
                alert()->warning('Warning', 'Password did not matched.');
            }
        } else {
            alert()->warning('Warning', 'Incorrect user password.');
        }
        return redirect()->action([ClientController::class, 'home']);
    }
    // END ACCOUNT

    // START BOOK APPOINTMENT
    public function appointment_book()
    {
        $usrId = session('usr_id');
        $branchId = session('branch_id');

        $addresses = DB::table('user_addresses')
            ->leftJoin('addresses', 'user_addresses.add_id', '=', 'addresses.add_id')
            ->where('user_addresses.usr_id', $usrId)
            ->where('user_addresses.uadd_active', 1)
            ->select('user_addresses.*', 'addresses.add_name')
            ->get();

        $servicePackages = DB::table('service_packages')->get();

        $areaOptionsForJs = DB::table('service_package_areas')
            ->where('svcpa_active', 1)
            ->where('branch_id', $branchId)
            ->select('svcpa_id as id', 'svcpa_area as label', 'svcpa_cost as cost')
            ->get();

        $termiteOptionsForJs = DB::table('service_package_area_termites')
            ->where('svcpat_active', 1)
            ->where('branch_id', $branchId)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->svcpat_id,
                    'label' => $t->svcpat_sqm_details . ' (₱' . number_format($t->svcpat_cost, 2) . ')',
                    'cost' => $t->svcpat_cost,
                ];
            })
            ->values();

        return view('client.appointments.book_appointment', compact('addresses', 'servicePackages', 'areaOptionsForJs', 'termiteOptionsForJs'));
    }

    public function appointment_book_save(Request $request)
    {
        $request->validate([
            'uadd_id' => 'required|integer',
            'svcp_id' => 'required|integer',
            'svca_client_date' => 'required|date',
            'svca_client_time' => 'required',
            'svc_problem_description' => 'nullable|string',
            'svcpa_id' => 'nullable|array',
            'svcpa_id.*' => 'integer',
            'svcpat_id' => 'nullable|integer',
            'extra_svcp_id' => 'nullable|array',
            'extra_svcp_id.*' => 'required|integer|not_in:8',
        ]);

        $usrId = session('usr_id');
        $branchId = session('branch_id');
        $isTermite = (int) $request->svcp_id === 8 ? 1 : 0;

        $selectedAreaIds = $isTermite ? [] : array_filter($request->input('svcpa_id', []));
        $selectedTermiteId = $isTermite ? $request->input('svcpat_id') : null;

        // Extra Problem Types are not allowed when the main Problem Type is termite
        $extraPestIds = $isTermite ? [] : array_values(array_filter((array) $request->input('extra_svcp_id', [])));

        if ($isTermite && empty($selectedTermiteId)) {
            return redirect()->back()->withInput()->withErrors(['svcpat_id' => 'Please select a termite treatment size.']);
        }
        if (!$isTermite && empty($selectedAreaIds)) {
            return redirect()->back()->withInput()->withErrors(['svcpa_id' => 'Please select at least one location.']);
        }

        // svc_is_package: only true for non-termite orders with 2+ locations
        $isPackage = (!$isTermite && count($selectedAreaIds) > 1) ? 1 : 0;

        $initialPrice = 0;
        $lineItems = [];

        if ($isTermite) {
            $termiteCost = DB::table('service_package_area_termites')
                ->where('svcpat_id', $selectedTermiteId)
                ->value('svcpat_cost') ?? 0;

            $initialPrice = $termiteCost;
            $lineItems[] = ['svcpa_id' => null, 'svcpat_id' => $selectedTermiteId];
        } else {
            $areaRows = DB::table('service_package_areas')
                ->whereIn('svcpa_id', $selectedAreaIds)
                ->get()
                ->keyBy('svcpa_id');

            foreach ($selectedAreaIds as $aid) {
                $initialPrice += $areaRows[$aid]->svcpa_cost ?? 0;
                $lineItems[] = ['svcpa_id' => $aid, 'svcpat_id' => null];
            }
        }

        // Distance & location price — same Haversine/Mapbox logic as admin, computed once per appointment
        $address = DB::table('user_addresses')->where('uadd_id', $request->uadd_id)->first();
        $branch = DB::table('branches')->where('branch_id', $branchId)->first();
        $locationFee = DB::table('service_package_area_locations')->where('branch_id', $branchId)->first();

        $kmDistance = 0;
        $locationPrice = 0;

        if ($address && $branch && $locationFee) {
            $lat1 = deg2rad($branch->branch_latitude);
            $lon1 = deg2rad($branch->branch_longitude);
            $lat2 = deg2rad($address->uadd_latitude);
            $lon2 = deg2rad($address->uadd_longitude);

            $dlat = $lat2 - $lat1;
            $dlon = $lon2 - $lon1;

            $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
            $haversineKm = 6371 * 2 * asin(sqrt($a));

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

            $kmDistance = (fmod($rawKm, 1) >= 0.5) ? ceil($rawKm) : floor($rawKm);

            $locationPrice = $kmDistance <= 10
                ? $locationFee->svcpal_first_cost
                : $locationFee->svcpal_first_cost + (($kmDistance - 10) * $locationFee->svcpal_succeeding_cost);
        }

        $svcId = DB::table('services')->insertGetId([
            'svc_uuid' => generateuuid(),
            'branch_id' => $branchId,
            'usr_id' => $usrId,
            'svc_km_distance' => $kmDistance,
            'svc_is_package' => $isPackage,
            'svc_is_termite' => $isTermite,
            'svcpat_id' => $isTermite ? $selectedTermiteId : null,
            'svc_problem_description' => $request->svc_problem_description,
            'svc_status' => 'REQUESTED',
            'svc_initial_price' => $initialPrice,
            'svc_location_price' => $locationPrice,
            'svc_balance' => $initialPrice + $locationPrice,
            'svc_payment_status' => 'NO PAYMENT',
            'svc_date_created' => Carbon::now(),
            'svc_created_by' => $usrId,
            'svc_active' => 1,
        ]);

        DB::table('services')->where('svc_id', $svcId)->update(['svc_sa_number' => $svcId]);

        DB::table('service_order_pests')->insert([
            'svcop_uuid' => generateuuid(),
            'svc_id' => $svcId,
            'svcp_id' => $request->svcp_id,
            'svcop_date_created' => Carbon::now(),
            'svcop_created_by' => $usrId,
            'svcop_active' => 1,
        ]);

        // Extra Problem Types
        foreach ($extraPestIds as $extraPestId) {
            DB::table('service_order_pests')->insert([
                'svcop_uuid' => generateuuid(),
                'svc_id' => $svcId,
                'svcp_id' => $extraPestId,
                'svcop_date_created' => Carbon::now(),
                'svcop_created_by' => $usrId,
                'svcop_active' => 1,
            ]);
        }

        foreach ($lineItems as $item) {
            DB::table('service_orders')->insert([
                'svco_uuid' => generateuuid(),
                'svc_id' => $svcId,
                'svcpa_id' => $item['svcpa_id'],
                'svcpat_id' => $item['svcpat_id'],
                'svco_date_created' => Carbon::now(),
                'svco_created_by' => $usrId,
                'svco_active' => 1,
            ]);
        }

        DB::table('service_appointments')->insert([
            'svca_uuid' => generateuuid(),
            'svc_id' => $svcId,
            'uadd_id' => $request->uadd_id,
            'svca_client_date' => $request->svca_client_date,
            'svca_client_time' => $request->svca_client_time,
            'svca_status' => 'UNASSIGNED',
            'svca_date_created' => Carbon::now(),
            'svca_created_by' => $usrId,
            'svca_active' => 1,
        ]);

        $serviceOrder = 'SA-' . str_pad($svcId, 6, '0', STR_PAD_LEFT);
        logUserActivity('Book Appointment', 'Booked appointment ' . $serviceOrder);

        session()->flash('successMessage', 'Appointment successfully booked.');
        return redirect('client/service/order/appointments/requested/' . $svcId);
    }
    // END BOOK APPOINTMENT

    // START REQUESTED APPOINTMENTS
    public function requested_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionUsrId = session('usr_id');

        $query = DB::table('services')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->where('services.svc_active', 1)
            ->whereIn('services.svc_status', ['REQUESTED', 'CONFIRM ASSESSMENT'])
            ->where('services.usr_id', $sessionUsrId);

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_status',
            'services.svc_payment_status',
            'services.svc_date_created',
            'branches.branch_name'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('services.svc_status', 'LIKE', "%$search%")
                    ->orWhere('services.svc_date_created', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('services.svc_date_created', 'asc');

        $appointments = $query->paginate(500);

        return view('client.appointments.requested.requested', compact('appointments', 'search'));
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

        return view('client.appointments.requested.view_requested', compact(
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'appointmentImages',
            'contracts',
            'hasOwnContract'
        ));
    }
    // END REQUESTED APPOINTMENTS

    // START ASSESSED APPOINTMENTS
    public function assessed_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionUsrId = session('usr_id');

        $query = DB::table('services')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->where('services.svc_active', 1)
            ->where('services.svc_status', 'ASSESSED')
            ->where('services.usr_id', $sessionUsrId);

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_status',
            'services.svc_balance',
            'services.svc_payment_status',
            'services.svc_date_created',
            'branches.branch_name'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('services.svc_status', 'LIKE', "%$search%")
                    ->orWhere('services.svc_date_created', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('services.svc_date_created', 'asc');

        $appointments = $query->paginate(500);

        return view('client.appointments.assessed.assessed', compact('appointments', 'search'));
    }

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

        return view('client.appointments.assessed.view_assessed', compact('display', 'serviceAreas', 'termiteAreas', 'appointmentImages', 'mappingImages', 'contracts', 'hasOwnContract', 'serviceChemicals'));
    }
    // END ASSESSED APPOINTMENTS

    // START SCHEDULED APPOINTMENTS
    public function scheduled_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionUsrId = session('usr_id');

        $query = DB::table('services')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->where('services.svc_active', 1)
            ->whereIn('services.svc_status', ['SCHEDULED', 'ASSIGNED'])
            ->where('services.usr_id', $sessionUsrId);

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_status',
            'services.svc_balance',
            'services.svc_payment_status',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'branches.branch_name'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('services.svc_status', 'LIKE', "%$search%")
                    ->orWhere('service_appointments.svca_approved_date', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_appointments.svca_approved_date', 'asc');

        $appointments = $query->paginate(500);

        return view('client.appointments.scheduled.scheduled', compact('appointments', 'search'));
    }

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

        return view('client.appointments.scheduled.view_scheduled', compact(
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'appointmentImages',
            'mappingImages',
            'contracts',
            'hasOwnContract',
            'serviceChemicals'
        ));
    }
    // END SCHEDULED APPOINTMENTS

    // START ONGOING APPOINTMENTS
    public function ongoing_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionUsrId = session('usr_id');

        $query = DB::table('services')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->leftJoin('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->where('services.svc_active', 1)
            ->whereIn('services.svc_status', ['ONGOING', 'DONE SERVICE'])
            ->where('services.usr_id', $sessionUsrId);

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_status',
            'services.svc_balance',
            'services.svc_payment_status',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'branches.branch_name'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('services.svc_status', 'LIKE', "%$search%")
                    ->orWhere('service_appointments.svca_approved_date', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_appointments.svca_approved_date', 'asc');

        $appointments = $query->paginate(500);

        return view('client.appointments.ongoing.ongoing', compact('appointments', 'search'));
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
                'service_appointment_schedules.svcas_id',
                'service_appointment_schedules.svcas_start_date',
                'service_appointment_schedules.svcas_start_time',
                'service_appointment_schedules.svcas_end_date',
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

        return view('client.appointments.ongoing.view_ongoing', compact(
            'display',
            'pestTypes',
            'serviceAreas',
            'termiteAreas',
            'appointmentImages',
            'mappingImages',
            'contracts',
            'hasOwnContract',
            'serviceChemicals'
        ));
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
    // END ONGOING APPOINTMENTS

    // START FINISHED APPOINTMENTS
    public function finished_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionUsrId = session('usr_id');

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
            ->where('services.svc_status', 'DONE REPORT')
            ->where('services.usr_id', $sessionUsrId);

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_balance',
            'services.svc_payment_status',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'service_appointment_schedules.svcas_start_date',
            'service_appointment_schedules.svcas_start_time',
            'service_appointment_schedules.svcas_end_date',
            'service_appointment_schedules.svcas_end_time',
            'service_appointment_schedules.svcas_time_travel',
            'service_appointment_schedules.svcas_time_finished',
            'branches.branch_name'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('services.svc_status', 'LIKE', "%$search%")
                    ->orWhere('service_appointments.svca_approved_date', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_appointments.svca_approved_date', 'asc');

        $appointments = $query->paginate(500);

        return view('client.appointments.finished.finished', compact('appointments', 'search'));
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

        return view('client.appointments.finished.view_finished', compact(
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
    // END FINISHED APPOINTMENTS

    // START RATING APPOINTMENT
    public function rate_service_appointments(Request $request, $svc_id)
    {
        $request->validate([
            'svc_rating' => 'required|integer|min:1|max:5',
        ]);

        $usrId = session('usr_id');

        $service = DB::table('services')->where('svc_id', $svc_id)->first();

        if (!$service) {
            session()->flash('errorMessage', 'Service not found.');
            return redirect()->back();
        }

        DB::table('services')->where('svc_id', $svc_id)->update([
            'svc_rating' => $request->svc_rating,
        ]);

        logUserActivity('Rate Service', 'Rated service ' . ($service->svc_sa_number ?? $svc_id) . ' with ' . $request->svc_rating . ' star(s)');

        session()->flash('successMessage', 'Thank you for rating this service.');
        return redirect()->back();
    }
    // END RATING APPOINTMENT

    // START COMPLETED APPOINTMENTS
    public function completed_appointments(Request $request)
    {
        $search = $request->search ?? '';
        $sessionUsrId = session('usr_id');

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
            ->where('services.svc_status', 'COMPLETED')
            ->where('services.usr_id', $sessionUsrId);

        $query->select(
            'services.svc_id',
            'services.svc_sa_number',
            'services.svc_is_termite',
            'services.svc_is_package',
            'services.svc_date_completed',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'service_appointments.svca_approved_date',
            'service_appointments.svca_approved_time_from',
            'service_appointments.svca_approved_time_to',
            'service_appointment_schedules.svcas_start_date',
            'service_appointment_schedules.svcas_start_time',
            'service_appointment_schedules.svcas_end_date',
            'service_appointment_schedules.svcas_end_time',
            'service_appointment_schedules.svcas_time_travel',
            'service_appointment_schedules.svcas_time_finished',
            'branches.branch_name'
        );

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('services.svc_sa_number', 'LIKE', "%$search%")
                    ->orWhere('services.svc_status', 'LIKE', "%$search%")
                    ->orWhere('service_appointments.svca_approved_date', 'LIKE', "%$search%");
            });
        }

        $query->orderBy('service_appointments.svca_approved_date', 'asc');

        $appointments = $query->paginate(500);

        return view('client.appointments.completed.completed', compact('appointments', 'search'));
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

        return view('client.appointments.completed.view_completed', compact(
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

    // START PAYMENTS
    public function service_order_payment_view($svc_id)
    {
        $sessionBranchId = session('branch_id');

        // 1. Service / Service Order details
        $display = DB::table('services')
            ->leftJoin('users', 'services.usr_id', '=', 'users.usr_id')
            ->leftJoin('branches', 'services.branch_id', '=', 'branches.branch_id')
            ->where('services.svc_id', $svc_id)
            ->where('services.usr_id', session('usr_id'))
            ->where('services.svc_active', 1);

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

        if (!$display) {
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

        if (!$display->svc_is_termite) {
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
        $paymentTypes = DB::table('payment_types')
            ->where('typ_id', '!=', 1)
            ->get();

        return view('client.appointments.accounting.payments', compact(
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
            ->where('usr_id', session('usr_id'))
            ->where('svc_active', 1)
            ->first();

        if (!$service) {
            session()->flash('errorMessage', 'Service order not found.');
            return redirect()->back();
        }

        $request->validate([
            'typ_id' => 'required',
            'spay_date' => 'required|date',
            'spay_amount' => 'required|numeric|min:0.1|max:' . $service->svc_balance,
            'spay_reference_number' => 'required',
            'spay_details' => 'nullable',
            'spay_image_proof' => 'required|mimes:jpeg,jpg,png,webp|max:8192',
        ]);

        // Re-fetch to avoid acting on stale data
        $service = DB::table('services')
            ->where('svc_id', $svc_id)
            ->where('usr_id', session('usr_id'))
            ->where('svc_active', 1)
            ->first();

        if (!$service) {
            session()->flash('errorMessage', 'Service order not found.');
            return redirect()->back();
        }

        // PROOF OF PAYMENT UPLOAD
        $fileName = null;

        if ($request->hasFile('spay_image_proof')) {
            $file = $request->file('spay_image_proof');
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $path = public_path('images/proof_payment_images/' . $fileName);
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
        }

        DB::table('service_payments')->insert([
            'spay_uuid' => generateuuid(),
            'svc_id' => $svc_id,
            'typ_id' => $request->typ_id,
            'spay_reference_number' => $request->spay_reference_number,
            'spay_date' => $request->spay_date,
            'spay_amount' => $request->spay_amount,
            'spay_details' => $request->spay_details,
            'spay_status' => 'FOR APPROVAL',
            'spay_image_proof' => $fileName,
            'spay_date_created' => Carbon::now(),
            'spay_created_by' => session('usr_id'),
            'spay_active' => 1,
        ]);

        $serviceOrder = 'SA-' . str_pad($svc_id, 6, '0', STR_PAD_LEFT);

        logUserActivity(
            'Manage Payments',
            'Requested Payment ' . $serviceOrder
        );

        session()->flash('successMessage', 'Payment requested successfully. Awaiting approval.');

        return redirect()->back();
    }
    // END PAYMENTS

    // START CLIENT REPORT
    public function client_report()
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

        $totalServiceOrders = 0;
        $totalActiveOrders = 0;
        $totalDeletedOrders = 0;
        $totalRats = 0;
        $totalSnakes = 0;
        $totalLizard = 0;
        $totalKittens = 0;
        $totalOthers = 0;
        $totalPestsCaptured = 0;

        return view('client.reports.client', compact(
            'services',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalServiceOrders',
            'totalActiveOrders',
            'totalDeletedOrders',
            'totalRats',
            'totalSnakes',
            'totalLizard',
            'totalKittens',
            'totalOthers',
            'totalPestsCaptured',
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
        $filtered = true;

        $sessionUsrId = session('usr_id');

        $query = DB::table('services')
            ->join('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->where('services.usr_id', $sessionUsrId);

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
                'services.svc_active',
                'services.svc_rat_captured',
                'services.svc_snake_captured',
                'services.svc_lizard_captured',
                'services.svc_kitten_captured',
                'services.svc_other_captured',
                'service_appointments.svca_approved_date'
            )
            ->get();

        $totalServiceOrders = $services->count();
        $totalActiveOrders = $services->where('svc_active', 1)->count();
        $totalDeletedOrders = $services->where('svc_active', 0)->count();

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

        return view('client.reports.client', compact(
            'services',
            'filter_type',
            'date',
            'date_from',
            'date_to',
            'week',
            'month',
            'year',
            'totalServiceOrders',
            'totalActiveOrders',
            'totalDeletedOrders',
            'totalRats',
            'totalSnakes',
            'totalLizard',
            'totalKittens',
            'totalOthers',
            'totalPestsCaptured',
            'filtered'
        ));
    }
    // END CLIENT REPORT

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

        $sessionUsrId = session('usr_id');

        $paymentStatuses = DB::table('services')
            ->where('usr_id', $sessionUsrId)
            ->whereNotNull('svc_payment_status')
            ->distinct()
            ->orderBy('svc_payment_status')
            ->pluck('svc_payment_status');

        return view('client.reports.balance', compact(
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
            'paymentStatuses',
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
        $svc_payment_status = $request->input('svc_payment_status');
        $filtered = true;

        $sessionUsrId = session('usr_id');

        $query = DB::table('services')
            ->join('service_appointments', 'services.svc_id', '=', 'service_appointments.svc_id')
            ->where('services.usr_id', $sessionUsrId)
            ->where('services.svc_active', 1);

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
                'service_appointments.svca_approved_date'
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

        $paymentStatuses = DB::table('services')
            ->where('usr_id', $sessionUsrId)
            ->whereNotNull('svc_payment_status')
            ->distinct()
            ->orderBy('svc_payment_status')
            ->pluck('svc_payment_status');

        return view('client.reports.balance', compact(
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
            'paymentStatuses',
            'filtered'
        ));
    }
    // END BALANCE REPORT
}