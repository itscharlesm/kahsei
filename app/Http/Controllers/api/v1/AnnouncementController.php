<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * get_announcements
     */
    function get_announcements(): JsonResponse
    {
        $announcements = Announcement::whereAnnActive(1)
            ->select(
                'ann_id',
                'ann_title',
                'ann_content',
                'ann_image',
                'ann_created_by',
                'ann_date_created'
            )
            ->latest()
            ->get();

        return response()->json($announcements);
    }
}
