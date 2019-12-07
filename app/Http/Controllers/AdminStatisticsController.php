<?php

namespace App\Http\Controllers;

use App\User;
use App\Poll;
use App\Option;
use App\Vote;
use App\Interest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminStatisticsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public $trending_user_by_votes = [];
    public $trending_user_by_polls = [];
    public $trending_polls_by_votes = [];
    public $trending_category_by_poll = [];

    public function __construct()
    {
        $this->middleware('admin.auth');
    }
    
    public function users()
    {

        $users = User::orderBy('created_at', 'desc')->paginate(10);

           //$t_p_by_v (Trending user by vote)
        $t_u_by_v = DB::table('votes')
                                    ->select('owner_id', DB::raw('count(*) as totalVote'))
                                    ->groupBy('owner_id')
                                    ->orderBy('totalVote', 'desc')
                                    ->take(10)
                                    ->get(); 
          //$t_p_by_v (Trending user by pole)
        $t_u_by_p = DB::table('polls')
                                    ->select('owner_id', DB::raw('count(*) as totalPoll'))
                                    ->groupBy('owner_id')
                                    ->orderBy('totalPoll', 'desc')
                                    ->take(10)
                                    ->get(); 
        foreach ($t_u_by_v as $t) {
            $user = User::where('id', $t->owner_id)->first();
            array_push($this->trending_user_by_votes , ['user' => $user, 'voteCount' =>$t->totalVote]);
        }
        foreach ($t_u_by_p as $p) {
            $user = User::where('id', $p->owner_id)->first();
            array_push($this->trending_user_by_polls , ['user' => $user, 'pollCount' =>$p->totalPoll]);
        }

		return response()->json([
             'image_link' => 'https://res.cloudinary.com/getfiledata/image/upload/w_200,c_thumb,ar_4:4,g_face/',
             'latest_registered_user' => $users,
             'trending_users_by_votes' => $this->trending_user_by_votes,
             'trending_users_by_polls' => $this->trending_user_by_polls

        ], 200);
    }


    public function polls()
    {
        $polls = Poll::where('expirydate', '>=', Carbon::now())->with('options')->orderBy('created_at', 'asc')->paginate(10);

        //$t_p_by_v (Trending poll by vote)
        $t_p_by_v = DB::table('votes')->select('poll_id', DB::raw('count(*) as totalVote'))
            ->groupBy('poll_id')
            ->orderBy('totalVote', 'desc')
            ->take(10)
            ->get();   

        foreach ($t_p_by_v as $t) {
            $poll = Poll::where('id', $t->poll_id)->with('users')->first();
            array_push($this->trending_polls_by_votes , [
                 'poll' => $poll, 'voteCount' =>$t->totalVote]);
        }

        return response()->json(
            [
             'image_link' => 'https://res.cloudinary.com/getfiledata/image/upload/w_200,c_thumb,ar_4:4,g_face/',
             'trending_poll_by_expiry_date_poll' => $polls,
             'trending_polls_by_vote' => $this->trending_polls_by_votes

            ], 200);
            
    }


    public function interest()
    {
        $interest_count = Interest::count();
        $t_c_by_p = DB::table('polls')->select('interest_id', DB::raw('count(*) as totalPoll'))
                    ->groupBy('interest_id')
                    ->orderBy('totalPoll', 'desc')
                    ->take(10)
                    ->get();      

        foreach ($t_c_by_p as $t) {
            $category = Interest::where('id', $t->interest_id)->first();
            array_push($this->trending_category_by_poll  , ['interest' => $category, 'pollCount' =>$t->totalPoll]);
        }
        return response()->json([
            'interest_count' => $interest_count,
            'trending_interest_by_poll' => $this->trending_category_by_poll
        ], 200);
            
    }

    public function GenderCount() {
       $polls_male_total_poll_count=0;
       $polls_female_total_poll_count=0;
       $polls_others_total_poll_count=0;

       $vote_male_total_poll_count=0;
       $vote_female_total_poll_count=0;
       $vote_others_total_poll_count=0;

       $male_count =  User::where('gender', 'Male')->count();
         $female_count =  User::where('gender', 'Female')->count();
           $others_count =  User::where('gender', 'Others')->count();

           $data= DB::table('polls')
                            ->select('owner_id', DB::raw('count(*) as totalPoll'))
                            ->groupBy('owner_id')
                            ->orderBy('totalPoll', 'desc')
                            ->get(); 

              foreach ($data as $x) {
                $user = User::where('id', $x->owner_id)->pluck('gender');

                if($user[0] == 'Male') {
                    $polls_male_total_poll_count+=1;
                }elseif ($user[0] == 'Female') {
                    $polls_female_total_poll_count+=1;
                }else {
                    $polls_others_total_poll_count+=1;           }
                }
            $data_2= DB::table('votes')
                            ->select('owner_id', DB::raw('count(*) as totalVote'))
                            ->groupBy('owner_id')
                            ->orderBy('totalVote', 'desc')
                            ->get(); 

              foreach ( $data_2 as $y) {
                $user = User::where('id', $y->owner_id)->pluck('gender');

                if($user[0] == 'Male') {
                    $vote_male_total_poll_count+=1;
                }elseif ($user[0] == 'Female') {
                    $vote_female_total_poll_count+=1;
                }else {
                    $vote_others_total_poll_count+=1;           }
                }

           return response()->json([
            'male_count' => $male_count,
             'female_count' => $female_count,
              'others_count' => $others_count,
               'polls_interest_total_male_count' => $polls_male_total_poll_count,
                 'polls_interest_total_female_count' => $polls_female_total_poll_count,
                    'polls_interest_total_others_count' => $polls_others_total_poll_count,
                       'vote_total_male_count' =>$vote_male_total_poll_count,
                         'vote_total_female_count' =>$vote_female_total_poll_count,
                            'vote_total_others_count' =>$vote_others_total_poll_count,

            ], 200);
        
    }

    public function periodCount() {
        $dailyCount = $this->dailyCount();
        $weeklyCount = $this->weeklyCount();
        $monthlyCount = $this->monthlyCount();
        $annualCount = $this->annualCount();

        return response()->json([
            'status' => true,
            'dailyCount'   => $dailyCount,
            'weeklyCount'  => $weeklyCount,
            'monthlyCount' => $monthlyCount,
            'annualCount'  => $annualCount

        ], 200);
    }
    public function dailyCount(){
        $startDay = Carbon::now()->startOfDay();
        $endDay   = $startDay->copy()->endOfDay();

        $polls_daily = Poll::whereBetween('created_at', 
        [$startDay,  $endDay])->count();

        $votes_daily = Vote::whereBetween('created_at', 
        [$startDay,  $endDay])->count();

        $dailyCount = [
            "polls_daily" => $polls_daily,
            "votes_daily" => $votes_daily
        ];
        return $dailyCount;

    }

    public function weeklyCount(){

        $polls_weekly = Poll::whereBetween('created_at', 
        [Carbon::now()->startOfWeek(),Carbon::now()->endOfWeek()])->count();

        $votes_weekly = Vote::whereBetween('created_at', 
        [Carbon::now()->startOfWeek(),Carbon::now()->endOfWeek()])->count();

        $weeklyCount = [
            "polls_weekly" => $polls_weekly,
            "votes_weekly" => $votes_weekly
        ];
        return $weeklyCount;

    }

    public function monthlyCount(){

        $polls_monthly = Poll::whereBetween('created_at', 
        [Carbon::now()->startOfMonth(),Carbon::now()->endOfMonth()])->count();

        $votes_monthly = Vote::whereBetween('created_at', 
        [Carbon::now()->startOfMonth(),Carbon::now()->endOfMonth()])->count();

        $monthlyCount = [
            "polls_monthly" => $polls_monthly,
            "votes_monthly" => $votes_monthly
        ];
        return $monthlyCount;
    }

    public function annualCount(){
        $polls_yearly = Poll::whereBetween('created_at', 
        [Carbon::now()->startOfYear(),Carbon::now()->endOfYear()])->count();

        $votes_yearly = Vote::whereBetween('created_at', 
        [Carbon::now()->startOfYear(),Carbon::now()->endOfYear()])->count();

        $yearlyCount = [
            "polls_yearly" => $polls_yearly,
            "votes_yearly" => $votes_yearly
        ];
        return $yearlyCount;
    }
}
