<?php

namespace App\Http\Controllers;

use App\Poll;
use App\User;
use App\Interest;
use App\Option;
use App\Vote;
use Illuminate\Http\Request;
use App\Userinterest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserFeedsController extends Controller
 {
    /**
    * Create a new controller instance.
    *
    * @return void
    */

    public  $feeds   = [];
    public  $options = [];

    public function index( Request $request, $id = null )
 {
        $query = $request->query( 'search_poll' );

        if ( $query ) {

            $fetch_polls = $this->searchPoll( $query, $id );

        } else if ( $id == null ) {

            $fetch_polls =  Poll::whereIn( 'interest_id', $this->feedspermit() )
            ->withCount( 'votes' )
            ->orderBy( 'id', 'desc' )
            ->limit( 10 )
            ->get();

        } else {
            $this->Interestpermit( $id );
            $fetch_polls = Poll::where( 'interest_id', $id )
            ->withCount( 'votes' )
            ->orderBy( 'id', 'desc' )
            ->limit( 10 )
            ->get();
        }
        //This is the triggerQuery
        $this->triggerQuery( $fetch_polls, $vote_status_key = 'on' );
        return response()->json( ['data' =>['success' => true, 'feeds' => $this->feeds]], 200 );

    }

    public function scrolledfeeds( Request $request, $offset , $id = null )
    {
           $query = $request->query( 'search_poll' );

        if ( empty( $offset ) ) {
            return response()->json( ['data' =>['error' => false, 'message' => 'offset cannot be empty']], 400 );
        }
        if ( $query ) {

            $fetch_polls = $this->searchPoll( $query, $id );

        } else if (  $id == null ) {
            $fetch_polls = Poll::whereIn( 'interest_id', $this->feedspermit() )
            ->offset( ( int )$offset )
            ->withCount( 'votes' )
            ->orderBy( 'id', 'desc' )
            ->limit( 10 )
            ->get();
        } else {
            $this->Interestpermit( $id );
            $fetch_polls = Poll::where( 'interest_id', $id )
            ->offset( $offset )
            ->withCount( 'votes' )
            ->orderBy( 'id', 'desc' )
            ->limit( 10 )
            ->get();
        }
        //This is the triggerQuery
        $this->triggerQuery( $fetch_polls, $vote_status_key = 'on' );

        $offset += 10;
        return response()->json( ['data' =>['success' => true, 'scrolled_feeds' => $this->feeds, 'new_offset' => $offset]], 200 );

    }

    public function usersFeeds( $id )
 {
        $this->Interestpermit( $id );
        $fetch_polls = Poll::where( 'interest_id', $id )
        ->orderBy( 'id', 'desc' )
        ->withCount( 'votes' )
        ->limit( 50 )
        ->get();

        //This is the triggerQuery
        $this->triggerQuery( $fetch_polls );
        return response()->json( ['data' =>['success' => true, 'usersfeeds' => $this->feeds]], 200 );

    }

    //Search a specific poll

    public function searchPoll( $query , $id) {
        //Get the user in connection to the seacrh name
        $query_user = User::where( 'first_name', 'LIKE',  "%{$query}%" )
                            ->orWhere( 'last_name', 'LIKE', "%{$query}%" )
                            ->orWhere( 'username', 'LIKE', "%{$query}%" )
                            ->pluck( 'id' );
        //Get the Interest in connection to the seacrh query
        $query_interest = Interest::where('title', 'LIKE', "%{$query}%")
                            ->pluck('id');
          
        //get the option in connection to the seacrh query
        $query_option = Option::where('option', 'LIKE', "%{$query}%")
                               ->pluck('poll_id');

        if($id == null){
            $query_poll = Poll::whereIn( 'interest_id', $query_interest)
                ->orwhere( 'question', 'LIKE',  "%{$query}%" )->orWhereIn( 'owner_id', $query_user)
                ->orWhereIn( 'id', $query_option)
                ->orwhere('option_type', $query)
                ->withCount( 'votes' )
                ->orderBy( 'id', 'desc' )->limit( 10 )->get();
        }else {
            $query_poll = Poll::where( 'interest_id', $id)
                ->orwhere( 'question', 'LIKE',  "%{$query}%" )->orWhereIn( 'owner_id', $query_user)
                ->orWhereIn( 'id', $query_option)
                ->orwhere('option_type', $query)
                ->withCount( 'votes' )
                ->orderBy( 'id', 'desc' )->limit( 10 )->get();
        }
        
        return $query_poll;
    }

    public function feedspermit() {
        $check_interest = Userinterest::where( 'owner_id', Auth::user()->id )->pluck( 'interest_id' );
        return $check_interest;
    }

    public function Interestpermit( $id ) {
        $id_interest = Interest::where( 'id', $id )->exists();
        if ( !$id_interest ) {
            return response()->json( ['data' =>['error' => false, 'message' => 'Interest id']], 404 );
        }
    }

    public function triggerQuery( $fetch_polls, $vote_status_key = null ) {
        foreach ( $fetch_polls as $fetch_poll ) {
            //Fetch the user info
            $fetch_user     = User::where( 'id', $fetch_poll->owner_id )->first();
            //Fetch the user interest
            $fetch_interest = Interest::where( 'id', $fetch_poll->interest_id )->first();
            //Fetch the user options
            $fetch_options  = Option::where( 'poll_id', $fetch_poll->id )
            ->select( 'id', 'option' )
            ->get();

            if ( $vote_status_key == 'on' ) {
                $vote_status = $this->voteStatus( $fetch_poll->id );

            } else {
                $vote_status = null;
            }
            //Get the whole option related to the poll
            foreach ( $fetch_options as $fetch_option ) {
                $values = [
                    'option_id' => $fetch_option->id,
                    'option'    => $fetch_option->option
                ];
                array_push( $this->options, $values );
            }

            $data = [
                'interest_id' => $fetch_poll->interest_id,
                'poll_id'   => $fetch_poll->id,
                'poll'      => $fetch_poll->question,
                'interest'  => $fetch_interest->title,
                'poll_owner_id' => $fetch_poll->owner_id,
                'option_type' => $fetch_poll->option_type,
                'poll_date' => date( 'Y-m-d', strtotime( $fetch_poll->created_at ) ),
                'poll_startdate'  =>  date( 'Y-m-d', strtotime( $fetch_poll->startdate ) ),
                'poll_expirydate' =>  date( 'Y-m-d', strtotime( $fetch_poll->expirydat ) ),
                'firstname' => $fetch_user->first_name,
                'lastname'  => $fetch_user->last_name,
                'image_link'=> env( 'CLOUDINARY_IMAGE_LINK' ).'/w_200,c_thumb,ar_4:4,g_face/',
                'image'     => $fetch_user->image,
                'option'    => $this->options,
                'vote_status' => $vote_status,
                'votes_count' => $fetch_poll->votes_count

            ];
            array_push( $this->feeds, $data );
            $this->options = [];
        }
    }

    public function voteStatus( $poll_id ) {
        $check_vote_status = Vote::where( 'voter_id', Auth::user()->id )->where( 'poll_id', $poll_id )->exists();
        if ( $check_vote_status ) {
            $vote_info = Vote::where( 'voter_id', Auth::user()->id )->where( 'poll_id', $poll_id )->first();
            return  $vote_info->option_id;
        } else {
            return false;
        }
    }

}
