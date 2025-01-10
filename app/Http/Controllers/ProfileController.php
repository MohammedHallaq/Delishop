<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use StoreFileTrait;

    public function createProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'profile_picture' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return ResponseFormatter::error('Validation Error',$validator->errors(),422);
        }
        $user =User::query()->where('id',Auth::id())->first();
        //store picture in project
        $fileUrl = $this->store($request['profile_picture'], 'uploads');
        // Create a new profile
        $profile = Profile::create([
            'user_id'=>Auth::id() ,
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
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
            $profile->phone_number = $request->input('phone_number');
        }

        if ($request->hasFile('profile_picture')) {

            if ($profile->profile_picture) {
                $oldFilePath = str_replace(asset('storage/'), '', $profile->profile_picture);
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
            }

            // رفع الصورة الجديدة
            $fileUrl = $this->store($request->file('profile_picture'), 'uploads');
            $profile->profile_picture = $fileUrl;
        }

        // حفظ التغييرات
        $profile->save();

        return ResponseFormatter::success('The Profile updated successfully', $profile, 200);
    }

}
