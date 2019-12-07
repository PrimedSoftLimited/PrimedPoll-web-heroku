<?php

namespace App\Http\Controllers;

use App\Poll;
use App\User;
use App\Option;
use App\Interest;
use App\Follow;
use App\Userinterest;
use App\Vote;
use Cloudder;
use Faker\Factory as Faker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserPollController extends Controller
{    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
    public function index()
    {
        $poll = Poll::where('owner_id', Auth::user()->id)
                ->withCount('votes')
                ->with(['options' => function($query){
                    $query->withCount('votes');
                 }])
                ->get();
        return response()->json($poll, 200);
    }
    public function voters($id) {
        $pluck_user_ids = Vote::where('poll_id', $id)->pluck('owner_id');
        if ($pluck_user_ids) {
            $user = [];
            foreach ($pluck_user_ids as $pluck_user_id) {
               $get_user = User::where('id', $pluck_user_id)->get();

               $get_follow_status = Follow::where('follower_id', Auth::user()->id)
               ->where('following_id', $pluck_user_id)->get();
               $data = [$get_user, $get_follow_status];
               array_push($user, $data);
            }
            return response()->json(['success' => true,
             'message' => 'users who voted', 'users' =>  $user,
             'image_link' => 'https://res.cloudinary.com/getfiledata/image/upload/w_200,c_thumb,ar_4:4,g_face/'
            ]);
        }else {
            return response()->json(['error' => true, 'message' => 'Invalid poll id']);
        }
    }
    public function show($id)
    {   
        $pollCheck = Poll::findOrFail($id);
        if(Auth::user()->id == $pollCheck->owner_id)
            {
                $poll = Poll::where('id', $id)
                    ->withCount('votes')
                    ->with(['options' => function($query){
                        $query->withCount('votes');
                     }])
                    ->get();
                return response()->json($poll, 200);
            }
            return response()->json('Unauthorized Access!', 400);
    }
  
    public function create(Request $request, $id)
    {
        $user = Auth::user();
        
        $interest = $user->interest()->where('interest_id', $id)->first();

        if($interest)
        {
            // return $request;
            $this->validatePoll($request);
            $poll = new Poll;
            if(!Poll::where('question', $request->input('question'))->where('interest_id', $id)->exists())
                {
                    DB::beginTransaction();
                    try {
                        $poll->question    = $request->input('question');
                        $poll->startdate   = $request->input('startdate');
                        $poll->option_type = $request->input('option_type');
                        $poll->expirydate  = $request->input('expirydate');
                        $poll->interest_id = $interest->id;
                        $poll->owner_id    = Auth::user()->id;
                        $poll->save();

                        if ($request->input('option_type') === "text") {
                            $items = $request->input('options');
                            //This handles the text options 
                            $res   = $this->textOption($items, $poll);
                        }else if($request->input('option_type') === "image") { 
                            $files = $request->file('options');
                            $res = $this->imageOption($files, $poll); 
                        }

                        $poll = Poll::where('id', $poll->id)
                        ->withCount('votes')
                        ->with(['options' => function($query){
                            $query->withCount('votes');
                         }])
                        ->get();
                        $res['polls'] = $poll;
                        DB::commit();
                        return response()->json($res,  $res['status_code']);
                    }catch(\Exception $e) {
                        DB::rollBack();
                        $res['message'] = 'An error occured, please try again!';
                        $res['hint']    = $e->getMessage();
                        
                        return response()->json($res, 501);
                    }

                 }else {
                     return response()->json('Poll exist for Interest', 422);
                 }

            }else {
               return response()->json('Please Select Interest Before Creating Poll', 422);  
            }
    }

    public function textOption($items, $poll) {
              foreach($items as $item) {
                $option = new Option;
                $option->option = $item;
                $option->owner_id = Auth::user()->id;
                $option->poll_id = $poll->id;
                $option->save();
            }
            $res['status'] = true;
            $res['message'] = 'Poll created Successfully';
            $res['status_code'] = 201;
            return $res;
    }
     public function imageOption($files, $poll) {
        $format = array('jpg', 'jpeg', 'png', 'gif');
        $data = [];
       
              foreach($files as $file) {
                if ($file->isValid()) {
                    $extension = strtolower($file->extension());
                    $file_size = filesize($file);
                    if (in_array($extension, $format)) {
                    
                        if($file_size > 800000) {
                            $res['message'] = "An image with title ".$file->getClientOriginalName()." size is too large (less than 5mb only)";
                             $res['status_code'] =  422;
                             return $res;
                        }
                        DB::beginTransaction();
                        try{
                            $result = $this->imageOptionUpload($file, $poll);

                            $option = new Option;
                            $option->option = $result;
                            $option->owner_id = Auth::user()->id;
                            $option->poll_id = $poll->id;
                            $option->save();

                            DB::commit();
                            array_push($data, $result);
                        }catch(\Exception $e) {
                          DB::rollBack();
                              $res['status']   = false;
                              $res['message']  = 'An error occured, please try again';
                              $res['hint']     = $e->getMessage();
                              $res['status_code'] =  501;
                              return $res;
                            }
                    }else {
                          $res['message'] = 'Format not support or invalid, use only (jpg,png,jpeg,gif)';
                          $res['status_code'] = 422;
                          return $res;
                    }
                }
            }
        
            if(count($data) >= 3) {
                $res['status'] = true;
                $res['message'] = "Upload Successful!";
                $res['image_link'] = 'https://res.cloudinary.com/getfiledata/image/upload/';
                $res['image_format']  = 'w_100,ar_1:1,c_fill,g_auto/';
                $res['image_example_link']  = 'https://res.cloudinary.com/getfiledata/image/upload/w_100,ar_1:1,c_fill,g_auto/'.$result;

                $res['status_code'] = 201;
                return $res;
            }
    }
    public function imageOptionUpload($file, $poll) {
            $image = $file->getRealPath();
            //Store to Cloudinary 
            $cloudder = Cloudder::upload($image);
            $getResult = $cloudder->getResult();
            
            $file_url     = $getResult["public_id"];
            //Get the image format from the api
            $format       = $getResult["format"];

            $user_image   = $file_url.".".$format;

            return $user_image;
    }
    public function update(Request $request, $id)
    {
        $pollCheck = Poll::where('id', $id)->exists();
        $poll = Poll::findOrFail($id);
        $option = Option::where('poll_id', $poll)->get();

        if(Auth::user()->id == $poll->owner_id)
        {
            if($pollCheck)
            {
                $this->validatePoll($request);

                $poll->question = $request->input('question');
                $poll->startdate = $request->input('startdate');
                $poll->expirydate = $request->input('expirydate');
                $poll->save();

                $items = $request->input('options');

                foreach($items as $item) {
                    $option = new Option;
                    $option->option = $item['option'];
                    $option->owner_id = Auth::user()->id;
                    $option->poll_id = $poll->id;
                    $option->save();
                }

                $res['status'] = "{$poll->question} Updated Successfully!";
                $res['poll'] = $poll;
                $res['options'] = Option::where('poll_id', $poll->id)->get();
                return response()->json($res, 201);

            } return response()->json('Poll Does not Exist', 400);

        } return response()->json('Unauthorized Acess', 400);

    }
    
    public function destroy(Request $request, $id)
    {
        $pollCheck = Poll::where('id', $id)->exists();
        $poll = Poll::findOrFail($id);

        if(Auth::user()->id == $poll->owner_id)
        {
            if($pollCheck)
            {
                $poll->delete();
            
                $res['status'] = "Deleted Successfully!";
                return response()->json($res, 201);

            } return response()->json('Poll Does not Exist', 400);

        } return response()->json('Unauthorized Acess', 400);
                        
    }

    public function validatePoll(Request $request){

		$rules = [
            'question' => 'required|min:3',
            'options' => 'required',
            'startdate' => 'required|date|before:expirydate',
            'option_type' => 'required',
            'expirydate' => 'required|date|after:startdate',
        ];
         
		$this->validate($request, $rules);
    }
//upload_max_filesize = 10M
//post_max_size = 10M
//memory_limit = 32M
}
