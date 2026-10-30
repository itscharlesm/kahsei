<?php

namespace App\Http\Controllers\api\v1\mobile_controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileNotificationController extends Controller
{
    // ============================================================
    // GET USER NOTIFICATIONS
    // ============================================================

    public function notifications(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
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

            $notifications = DB::table('message_notifications')
                    ->where('usr_id', $user->usr_id)
                    ->where('mesn_active', 1)
                    ->orderByRaw("
                        CASE
                            WHEN mesn_view IS NULL
                                OR mesn_view = ''
                                OR mesn_view = '0'
                            THEN 0
                            ELSE 1
                        END ASC
                    ")
                    ->orderByDesc('mesn_date_created')
                    ->paginate(8);

                $items = collect($notifications->items())
                    ->map(function ($notification) {
                        return [
                            'id' => $notification->mesn_id,
                            'message' => $notification->mesn_name,
                            'is_read' => (string) $notification->mesn_view === '1',
                            'created_at' => $notification->mesn_date_created,
                        ];
                    })
                    ->values();

            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'has_more' => $notifications->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load notifications.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // UNREAD COUNT
    // ============================================================

    public function unreadCount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
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

            $count = DB::table('message_notifications')
                ->where('usr_id', $user->usr_id)
                ->where('mesn_active', 1)
                ->where(function ($query) {
                    $query
                        ->whereNull('mesn_view')
                        ->orWhere('mesn_view', '')
                        ->orWhere('mesn_view', '0');
                })
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load unread notification count.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // MARK ONE AS READ
    // ============================================================

    public function markAsRead(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'notification_id' => 'required|integer',
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

            $notification = DB::table('message_notifications')
                ->where('mesn_id', $request->notification_id)
                ->where('usr_id', $user->usr_id)
                ->where('mesn_active', 1)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found.',
                ], 404);
            }

            DB::table('message_notifications')
                ->where('mesn_id', $request->notification_id)
                ->where('usr_id', $user->usr_id)
                ->update([
                    'mesn_view' => '1',
                    'mesn_date_modified' => now(),
                    'mesn_modified_by' => $user->usr_id,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to update notification.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // MARK ALL AS READ
    // ============================================================

    public function markAllAsRead(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
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

            DB::table('message_notifications')
                ->where('usr_id', $user->usr_id)
                ->where('mesn_active', 1)
                ->where(function ($query) {
                    $query
                        ->whereNull('mesn_view')
                        ->orWhere('mesn_view', '')
                        ->orWhere('mesn_view', '0');
                })
                ->update([
                    'mesn_view' => '1',
                    'mesn_date_modified' => now(),
                    'mesn_modified_by' => $user->usr_id,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to update notifications.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}