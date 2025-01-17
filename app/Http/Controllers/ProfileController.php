<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isFalse;
use function PHPUnit\Framework\lessThanOrEqual;

class ProfileController extends Controller
{
    use StoreFileTrait;

    public function createProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'profile_picture' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        if ($validator->fails()) {
            return ResponseFormatter::error('Validation Error',$validator->errors(),422);
        }
        $user =User::query()->where('id',Auth::id())->first();
        //store picture in project
        $fileUrl = $this->storePicture($request['profile_picture'], 'uploads');
        // Create a new profile
        $profile = Profile::query()->create([
            'user_id'=>Auth::id() ,
            'first_name'=>$request['first_name'],
            'last_name'=>$request['last_name'],
            'profile_picture'=>$fileUrl,
            'phone_number'=>$user->phone_number,
        ]);
        return ResponseFormatter::success('The Profile created successfully',$profile,201);
    }
    public function getProfile()
    {
        $profile = Profile::query()->where('user_id',Auth::id())->first();
        return ResponseFormatter::success('The Profile created successfully',$profile,201);
    }
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'phone_number' => 'nullable|regex:/^09\d{8}$/',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation Error', $validator->errors(), 422);
        }

        $profile = Profile::query()->where('user_id', Auth::id())->first();


        if ($request->filled('first_name')) {
            $profile->first_name = $request->input('first_name');
        }
        if ($request->filled('last_name')) {
            $profile->last_name = $request->input('last_name');
        }
        if ($request->filled('phone_number')) {

            if (User::query()->where('phone_number',$request['phone_number'])->exists() && $profile->phone_number != $request['phone_number']){
                return ResponseFormatter::error('Validation Error','the number  used for other user',422);
            }
            if ($profile->phone_number != $request['phone_number']){
                $profile->phone_number = $request->input('phone_number');
                $user=User::query()->where('id',Auth::id())->first();
                $user->phone_number=$request->input('phone_number');
                $user->save();
            }
            if($profile->phone_number == $request['phone_number'] ){
                $profile->phone_number = $request['phone_number'];
            }


        }

        if ($request->hasFile('profile_picture')) {
            $fileUrl = $this->updatePicture($request['profile_picture'],$profile->profile_picture);
            $profile->profile_picture = $fileUrl;
        }

        // حفظ التغييرات
        $profile->save();

        return ResponseFormatter::success('The Profile updated successfully', $profile, 200);
    }

}
