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

class UserPollStatisticsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function user_general_count()
    {

        //Total Poll a User have
        $total_poll_count = Poll::where('owner_id', Auth::user()->id)
                                ->orderBy('created_at', 'desc')->count();

        //Total Vote Count
        $total_vote_count = Vote::where('owner_id', Auth::user()->id)
                                ->orderBy('created_at', 'desc')
                                ->count();

        $polls_weekly = Poll::where('owner_id', Auth::user()->id)->whereBetween('created_at', 
        [Carbon::now()->startOfWeek(),Carbon::now()->endOfWeek()])->count();

        $polls_monthy = Poll::where('owner_id', Auth::user()->id)->whereBetween('created_at', 
        [Carbon::now()->startOfMonth(),Carbon::now()->endOfMonth()])->count();

        $polls_yearly = Poll::where('owner_id', Auth::user()->id)->whereBetween('created_at', 
        [Carbon::now()->startOfYear(),Carbon::now()->endOfYear()])->count();

        return response()->json([
             'total_poll_count' =>  $total_poll_count,
             'polls_weekly' => $polls_weekly,
             'polls_monthy' => $polls_monthy,
             'polls_yearly' => $polls_yearly,
             'total_vote_count' =>  $total_vote_count,
        ], 200);
    }

    public function user_poll_count($poll_id = null)
    {
        $total_male_count = 0;
        $total_female_count = 0;
        $total_other_count = 0;

        //Date Different
        $btw_16_25 = 0;
        $btw_26_36 = 0;
        $btw_37_51 = 0;
        $btw_52_above = 0;
        $age = [];

        //Total Vote Count for a poll
        $total_vote_count = Vote::where('owner_id', Auth::user()->id)
                                ->where('poll_id', $poll_id)
                                ->orderBy('created_at', 'desc')
                                ->count();

        $total_genders = Vote::where('owner_id', Auth::user()->id)
                                     ->where('poll_id', $poll_id)
                                     ->with('voter_users')
                                     ->orderBy('created_at', 'desc')
                                     ->get();


        foreach ($total_genders as $total_gender) {
            
            //Get the age Difference
            $current_date = date_create(Carbon::now());//Get the current date
            $present_date = date_create($total_gender->voter_users->dob);//Voter date of birth
            $age_range = date_diff($current_date, $present_date);//The difference
            
            //First age range check [16 - 25]
            //Second age range check [26 - 36]
            // Third Stage [37 - 51]
            // // Final Stage [52 - above]
            // array_push($age, $age_range);
            $age_range = $age_range->y;
            if ((16 <= $age_range) && ($age_range <= 25)) {
                $btw_16_25+=1;
            }else if((26 <= $age_range) && ($age_range <= 36)){
                $btw_26_36+=1;
            }else if ((37 <= $age_range) && ($age_range <= 51)) {
                $btw_37_51+=1;
            }else if (($age_range >= 52)){
                $btw_52_above+=1;
            }


            if($total_gender->voter_users->gender == 'Male'){
                $total_male_count+=1;
            }else if($total_gender->voter_users->gender == 'Female') {
                $total_female_count+=1;
            }else {
                $total_other_count+=1;
            }
        }
        return response()->json([
             'age' => $age,
             'total_vote_count' =>  $total_vote_count,
             'total_vote_count(Male)' =>  $total_male_count,
             'total_vote_count(Female)' =>  $total_female_count,
             'total_other_count(other)' => $total_other_count,
             'btw_16_25' => $btw_16_25,
             'btw_26_36' => $btw_26_36,
             'btw_37_51' => $btw_37_51,
             'btw_52_above' => $btw_52_above
        ], 200);
    }

}
