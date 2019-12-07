<?php

// namespace App\Http\Controllers;

// use App\User;
// use App\Poll;
// use App\Option;
// use Carbon\Carbon;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Hash;

// class AdminController extends Controller
// {
//     /**
//      * Create a new controller instance.
//      *
//      * @return void
//      */
//     public function __construct()
//     {
//         $this->middleware('admin.auth');
//     }
    
//     public function trending()
//     {
//         $poll = Poll::where('expirydate', '>=', Carbon::now())->get();
//         dd($poll);
//         if($poll){
//                 $trending = DB::table('votes')
//                 ->select('poll_id', DB::raw('count(*) as totalVote'))
//                 ->groupBy('poll_id')
//                 ->orderBy('totalVote', 'desc')
//                 ->take(10)
//                 ->get();           
//                 return response()->json([$trending, $poll], 200);
            
//         } return response()->json('No trending Post', 202);
//     }

//     public function deleteUser($id)
//     {
//         $user = User::findOrFail($id);
//         $user->delete();
        
//         return response()->json(['data' =>['success' => true, 'message' => 'User Deleted']], 200);
//     }

// }
