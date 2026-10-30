<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfilingController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ClientController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ------------------------------------------------------------------------------------------------------------------------------------ //
// LOGIN
Route::get('/', [MainController::class, 'main']);
Route::post('/forgot/password', [LoginController::class, 'forgot_password']);
Route::post('/validate', [LoginController::class, 'validate_user']);
Route::get('logout', [LoginController::class, 'logout']);
// ------------------------------------------------------------------------------------------------------------------------------------ //

// -----------------------------------------------------ADMIN SIDE--------------------------------------------------------------------- //
// ------------------------------------------------------------------------------------------------------------------------------------ //
// HOME
Route::get('home', [AdminController::class, 'home']);
Route::get('home/dashboard-data', [AdminController::class, 'dashboard_data']);
Route::get('setup', [AdminController::class, 'setup']);
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// ANNOUNCEMENTS
Route::post('announcement/save', [AnnouncementController::class, 'save']);
Route::post('announcement/delete/{ann_uuid}', [AnnouncementController::class, 'delete']);
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// ACCOUNT
Route::get('account', [UserController::class, 'account']);
Route::post('account/update', [UserController::class, 'account_update']);
Route::post('account/address/add', [UserController::class, 'account_address_add']);
Route::post('account/address/edit', [UserController::class, 'account_address_edit']);
Route::post('account/update/password', [UserController::class, 'account_update_password']);
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// MESSAGES
Route::get('messages', [MessageController::class, 'main']);
Route::get('messages/chat/{mesg_group_id}', [MessageController::class, 'personal']);
Route::post('messages/chat/{mesg_group_id}/rename', [MessageController::class, 'renameGroup']);
Route::post('messages/chat/{mesg_group_id}/avatar', [MessageController::class, 'updateGroupAvatar']);
Route::post('messages/chat/{mesg_group_id}/avatar/remove', [MessageController::class, 'removeGroupAvatar']);
Route::post('messages/send', [MessageController::class, 'send']);
Route::post('messages/compose', [MessageController::class, 'compose']);
Route::get('messages/{mes_id}/image', [MessageController::class, 'image'])->name('messages.image');
Route::patch('messages/{mes_id}', [MessageController::class, 'update']);
Route::post('messages/{mes_id}/unsend', [MessageController::class, 'unsend']);
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// PROFILING
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,PROFILING OFFICER')->group(function () {
    // - Users
    Route::get('profiling/users/active', [ProfilingController::class, 'users_active']);
    Route::get('profiling/users/deleted', [ProfilingController::class, 'users_deleted']);
    Route::post('profiling/users/add', [ProfilingController::class, 'users_add']);
    Route::post('profiling/users/update/user/{usr_id}', [ProfilingController::class, 'users_update_user']);
    Route::post('profiling/users/reset/password/{usr_id}', [ProfilingController::class, 'users_reset_password']);
    Route::post('profiling/users/delete/{usr_id}', [ProfilingController::class, 'users_delete']);
    Route::post('profiling/users/restore/{usr_id}', [ProfilingController::class, 'users_restore']);
    // - Technicians
    Route::get('profiling/technicians/active', [ProfilingController::class, 'technicians_active']);
    Route::get('profiling/technicians/deleted', [ProfilingController::class, 'technicians_deleted']);
    Route::post('profiling/technicians/add', [ProfilingController::class, 'technicians_add']);
    Route::post('profiling/technicians/update/technician/{usr_id}', [ProfilingController::class, 'technicians_update_technician']);
    Route::post('profiling/technicians/reset/password/{usr_id}', [ProfilingController::class, 'technicians_reset_password']);
    Route::post('profiling/technicians/delete/{usr_id}', [ProfilingController::class, 'technicians_delete']);
    Route::post('profiling/technicians/restore/{usr_id}', [ProfilingController::class, 'technicians_restore']);
    // - Clients
    Route::get('profiling/clients/active', [ProfilingController::class, 'clients_active']);
    Route::get('profiling/clients/deleted', [ProfilingController::class, 'clients_deleted']);
    Route::get('profiling/clients/contracts/{usr_id}', [ProfilingController::class, 'clients_contracts']);
    Route::post('profiling/clients/add', [ProfilingController::class, 'clients_add']);
    Route::post('profiling/clients/import', [ProfilingController::class, 'clients_import']);
    Route::post('profiling/clients/update/branch/{usr_id}', [ProfilingController::class, 'clients_update_branch']);
    Route::post('profiling/clients/address/add/{usr_id}', [ProfilingController::class, 'clients_address_add']);
    Route::post('profiling/clients/address/edit/{usr_id}', [ProfilingController::class, 'clients_address_edit']);
    Route::post('profiling/clients/reset/password/{usr_id}', [ProfilingController::class, 'clients_reset_password']);
    Route::post('profiling/clients/delete/{usr_id}', [ProfilingController::class, 'clients_delete']);
    Route::post('profiling/clients/restore/{usr_id}', [ProfilingController::class, 'clients_restore']);
});
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// SERVICE ORDER
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,BOOKING OFFICER,SALES OFFICER')->group(function () {
    // - Book Appointment
    Route::get('service/order/appointments/clients', [AppointmentController::class, 'clients']);
    Route::post('service/order/appointments/clients/book', [AppointmentController::class, 'clients_book']);
});
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,ACCOUNTING OFFICER,BILLING OFFICER,APPOINTMENT CLERK,ASSESSMENT OFFICER,SCHEDULING OFFICER,SALES OFFICER,QUALITY OFFICER')->group(function () {
    // - Requested Appointments
    Route::get('service/order/appointments/requested', [AppointmentController::class, 'requested_appointments']);
    Route::get('service/order/appointments/requested/{svc_id}', [AppointmentController::class, 'requested_appointments_view']);
    Route::post('service/order/appointments/requested/add/pest_type', [AppointmentController::class, 'requested_appointments_view_add_pest']);
    Route::post('service/order/appointments/requested/delete/pest_type/{svcop_id}', [AppointmentController::class, 'requested_appointments_view_delete_pest']);
    Route::post('service/order/appointments/requested/add/service_order', [AppointmentController::class, 'requested_appointments_view_add_service']);
    Route::post('service/order/appointments/requested/delete/service_order/{svcpa_id}', [AppointmentController::class, 'requested_appointments_view_delete_service']);
    Route::post('service/order/appointments/requested/assess', [AppointmentController::class, 'requested_appointments_view_assess']);
    Route::post('service/order/appointments/requested/assess/confirmation', [AppointmentController::class, 'requested_appointments_view_assess_confirmation']);
    // - Scheduling
    Route::get('service/order/appointments/scheduling', [AppointmentController::class, 'scheduling_appointments']);
    // - Assesed Appointments
    Route::get('service/order/appointments/assessed/{svc_id}', [AppointmentController::class, 'assessed_appointments_view']);
    Route::post('service/order/appointments/assessed/mapping/images/add', [AppointmentController::class, 'mapping_images_store']);
    Route::post('service/order/appointments/assessed/mapping/images/delete', [AppointmentController::class, 'mapping_images_delete']);
    Route::post('service/order/appointments/assessed/update-schedule', [AppointmentController::class, 'assessed_appointments_update_schedule']);
    Route::post('service/order/appointments/assessed/assign', [AppointmentController::class, 'assessed_appointments_view_assign']);
    // - Scheduled Appointments
    Route::get('service/order/appointments/scheduled/{svc_id}', [AppointmentController::class, 'scheduled_appointments_view']);
    Route::post('service/order/appointments/scheduled/update-scheduled', [AppointmentController::class, 'scheduled_appointments_update_scheduled']);
    Route::post('service/order/appointments/scheduled/update/technician/{svc_id}', [AppointmentController::class, 'scheduled_appointments_view_update_technician']);
    // - Ongoing Appointments
    Route::get('service/order/appointments/ongoing', [AppointmentController::class, 'ongoing_appointments']);
    Route::get('service/order/appointments/ongoing/tracking', [AppointmentController::class, 'ongoing_appointments_tracking']);
    Route::get('service/order/appointments/ongoing/{svc_id}', [AppointmentController::class, 'ongoing_appointments_view']);
    Route::post('service/order/appointments/ongoing/mark/arrived/{svc_id}', [AppointmentController::class, 'ongoing_appointments_view_mark_arrived']);
    Route::post('service/order/appointments/ongoing/mark/done/service/{svc_id}', [AppointmentController::class, 'ongoing_appointments_view_mark_done_service']);
    Route::get('service/order/appointments/ongoing/track/{svc_id}', [AppointmentController::class, 'ongoing_appointments_view_track']);
    // - Finished Appointments
    Route::get('service/order/appointments/finished', [AppointmentController::class, 'finished_appointments']);
    Route::get('service/order/appointments/finished/{svc_id}', [AppointmentController::class, 'finished_appointments_view']);
    Route::post('service/order/appointments/finished/initiate/contract/{svc_id}', [AppointmentController::class, 'finished_appointments_view_initiate_contract']);
    Route::post('service/order/appointments/finished/complete/{svc_id}', [AppointmentController::class, 'finished_appointments_view_complete']);
    // - Completed Appointments
    Route::get('service/order/appointments/completed', [AppointmentController::class, 'completed_appointments']);
    Route::get('service/order/appointments/completed/{svc_id}', [AppointmentController::class, 'completed_appointments_view']);
    // - Deleted Appointments
    Route::post('service/order/appointments/delete/{svc_id}', [AppointmentController::class, 'delete_appointment']);
    Route::post('service/order/appointments/batch/delete', [AppointmentController::class, 'batch_delete_appointment']);
    Route::get('service/order/appointments/deleted', [AppointmentController::class, 'deleted_appointments']);
    Route::get('service/order/appointments/deleted/{svc_id}', [AppointmentController::class, 'deleted_appointments_view']);
    // - Change Final Price
    Route::post('service/order/appointments/assessed/update/price', [AppointmentController::class, 'update_price_appointment']);
});
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// ACCOUNTING
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,ACCOUNTING OFFICER,BILLING OFFICER')->group(function () {
    // - Service Order Payments
    Route::get('service/order/payment/view/{svc_id}', [AccountingController::class, 'service_order_payment_view']);
    Route::post('service/order/payment/view/add/payment/{svc_id}', [AccountingController::class, 'service_order_payment_view_add_payment']);
    Route::post('service/order/payment/view/approve/{spay_id}', [AccountingController::class, 'service_order_payment_view_approve']);
    Route::post('service/order/payment/view/void/{spay_id}', [AccountingController::class, 'service_order_payment_view_void']);
    // - Client Payments
    Route::get('accounting/payments', [AccountingController::class, 'accounting_payments']);
    Route::get('accounting/payments/{spay_id}/details', [AccountingController::class, 'accounting_payment_details']);
    Route::post('accounting/payments/{svc_id}/details/approve', [AccountingController::class, 'accounting_payment_approve']);
    Route::get('accounting/history/payments', [AccountingController::class, 'accounting_history']);
    Route::get('accounting/history/{spay_id}/details', [AccountingController::class, 'accounting_history_details']);
    Route::post('accounting/history/{svc_id}/details/void', [AccountingController::class, 'accounting_payment_void']);
    Route::get('accounting/history/voided/payments', [AccountingController::class, 'accounting_voided']);
Route::get('accounting/history/voided/{spay_id}/details', [AccountingController::class, 'accounting_voided_details']);
});
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// REPORTS
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,ACCOUNTING OFFICER')->group(function () {
    // - Balance Report
    Route::get('reports/balance', [ReportController::class, 'balance_report']);
    Route::post('reports/balance/filter', [ReportController::class, 'filter_balance_report']);
    // - Income Report
    Route::get('reports/income', [ReportController::class, 'income_report']);
    Route::post('reports/income/filter', [ReportController::class, 'filter_income_report']);
    // - Void Report
    Route::get('reports/void', [ReportController::class, 'void_report']);
    Route::post('reports/void/filter', [ReportController::class, 'filter_void_report']);
    // - Client Report
    Route::get('reports/client', [ReportController::class, 'client_report']);
    Route::post('reports/client/filter', [ReportController::class, 'filter_client_report']);
});
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,QUALITY OFFICER')->group(function () {
    // - Pest Captured Report
    Route::get('reports/pest/captured', [ReportController::class, 'pest_captured_report']);
    Route::post('reports/pest/captured/filter', [ReportController::class, 'filter_pest_captured_report']);
    // - Service Package Report
    Route::get('reports/service/package', [ReportController::class, 'service_package_report']);
    Route::post('reports/service/package/filter', [ReportController::class, 'filter_service_package_report']);
    // - Chemical Report
    Route::get('reports/chemical', [ReportController::class, 'chemical_report']);
    Route::post('reports/chemical/filter', [ReportController::class, 'filter_chemical_report']);
    // - Technician Report
    Route::get('reports/technician', [ReportController::class, 'technician_report']);
    Route::post('reports/technician/filter', [ReportController::class, 'filter_technician_report']);
    // - Turn Around Time Report
    Route::get('reports/turn-around-time', [ReportController::class, 'turn_around_time_report']);
    Route::post('reports/turn-around-time/filter', [ReportController::class, 'filter_turn_around_time_report']);
});
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// MANAGEMENT
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,DATA OFFICER')->group(function () {
    // - Addresses
    Route::get('management/addresses/active', [ManagementController::class, 'addresses_active']);
    Route::get('management/addresses/deleted', [ManagementController::class, 'addresses_deleted']);
    Route::post('management/addresses/add', [ManagementController::class, 'addresses_add']);
    Route::post('management/addresses/update/{add_id}', [ManagementController::class, 'addresses_update']);
    Route::post('management/addresses/delete/{add_id}', [ManagementController::class, 'addresses_delete']);
    Route::post('management/addresses/restore/{add_id}', [ManagementController::class, 'addresses_restore']);
    // - Chemicals
    Route::get('management/chemicals/active', [ManagementController::class, 'chemicals_active']);
    Route::get('management/chemicals/deleted', [ManagementController::class, 'chemicals_deleted']);
    Route::post('management/chemicals/add', [ManagementController::class, 'chemicals_add']);
    Route::post('management/chemicals/update/{chem_id}', [ManagementController::class, 'chemicals_update']);
    Route::post('management/chemicals/delete/{chem_id}', [ManagementController::class, 'chemicals_delete']);
    Route::post('management/chemicals/restore/{chem_id}', [ManagementController::class, 'chemicals_restore']);
    // - Branches
    Route::get('management/branches/active', [ManagementController::class, 'branches_active']);
    Route::get('management/branches/deleted', [ManagementController::class, 'branches_deleted']);
    Route::post('management/branches/add', [ManagementController::class, 'branches_add']);
    Route::post('management/branches/update/{branch_id}', [ManagementController::class, 'branches_update']);
    Route::post('management/branches/delete/{branch_id}', [ManagementController::class, 'branches_delete']);
    Route::post('management/branches/restore/{branch_id}', [ManagementController::class, 'branches_restore']);
});
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,ACCOUNTING OFFICER')->group(function () {
    // - Services
    Route::get('management/services/active', [ManagementController::class, 'services_active']);
    Route::post('management/services/area/cost/update/{svcpa_id}', [ManagementController::class, 'services_area_cost_update']);
    Route::post('management/services/area/delete/{svcpa_id}', [ManagementController::class, 'services_area_delete']);
    Route::post('management/services/area/restore/{svcpa_id}', [ManagementController::class, 'services_area_restore']);
    Route::post('management/services/area/termites/cost/update/{svcpa_id}', [ManagementController::class, 'services_area_termites_cost_update']);
    Route::post('management/services/area/device/cost/update/{svcpad_id}', [ManagementController::class, 'services_area_device_cost_update']);
    Route::post('management/services/area/location/cost/update/{svcpal_id}', [ManagementController::class, 'services_area_location_cost_update']);
});
Route::middleware('role:SUPERADMIN,ADMIN')->group(function () {
    // - Logins
    Route::get('histories/logins', [ManagementController::class, 'login_histories']);
    // - Prices
    Route::get('histories/prices', [ManagementController::class, 'price_histories']);
    // - Users
    Route::get('histories/users', [ManagementController::class, 'user_histories']);
});
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// LOCATION
Route::get('/locations/{region}/provinces', [LocationController::class, 'provinces']);
Route::get('/locations/{province}/municipalities', [LocationController::class, 'municipalities']);
Route::get('/locations/{muni}/barangays', [LocationController::class, 'barangays']);
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// LARAVEL COMMANDS //
Route::get('/laravel/clear-all', function () {
    $commands = [
        'cache:clear',
        'view:clear',
        'route:clear',
        'config:clear',
        'config:cache',
    ];

    foreach ($commands as $command) {
        Artisan::call($command);
    }

    return response()->json(['message' => 'All caches and configurations cleared successfully!']);
});
// ------------------------------------------------------------------------------------------------------------------------------------ //
// -----------------------------------------------------ADMIN SIDE--------------------------------------------------------------------- //

// ----------------------------------------------------CLIENT SIDE--------------------------------------------------------------------- //
// REGISTRATION
Route::get('client/registration', [ClientController::class, 'registration']);
Route::post('client/registration', [ClientController::class, 'registration_store']);
Route::middleware('client.auth')->group(function () {
    // HOME
    Route::get('client/home', [ClientController::class, 'home']);
    // ACCOUNT
    Route::get('client/account', [ClientController::class, 'account']);
    Route::post('client/account/update', [ClientController::class, 'account_update']);
    Route::post('client/account/address/add', [ClientController::class, 'account_address_add']);
    Route::post('client/account/address/edit', [ClientController::class, 'account_address_edit']);
    Route::post('client/account/update/password', [ClientController::class, 'account_update_password']);
    // BOOK APPOINTMENT
    Route::get('client/service/order/book', [ClientController::class, 'appointment_book']);
    Route::post('client/service/order/book/save', [ClientController::class, 'appointment_book_save']);
    // REQUESTED APPOINTMENT
    Route::get('client/service/order/appointments/requested', [ClientController::class, 'requested_appointments']);
    Route::get('client/service/order/appointments/requested/{svc_id}', [ClientController::class, 'requested_appointments_view']);
    // ASSESSED APPOINTMENT
    Route::get('client/service/order/appointments/assessed', [ClientController::class, 'assessed_appointments']);
    Route::get('client/service/order/appointments/assessed/{svc_id}', [ClientController::class, 'assessed_appointments_view']);
    // SCHEDULED APPOINTMENT
    Route::get('client/service/order/appointments/scheduled', [ClientController::class, 'scheduled_appointments']);
    Route::get('client/service/order/appointments/scheduled/{svc_id}', [ClientController::class, 'scheduled_appointments_view']);
    // ONGOING APPOINTMENT
    Route::get('client/service/order/appointments/ongoing', [ClientController::class, 'ongoing_appointments']);
    Route::get('client/service/order/appointments/ongoing/{svc_id}', [ClientController::class, 'ongoing_appointments_view']);
    Route::get('client/service/order/appointments/ongoing/track/{svc_id}', [AppointmentController::class, 'ongoing_appointments_view_track']);
    // FINISHED APPOINTMENT
    Route::get('client/service/order/appointments/finished', [ClientController::class, 'finished_appointments']);
    Route::get('client/service/order/appointments/finished/{svc_id}', [ClientController::class, 'finished_appointments_view']);
    // RATE SERVICE
    Route::post('client/service/order/appointments/rate-service/{svc_id}', [ClientController::class, 'rate_service_appointments']);
    // COMPLETED APPOINTMENT
    Route::get('client/service/order/appointments/completed', [ClientController::class, 'completed_appointments']);
    Route::get('client/service/order/appointments/completed/{svc_id}', [ClientController::class, 'completed_appointments_view']);
    // SERVICE ORDER PAYMENTS
    Route::get('client/service/order/payment/view/{svc_id}', [ClientController::class, 'service_order_payment_view']);
    Route::post('client/service/order/payment/view/add/payment/{svc_id}', [ClientController::class, 'service_order_payment_view_add_payment']);
    // BALANCE REPORT
    Route::get('client/reports/balance', [ClientController::class, 'balance_report']);
    Route::post('client/reports/balance/filter', [ClientController::class, 'filter_balance_report']);
    // CLIENT REPORT
    Route::get('client/reports/client', [ClientController::class, 'client_report']);
    Route::post('client/reports/client/filter', [ClientController::class, 'filter_client_report']);
    // BALANCE REPORT
    Route::get('client/reports/balance', [ClientController::class, 'balance_report']);
    Route::post('client/reports/balance/filter', [ClientController::class, 'filter_balance_report']);
});
// ----------------------------------------------------CLIENT SIDE--------------------------------------------------------------------- //