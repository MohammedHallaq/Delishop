<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

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
            'fcm_token' => 'nullable|unique:users,fcm_token',
        ]);

        if ($validator->fails())
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        // إنشاء المستخدم الجديد
        $user = User::query()->create([
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'phone_number' => $request['phone_number'],
            'password' => bcrypt($request['password']),
            'fcm_token' => $request['fcm_token'],
            'role_id' => 3,
        ]);
        $clientRole = Role::query()->where('name','client')->first();
        $user->assignRole($clientRole);
        $permissions = $clientRole->permissions()->pluck('name')->toArray();
        $user->givePermissionTo($permissions);

        Profile::query()->create([
            'user_id'=>$user->id ,
            'first_name'=>$user->first_name,
            'last_name'=>$user->last_name,
            'profile_picture'=>null,
            'phone_number'=>$user->phone_number,
        ]);

        $data = [
        'token' => auth('api')->login($user),
        'first_name' => $user->first_name ,
        'last_name' => $user->last_name,
         'role_id' => $user->role_id,];

        return ResponseFormatter::success('Logged in successfully',$data,201);
    }


    public function login(Request $request)
    {

        // الحصول على بيانات تسجيل الدخول (رقم الهاتف وكلمة المرور)
        $credentials = $request->only(['phone_number', 'password', 'fcm_token']);

        // محاولة تسجيل الدخول باستخدام Auth
        if (!$token = auth('api')->attempt($credentials)) {
            return  ResponseFormatter::error('Unauthorized',null,401);
        }
        $user = auth('api')->user();


        $user->fcm_token = $request->fcm_token;
        $user->save(); // Save the updated token in the database    

        $data = [
            'token' =>$token,
            'fcm_token'=> $user->fcm_token,
            'first_name' => $user->first_name ,
            'last_name' => $user->last_name,
            'role_id' => $user->role_id,
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
