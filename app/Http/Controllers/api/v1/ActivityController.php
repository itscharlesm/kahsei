<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * get_activities
     */
    function get_activities(): JsonResponse
    {
        $activities = Activity::whereActActive(1)
            ->select(
                'act_id',
                'act_title',
                'act_content',
                'act_image',
                'act_created_by',
                'act_date_created'
            )
            ->latest()
            ->get();

        return response()->json($activities);
    }
}
