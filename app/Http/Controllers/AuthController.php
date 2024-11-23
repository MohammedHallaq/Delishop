<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|regex:/^09\d{8}$/|unique:users,phone_number',
            'password' => 'required|string|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d{5,})(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/|confirmed',
        ]);

        // إنشاء المستخدم الجديد
        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'phone_number' => $validatedData['phone_number'],
            'password' => bcrypt($validatedData['password']),
            'role_id' => 3,
        ]);
         $token = auth('api')->login($user);
        // إرجاع الاستجابة
        return $this->respondWithToken($token);
    }


    public function login(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validatedData = $request->validate([
            'phone_number' => 'required|regex:/^09\d{8}$/|exists:users,phone_number',
            'password' => 'required|string|min:6',
        ]);

        // الحصول على بيانات تسجيل الدخول (رقم الهاتف وكلمة المرور)
        $credentials = $request->only(['phone_number', 'password']);

        // محاولة تسجيل الدخول باستخدام Auth
        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // إذا تم تسجيل الدخول بنجاح، إرجاع التوكن
        return $this->respondWithToken($token);
    }



    public function me()
    {
        return response()->json(auth('api')->user());
    }


    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }


    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }


    protected function respondWithToken($token)
    {
        $user = auth('api')->user();

        return response()->json([
            'message' => 'Logged in successfully',
            'data' => [
                'token' => $token,
                'first_name' => $user->first_name ,
                'last_name' => $user->last_name,
            ],
            'status' => true,
            'code' => 200
        ]);
    }
}
