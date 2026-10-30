<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class NewsController extends Controller
{
    function get_news($user_id): JsonResponse
    {
        $news = DB::table('news')
            ->where('news.news_active', 1)
            ->leftJoin('users', 'news.news_created_by', '=', 'users.usr_id')
            ->select(
                'news.news_id',
                'news.news_date',
                'news.news_title',
                'news.news_image',
                'news.news_content',
                'news.news_date_created',
            )
            ->orderBy('news.news_date_created')
            ->get();

        foreach ($news as $new) {
            $new->like_count = DB::table('news_likes')
                ->where('like_active', 1)
                ->where('news_id', $new->news_id)
                ->count();

            $new->user_liked = DB::table('news_likes')
                ->where('news_id', $new->news_id)
                ->where('usr_id', $user_id)
                ->orderByDesc('like_id')
                ->pluck('like_active')
                ->first();
            
        }
        return response()->json($news);
    }

    function get_news_comments($news_id): JsonResponse
    {
        $comments = DB::table('news_comments')
            ->where('news_id', $news_id)
            ->where('cmt_active', 1)
            ->leftJoin('users', 'users.usr_id', 'news_comments.usr_id')
            ->select(
                'cmt_id',
                'cmt_comment',
                'cmt_image',
                'cmt_date_created',
                'users.usr_id',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_last_name',
                'users.usr_image_path',
            )
            ->get();

        return response()->json($comments);
    }

    function post_comment(Request $request): JsonResponse
    {
        $comment = $request->comment;
        $news_id = $request->newsID;
        $user_id = $request->userID;

        DB::table('news_comments')
            ->where('news_id', $news_id)
            ->where('cmt_active', 1)
            ->insert([
                'news_id' => $news_id,
                'usr_id' => $user_id,
                'cmt_comment' => $comment,
                'cmt_date_created' => Carbon::now(),
                'cmt_active' => 1
            ]);

        return $this->get_news_comments($news_id);
    }

    function like_news(Request $request): JsonResponse
    {
        $user_id = $request->userID;
        $news_id = $request->newsID;

        if ($news_id) {
            DB::table('news_likes')->insert([
                'news_id' => $news_id,
                'usr_id' => $user_id,
                'like_active' => 1
            ]);
        }

        return response()->json(['liked']);
        // return $this->get_news($user_id);
    }

    function dislike_news(Request $request): JsonResponse
    {
        $user_id = $request->userID;
        $news_id = $request->newsID;

        $liked = DB::table('news_likes')
            ->where('news_id', $news_id)
            ->where('usr_id', $user_id)
            ->latest();

        if ($liked) {
            DB::table('news_likes')
                ->where('news_id', $news_id)
                ->where('usr_id', $user_id)
                ->delete();
        }


        return response()->json(['disliked']);
        // return $this->get_news($user_id);
    }
}
