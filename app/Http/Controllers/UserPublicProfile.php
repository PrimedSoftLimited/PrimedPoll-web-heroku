<?php

namespace App\Http\Controllers;

use App\User;
use App\Poll;
use App\Follow;
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
  public $following = false;
  public function showData(User $user, $permission = 0, $onSession = 0, $username)
  {
    $userData  = $user->usernameCheck($username);
    $interest =  $userData->interest()->get();
    $polls = Poll::where('owner_id', $userData->id)
      ->latest()
      ->with('interest')
      ->withCount('votes')
      ->with(['options' => function ($query) {
        $query->withCount('votes');
      }])
      ->limit(20)
      ->get();
    $pollsCount = Poll::where('owner_id', $userData->id)->count();

    if ($permission == 1) {
      $follow_check = Follow::where('follower_id', $onSession)->where('following_id', $userData->id)->exists();
      $onSession = true;
      if ($follow_check) {
        $this->following = true;
      }
    }
    return response()->json(['data' => [
      'success' => true, 'user' => $userData, 'interest' => $interest, 'polls' => $polls,
      'pollCount' =>  $pollsCount, 'imageLink' => env('CLOUDINARY_IMAGE_LINK'),
      'imageProp' => [
        'cropType1' => 'c_fit',
        'cropType2' => 'g_face',
        'imageStyle' => 'c_thumb',
        'heigth' => 'h_577',
        'width' =>  '433',
        'widthThumb' => 'w_200',
        'aspectRatio' => 'ar_4:4'
      ],
      'following' => $this->following,
      'onSession' => $onSession
    ]], 200);
  }
}
