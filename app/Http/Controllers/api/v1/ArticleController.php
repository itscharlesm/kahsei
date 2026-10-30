<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

use function PHPUnit\Framework\isEmpty;

class ArticleController extends Controller
{
    function get_articles($user_id): JsonResponse
    {
        $articles = DB::table('articles')
            ->where('articles.art_active', 1)
            ->leftJoin('users', 'articles.art_created_by', '=', 'users.usr_id')
            ->select(
                'articles.art_id',
                'articles.art_title',
                'articles.art_image',
                'articles.art_content',
                'articles.art_date_created',
                // 'users.usr_first_name'
                // DB::raw('COUNT(DISTINCT article_likes.like_id) as like_count'),
                // DB::raw('COUNT(article_comments.cmt_id) as comment_count'),
                // DB::raw('(SELECT COUNT(*) FROM article_likes WHERE article_likes.art_id = articles.art_id AND article_likes.usr_id = ' . $user_id . ') as liked_by_user'),

            )
            ->orderBy('articles.art_date_created')
            ->get();

        foreach ($articles as $article) {
            // $article->comments = DB::table('article_comments')
            //     ->leftJoin('users', 'article_comments.usr_id', '=', 'users.usr_id')
            //     ->where('art_id', $article->art_id)
            //     ->select(
            //         'article_comments.cmt_comment',
            //         'article_comments.cmt_image',
            //         'article_comments.cmt_date_created',
            //         'users.usr_first_name',
            //         'users.usr_middle_name',
            //         'users.usr_last_name'
            //     )
            //     ->get();

            $article->like_count = DB::table('article_likes')
                ->where('like_active', 1)
                ->where('art_id', $article->art_id)
                ->count();

            $article->user_liked = DB::table('article_likes')
                ->where('art_id', $article->art_id)
                ->where('usr_id', $user_id)
                ->orderByDesc('like_id')
                ->pluck('like_active')
                ->first();
            
        }
        return response()->json($articles);
    }

    function get_article_comments($article_id): JsonResponse
    {
        $comments = DB::table('article_comments')
            ->where('art_id', $article_id)
            ->where('cmt_active', 1)
            ->leftJoin('users', 'users.usr_id', 'article_comments.usr_id')
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
        $article_id = $request->articleID;
        $user_id = $request->userID;

        DB::table('article_comments')
            ->where('art_id', $article_id)
            ->where('cmt_active', 1)
            ->insert([
                'art_id' => $article_id,
                'usr_id' => $user_id,
                'cmt_comment' => $comment,
                'cmt_date_created' => Carbon::now(),
                'cmt_active' => 1
            ]);

        return $this->get_article_comments($article_id);
    }

    function like_article(Request $request): JsonResponse
    {
        $user_id = $request->userID;
        $article_id = $request->articleID;

        if ($article_id) {
            DB::table('article_likes')->insert([
                'art_id' => $article_id,
                'usr_id' => $user_id,
                'like_active' => 1
            ]);
        }

        return response()->json(['liked']);
        // return $this->get_articles($user_id);
    }

    function dislike_article(Request $request): JsonResponse
    {
        $user_id = $request->userID;
        $article_id = $request->articleID;

        $liked = DB::table('article_likes')
            ->where('art_id', $article_id)
            ->where('usr_id', $user_id)
            ->latest();

        if ($liked) {
            DB::table('article_likes')
                ->where('art_id', $article_id)
                ->where('usr_id', $user_id)
                ->delete();
        }


        return response()->json(['disliked']);
        // return $this->get_articles($user_id);
    }
}
