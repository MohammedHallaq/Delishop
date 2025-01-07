<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{

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
        $profile = Profile::create([
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'profile_picture'=>$request->profile_picture,
            'phone_number'=>$user->phone_number,
        ]);
        return ResponseFormatter::success('The Profile created successfully',$profile,201);
    }
    public function getProfile()
    {
        $profile = Profile::query()->where('id',Auth::id())->first();
        return ResponseFormatter::success('The Profile created successfully',$profile,201);
    }
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'profile_picture' => 'mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return ResponseFormatter::error('Validation Error',$validator->errors(),422);
        }
        $profile = Profile::query()->where('id',Auth::id())->first();
        $profile->update([
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'profile_picture'=>$request->profile_picture
        ]);
        return ResponseFormatter::success('The Profile updated successfully',$profile,200);
    }
}
