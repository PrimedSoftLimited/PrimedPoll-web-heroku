<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\Admin;
use Carbon\Carbon;
use JWTAuthException;
use App\Http\Requests;
use App\Mail\VerifyEmail;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;


class SignInController extends Controller
{
    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }
    public function userLogin(Request $request)
    {
        // Do a validation for the input
        $this->validate($request, [

            'email' => 'required|email',
            'password' => 'required'
        ]);
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = $this->jwt->attempt($credentials, ['exp' => Carbon::now()->addDay(2)->timestamp])) {
                return response()->json(['message' => 'User not found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], 500);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent' => $e->getMessage()], 500);
        }
        $user = Auth::guard('api')->user();
        $image_link = env('CLOUDINARY_IMAGE_LINK').'/w_200,c_thumb,ar_4:4,g_face/';

        $user->first_name == null ? $process = 'incompleted' : $process = 'completed';
        if ($user->email_verified_at != null) {
            return response()->json(['data' => ['success' => true, 'user' => $user, 'process' => $process, 'image_link' => $image_link, 'token' => $token]], 200);
        } else {

  			Mail::to($user->email)->send(new VerifyEmail($user));
  			$warning = "Please your account has not been confirmed yet ". $user->email;
            $message = "A verification code has been sent to your email ". $user->email;
            return response()->json(['data' => ['error' => false, 'user_status' => 0, 'warning' => $warning, 'message' => $message]], 401);
        }
    }

    public function adminLogin(Request $request)
    {
        // Do a validation for the input--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        $this->validate($request, [

            'email' => 'required|email',
            'password' => 'required'
        ]);
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = Auth::guard('admin')->attempt($credentials)) {
                return response()->json(['message' => 'Admin not found'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], 500);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent' => $e->getMessage()], 500);
        }

        $admin = Auth::guard('admin')->user();
        $image_link = env('CLOUDINARY_IMAGE_LINK').'/w_200,c_thumb,ar_4:4,g_face/';
        return response()->json(['data' => ['success' => true, 'admin' => $admin, 'imag_link' => $image_link, 'token' => $token]], 200);
    }
}
