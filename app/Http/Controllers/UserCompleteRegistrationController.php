<?php

namespace App\Http\Controllers;

use App\User;
use App\Userinterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use libphonenumber\PhoneNumberType;

class UserCompleteRegistrationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function update(Request $request)
    {
        $user = Auth::guard('api')->user();

        $this->validateRequest($request);
        try{
        $default_image = 'noimage.png';
        $uniqueID = mt_rand(00000, 90000);

        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->phone = $request->input('phone');
        $user->country = $request->input('country');
        $user->gender = $request->input('gender');
        $user->dob = $request->input('dob');
        $user->image = $default_image;
        $user->username = '@'.$user->first_name.$uniqueID;

        $interest_ids = $request->input('interest_ids');

        $interest = $user->interest()->syncWithoutDetaching($interest_ids);

        $user->save();

        $msg['success'] = "Registration Completed";
        $msg['user'] = $user;
        $msg['interests'] = $user->interest()->get();
        $msg['interest'] = $interest;
        $msg['image_link'] = 'https://res.cloudinary.com/getfiledata/image/upload/w_200,c_thumb,ar_4:4,g_face/';
        $msg['image'] = $user->image;
        return response()->json($msg, 201);
        }catch (\Exception $e) {
            return response()->json(['message'=> "Opps! Something went wrong!"], 400);
        }
    }

    public function validateRequest($request)
    {
       $rules = [
        'first_name' => '|required',
        'last_name' => 'string|required',
        'phone' => 'phone:NG,US,mobile|required',
        'gender' => 'string|required',
        'country' => 'string|required',
        'dob' => 'date|required',
        'interest_ids' => 'required|array|min:5',
        'interest_ids.*' => 'required|integer',
        'username' => 'unique'
        ];
        $messages = [
            'required' => ':attribute is required',
            'phone' => ':attribute number is invalid'
        ];
        $this->validate($request, $rules);
    }
}
