<?php

namespace App\Http\Controllers;

use App\User;
use App\Poll;
use Cloudder;
use App\Interest;
use App\Userinterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserPublicProfile extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
   public function showData(User $user, $username) {
     $userData  = $user->usernameCheck($username);
     $interest =  $userData->interest()->get();
     $polls = Poll::where('owner_id', $userData->id)
                          ->latest()
                          ->with('interest')
                          ->limit(20)
                          ->get();
     $pollsCount = Poll::where('owner_id', $userData->id)->count();

     return response()->json(['data' => [ 'success' => true,
                              'user' => $userData,
                              'interest' => $interest,
                              'polls' => $polls,
                              'pollCount' =>  $pollsCount,
                              'imageLink' => 'https://res.cloudinary.com/getfiledata/image/upload/',
                              'imageProp' => [
                                'cropType1' => 'c_fit',
                                'cropType2' => 'g_face',
                                'imageStyle' => 'c_thumb',
                                'heigth' => 'h_577',
                                'width' =>  '433',
                                'widthThumb' => 'w_200',
                                'aspectRatio' => 'ar_4:4'
                              ]
                              ]], 200);
   }

}
