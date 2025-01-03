<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|regex:/^09\d{8}$/|unique:users,phone_number',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[#?!@$%^&*-])[A-Za-z\d#$?!@$%^&*-]{8,}$/|confirmed',
        ]);

        if ($validator->fails())
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        $validator=$validator->validated();
        // إنشاء المستخدم الجديد
        $user = User::create([
            'first_name' => $validator['first_name'],
            'last_name' => $validator['last_name'],
            'phone_number' => $validator['phone_number'],
            'password' => bcrypt($validator['password']),
            'role_id' => 3,
        ]);

        $data = [
        'token' => auth('api')->login($user),
        'first_name' => $user->first_name ,
        'last_name' => $user->last_name];
        return ResponseFormatter::success('Logged in successfully',$data,201);
    }


    public function login(Request $request)
    {

        // الحصول على بيانات تسجيل الدخول (رقم الهاتف وكلمة المرور)
        $credentials = $request->only(['phone_number', 'password']);

        // محاولة تسجيل الدخول باستخدام Auth
        if (!$token = auth('api')->attempt($credentials)) {
            return  ResponseFormatter::error('Unauthorized',null,401);
        }
        $user = auth('api')->user();
        $data = [
            'token' =>$token,
            'first_name' => $user->first_name ,
            'last_name' => $user->last_name
        ];

        // إذا تم تسجيل الدخول بنجاح، إرجاع التوكن
       return ResponseFormatter::success('Logged in successfully',$data,200);
    }



    public function me()
    {
        return response()->json(auth('api')->user());
    }


    public function logout()
    {
        auth()->logout();

        return ResponseFormatter::success('Logged out successfully',null,200);
    }


  /*  public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }*/



}
