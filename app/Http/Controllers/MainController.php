<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

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

    public function attorney_ask_submit(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'question' => 'required|string|max:2000',
        ], [
            'question.required' => 'Please type your question first.',
            'question.max' => 'Your question must not be more than 2000 characters.',
        ]);

        if ($validator->fails()) {
            alert()->error('Invalid Input', $validator->errors()->first());
            return redirect()->back()->withInput();
        }

        $question = trim($request->question);

        if ($question === '') {
            alert()->error('Invalid Input', 'Please type your question first.');
            return redirect()->back();
        }

        $usrId = session('usr_id');
        $isLoggedIn = !empty($usrId);

        // Logged in: anonymous only if the box is checked (usr_id is still saved)
        // Not logged in: always anonymous, usr_id = null
        $isAnonymous = $isLoggedIn ? ($request->has('is_anonymous') ? 1 : 0) : 1;

        DB::table('homepage_questions')->insert([
            'que_uuid' => generateuuid(),
            'usr_id' => $isLoggedIn ? $usrId : null,
            'que_is_anonymous' => $isAnonymous,
            'que_question' => $question,
            'que_status' => 'FOR APPROVAL',
            'que_active' => 1,
            'que_date_created' => Carbon::now(),
            'que_created_by' => $isLoggedIn ? $usrId : null,
        ]);

        if ($isLoggedIn) {
            logUserActivity('Itanong mo kay Attorney', 'Submitted a question for approval.');
        }

        alert()->success('Question Submitted', 'Thank you! Your question will be posted once it has been reviewed.');

        return redirect()->back();
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