<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\ProfilingController;
use App\Http\Controllers\LocationController;

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
Route::get('admin/home', [AdminController::class, 'home']);
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// ANNOUNCEMENTS
Route::post('admin/announcement/save', [AnnouncementController::class, 'save']);
Route::post('admin/announcement/delete/{ann_uuid}', [AnnouncementController::class, 'delete']);
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// ACCOUNT
Route::get('admin/account', [UserController::class, 'account']);
Route::post('admin/account/update', [UserController::class, 'account_update']);
Route::post('admin/account/address/add', [UserController::class, 'account_address_add']);
Route::post('admin/account/address/edit', [UserController::class, 'account_address_edit']);
Route::post('admin/account/update/password', [UserController::class, 'account_update_password']);
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
// HOMEPAGE
Route::middleware('role:SUPERADMIN,ADMIN')->group(function () {
    // - About
    Route::get('admin/homepage/about', [HomepageController::class, 'about']);
    Route::post('admin/homepage/about/update/{about_id}', [HomepageController::class, 'about_update']);
    // - Goals
    Route::get('admin/homepage/goals', [HomepageController::class, 'goals']);
    Route::post('admin/homepage/goals/update/{mis_id}', [HomepageController::class, 'goals_update']);
    // - Mission
    Route::get('admin/homepage/mission', [HomepageController::class, 'mission']);
    Route::post('admin/homepage/mission/update/{mis_id}', [HomepageController::class, 'mission_update']);
    // - Objectives
    Route::get('admin/homepage/objectives', [HomepageController::class, 'objectives']);
    Route::post('admin/homepage/objectives/update/{obj_id}', [HomepageController::class, 'objectives_update']);
    // - Partners
    Route::get('admin/homepage/partners', [HomepageController::class, 'partners']);
    Route::post('admin/homepage/partners/add', [HomepageController::class, 'partners_add']);
    Route::post('admin/homepage/partners/update/{obj_id}', [HomepageController::class, 'partners_update']);
    Route::post('admin/homepage/partners/delete/{obj_id}', [HomepageController::class, 'partners_delete']);
    // - Vision
    Route::get('admin/homepage/vision', [HomepageController::class, 'vision']);
    Route::post('admin/homepage/vision/update/{vis_id}', [HomepageController::class, 'vision_update']);
    // - Questions
    Route::get('admin/homepage/questions/for-approval', [HomepageController::class, 'questions_for_approval']);
    Route::post('admin/homepage/questions/approve/{que_id}', [HomepageController::class, 'questions_approve']);
    Route::get('admin/homepage/questions/approved', [HomepageController::class, 'questions_approved']);
    Route::post('admin/homepage/questions/archive/{que_id}', [HomepageController::class, 'questions_archive']);
    Route::get('admin/homepage/questions/archived', [HomepageController::class, 'questions_archived']);
    Route::post('admin/homepage/questions/activate/{que_id}', [HomepageController::class, 'questions_activate']);
    Route::get('admin/homepage/questions/rejected', [HomepageController::class, 'questions_rejected']);
    Route::post('admin/homepage/questions/reject/{que_id}', [HomepageController::class, 'questions_reject']);
});
//------------------------------------------------------------------------------------------------------------------------------------

// ------------------------------------------------------------------------------------------------------------------------------------ //
// MANAGEMENT
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,DATA OFFICER')->group(function () {
    // - Addresses
    Route::get('admin/management/addresses/active', [ManagementController::class, 'addresses_active']);
    Route::get('admin/management/addresses/deleted', [ManagementController::class, 'addresses_deleted']);
    Route::post('admin/management/addresses/add', [ManagementController::class, 'addresses_add']);
    Route::post('admin/management/addresses/update/{add_id}', [ManagementController::class, 'addresses_update']);
    Route::post('admin/management/addresses/delete/{add_id}', [ManagementController::class, 'addresses_delete']);
    Route::post('admin/management/addresses/restore/{add_id}', [ManagementController::class, 'addresses_restore']);
    // - Positions
    Route::get('admin/management/positions/active', [ManagementController::class, 'positions_active']);
    Route::get('admin/management/positions/deleted', [ManagementController::class, 'positions_deleted']);
    Route::post('admin/management/positions/add', [ManagementController::class, 'positions_add']);
    Route::post('admin/management/positions/update/{pos_id}', [ManagementController::class, 'positions_update']);
    Route::post('admin/management/positions/delete/{pos_id}', [ManagementController::class, 'positions_delete']);
    Route::post('admin/management/positions/restore/{pos_id}', [ManagementController::class, 'positions_restore']);
});
Route::middleware('role:SUPERADMIN,ADMIN')->group(function () {
    // - Logins
    Route::get('admin/histories/logins', [ManagementController::class, 'login_histories']);
    // - Users
    Route::get('admin/histories/users', [ManagementController::class, 'user_histories']);
});
// ------------------------------------------------------------------------------------------------------------------------------------ //

// ------------------------------------------------------------------------------------------------------------------------------------ //
// PROFILING
Route::middleware('role:SUPERADMIN,ADMIN,MANAGER,PROFILING OFFICER')->group(function () {
    // - Users
    Route::get('admin/profiling/users/active', [ProfilingController::class, 'users_active']);
    Route::get('admin/profiling/users/deleted', [ProfilingController::class, 'users_deleted']);
    Route::post('admin/profiling/users/add', [ProfilingController::class, 'users_add']);
    Route::post('admin/profiling/users/update/user/{usr_id}', [ProfilingController::class, 'users_update_user']);
    Route::post('admin/profiling/users/reset/password/{usr_id}', [ProfilingController::class, 'users_reset_password']);
    Route::post('admin/profiling/users/delete/{usr_id}', [ProfilingController::class, 'users_delete']);
    Route::post('admin/profiling/users/restore/{usr_id}', [ProfilingController::class, 'users_restore']);
    // - Board of Members
    Route::get('admin/profiling/board-of-members/active', [ProfilingController::class, 'boards_active']);
    Route::get('admin/profiling/board-of-members/deleted', [ProfilingController::class, 'boards_deleted']);
    Route::post('admin/profiling/board-of-members/add', [ProfilingController::class, 'boards_add']);
    Route::post('admin/profiling/board-of-members/update/user/{usr_id}', [ProfilingController::class, 'boards_update_user']);
    Route::post('admin/profiling/board-of-members/reset/password/{usr_id}', [ProfilingController::class, 'boards_reset_password']);
    Route::post('admin/profiling/board-of-members/delete/{usr_id}', [ProfilingController::class, 'boards_delete']);
    Route::post('admin/profiling/board-of-members/restore/{usr_id}', [ProfilingController::class, 'boards_restore']);
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