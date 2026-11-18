<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class MainController extends Controller
{
    public function home()
    {
        $vision = DB::table('homepage_vision')->where('vis_active', 1)->first();
        $mission = DB::table('homepage_mission')->where('mis_active', 1)->first();

        $goals = DB::table('homepage_goals')->where('goals_active', 1)->first();
        $objectives = DB::table('homepage_objectives')->where('obj_active', 1)->first();

        // Board members grouped by position (pos_id 1..6) -> one org-chart level each.
        $members = DB::table('users')
            ->join('positions', 'users.usr_position', '=', 'positions.pos_id')
            ->where('users.usr_active', 1)
            ->where('positions.pos_active', 1)
            ->whereIn('positions.pos_id', [1, 2, 3, 4, 5, 6])
            ->select(
                'users.usr_id',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_last_name',
                'positions.pos_id',
                'positions.pos_name'
            )
            ->orderBy('positions.pos_id', 'asc')
            ->orderBy('users.usr_last_name', 'asc')
            ->orderBy('users.usr_first_name', 'asc')
            ->get();

        $levels = $members->groupBy('pos_id');

        $partners = DB::table('homepage_partners')
            ->where('prt_active', 1)
            ->orderBy('prt_title', 'asc')
            ->get();

        return view('homepage.home', compact(
            'vision',
            'mission',
            'goals',
            'objectives',
            'levels',
            'partners'
        ));
    }

    public function about()
    {
        $about = DB::table('homepage_about')
            ->where('about_active', 1)
            ->first();

        return view('homepage.about', compact('about'));
    }

    public function vision_mission()
    {
        $vision = DB::table('homepage_vision')->where('vis_active', 1)->first();
        $mission = DB::table('homepage_mission')->where('mis_active', 1)->first();

        return view('homepage.vision_mission', compact('vision', 'mission'));
    }

    public function goals_objectives()
    {
        $goals = DB::table('homepage_goals')->where('goals_active', 1)->first();
        $objectives = DB::table('homepage_objectives')->where('obj_active', 1)->first();

        return view('homepage.goals_objectives', compact('goals', 'objectives'));
    }

    public function board_members()
    {
        $members = DB::table('users')
            ->join('positions', 'users.usr_position', '=', 'positions.pos_id')
            ->where('users.usr_active', 1)
            ->where('positions.pos_active', 1)
            ->whereIn('positions.pos_id', [1, 2, 3, 4, 5, 6])
            ->select(
                'users.usr_id',
                'users.usr_first_name',
                'users.usr_middle_name',
                'users.usr_last_name',
                'positions.pos_id',
                'positions.pos_name'
            )
            ->orderBy('positions.pos_id', 'asc')
            ->orderBy('users.usr_last_name', 'asc')
            ->orderBy('users.usr_first_name', 'asc')
            ->get();

        $levels = $members->groupBy('pos_id');

        return view('homepage.board_members', compact('levels'));
    }

    public function partners()
    {
        $partners = DB::table('homepage_partners')
            ->where('prt_active', 1)
            ->orderBy('prt_title', 'asc')
            ->get();

        return view('homepage.partners', compact('partners'));
    }

    public function attorney_ask()
    {
        return view('homepage.attorney_ask');
    }

    public function attorney_answers()
    {
        $questions = DB::table('homepage_questions')
            ->leftJoin('users', 'homepage_questions.usr_id', '=', 'users.usr_id')
            ->where('homepage_questions.que_status', '=', 'APPROVED')
            ->where('homepage_questions.que_active', '=', 1)
            ->select(
                'homepage_questions.que_id',
                'homepage_questions.que_question',
                'homepage_questions.que_answer',
                'homepage_questions.que_video_link',
                'homepage_questions.que_date_created',
                'homepage_questions.que_date_answered',
                'users.usr_first_name',
                'users.usr_last_name',
                DB::raw('CASE
                    WHEN homepage_questions.que_is_anonymous = 1
                        OR homepage_questions.usr_id IS NULL
                        OR homepage_questions.usr_id = 0
                    THEN 1 ELSE 0 END AS is_anonymous')
            )
            ->orderBy('homepage_questions.que_date_answered', 'desc')
            ->get();

        return view('homepage.attorney_answers', compact('questions'));
    }
}