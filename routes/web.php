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
Route::post('/validate', [LoginController::class, 'validate _user']);
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
Route::middleware('role:SUPERADMIN,ADMIN')->group(function () {
    // - Logins
    Route::get('histories/logins', [ManagementController::class, 'login_histories']);
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