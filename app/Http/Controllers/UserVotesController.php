<?php

namespace App\Http\Controllers;

use App\User;
use App\Poll;
use App\Vote;
use App\Option;
use App\Interest;
use App\Userinterest;
use Faker\Factory as Faker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

class UserVotesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function create(Request $request, $id)
    {
        $poll = Poll::find($id);

        $this->validateVote($request);
        if($poll){
          //start temporay transaction
          DB::beginTransaction();
        try{
            $vote = new Vote;
            //This check if the user has voted before ... Please commented
                // if(!Vote::where('owner_id', Auth::user()->id)->where('poll_id', $poll->id)->exists())
                // {
                    $vote->voter_id = Auth::user()->id;
                    $vote->owner_id = $request->input('poll_owner_id');
                    $vote->option_id = $request->input('option_id');
                    $vote->poll_id = $poll->id;
                    $vote->save();
                    //if operation was successful save changes to database
                    DB::commit();
                    return response()->json(['success' => true, 'check' => 1, 'message' => 'Voted Successful!', 'vote' => $vote], 201);
                // }

                // $unVote =Vote::where('owner_id', Auth::user()->id)->where('poll_id', $poll->id)->first();
                // $unVote->delete();
                //if operation was successful save changes to database
                // DB::commit();
                // return response()->json(['success' => true, 'check' => 0, 'message' => 'Unvote Successful!', 'unvote' => $unVote], 201);
            }catch (\Exception $e) {
              //if any operation fails, Thanos snaps finger - user was not created
              DB::rollBack();
                return response()->json(['error' => false, 'message'=> "Opps! Something went wrong!", 'hint' => $e->getMessage()], 501);
            }
        } return response()->json(['error' => false, 'message'=> "Opps! Something thing wrong!"], 404);



    }

    public function validateVote(Request $request){

		$rules = [
            'option_id' => 'required',
            'poll_owner_id' => 'required'
        ];
		$this->validate($request, $rules);
    }
}
