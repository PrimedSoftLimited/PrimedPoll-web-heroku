<?php

namespace App\Http\Controllers;

use App\User;
use Cloudder;
use App\Userinterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use libphonenumber\PhoneNumberType;

class UserProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function index(User $user)
    {
        $user = Auth::user();

        return response()->json(['data' => [ 'success' => true, 'user' => $user ]], 200);
    }

    public function uploadImage(Request $request)
    {
        $this->validate($request, [
            'image' => 'image|max:4000|required',
            ]);
        $user = Auth::user();

        if ($request->hasFile('image') && $request->file('image')->isValid()){

            if ($user->image != "noimage.png") {
                $oldImage = pathinfo($user->image, PATHINFO_FILENAME);
                try {
                    $delete_old_image = Cloudder::destroyImage($oldImage);
                } catch (Exception $e) {
                    $mes['error'] = "Try Again";
                    return back()->with($mes);
                }
            }

            $user = $request->file('image');
            $filename = $request->file('image')->getClientOriginalName();
            $image = $request->file('image')->getRealPath();
            $cloudder = Cloudder::upload($image);

            // list($width, $height) = getimagesize($image);
            // $image = Cloudder::show(Cloudder::getPublicId(), ["width" => $width, "height"=>$height]);

            //Request the image info from api and save to db
            $uploadResult = $cloudder->getResult();
            //Get the public id or the image name
            $file_url = $uploadResult["public_id"];
            //Get the image format from the api
            $format = $uploadResult["format"];

            $user_image = $file_url.".".$format;

            $this->saveImages($request, $user_image);

            $res['message'] = "Upload Successful!";
            $res['image_link'] = 'https://res.cloudinary.com/getfiledata/image/upload/';
            $res['image_prop'] = [
              'cropType1' => 'c_fit',
              'cropType2' => 'g_face',
              'imageStyle' => 'c_thumb',
              'heigth' => 'h_577',
              'width' =>  '433',
              'widthThumb' => 'w_200',
              'aspectRatio' => 'ar_4:4'
            ];
            $res['image'] = $user_image;
            return response()->json($res, 200);

        }
    }

    public function saveImages(Request $request, $user_image)
    {
        $user = Auth::user();

        $user->image = $user_image;
        $user->save();
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $this->validatePassword($request);

        $old_password = $request->input('old_password');
        $password = $request->input('password');

        $checker = Hash::check($old_password, $user->password);

        if($checker) {

            $user->password = Hash::make($password);
            $user->save();

            $msg['success'] = 'Password Changed Successfully';
            return response()->json($msg, 201);
        } else {
            $msg['error'] = 'Invalid Credentials';
            return response()->json($msg, 402);
        }
    }

    public function editUsername(Request $request)
    {
        $user = Auth::user();

        $this->validate($request, [
            'username' => 'required|unique:users'
        ]);

        $user->username = '@'.$request->input('username');

        $user->save();

		    $res['message'] = "Username Updated Successfully!";
        $res['user'] = $user;
        return response()->json($res, 201);
    }

    public function editProfile(Request $request)
    {
        $user = Auth::user();

        $this->validateRequest($request);

        $user->first_name = $request->input('first_name');
        $user->last_name  = $request->input('last_name');
        $user->phone      = $request->input('phone');
        $user->dob        = $request->input('dob');
        $user->country    = $request->input('country');

        $user->save();

		$res['message'] = "Account Updated Successfully!";
        $res['user'] = $user;
        return response()->json($res, 201);
    }

    public function createBio(Request $request)
    {
        $user = Auth::user();

        $this->validateBio($request);

        $user->bio = $request->input('bio');

        $user->save();

        $res['bio'] = $user->bio;
        $res['message'] = "Bio has been updated!";

        return response()->json($res, 200);
    }

    public function validateRequest(Request $request)
    {
       $rules = [
        'first_name' => 'required',
        'last_name'  => 'string|required',
        'phone'      => 'phone:NG,US,mobile|required',
        'dob'        => 'date|required',
        'country'    => 'required|string',
        'gender'     => 'required|regex:/(^([Male,Female,Others]+)?$)/u'
        ];

        $messages = [
            'required'     => ':attribute is required',
            'phone'        => ':attribute number is invalid',
            'gender.regex' => ':attribute is invalid accepted only(Male,Female,Others)',
        ];

        $this->validate($request, $rules);
    }

    public function validatePassword(Request $request)
    {
       $rules = [
        'old_password'=> 'required|string',
        'password' => 'required|min:6|different:old_password|confirmed'
        ];
        $messages = [
            'required' => ':attribute is required'
        ];
        $this->validate($request, $rules);
    }

    public function validateBio(Request $request)
    {
        $rules = [
           'bio' => 'required|string|max:200|min:5'
        ];
        $messages = [
            'required' => ':attribute is required'
        ];
        $this->validate($request, $rules);
    }
}
