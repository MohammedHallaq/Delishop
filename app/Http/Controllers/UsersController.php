<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|regex:/^09\d{8}$/|unique:users,phone_number',
            'password' => 'required|string|min:8',
            'role_id' => 'required|integer|exists:roles,id',
        ]);
        if ($validator->fails()){
            return ResponseFormatter::error('Validation error', $validator->errors(),422);
        }

        $user = User::query()->create([
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'phone_number' => $request['phone_number'],
            'password' => bcrypt($request['password']),
            'role_id' => $request['role_id'],
        ]);
        $this->givePermissions($user, $request['role_id']);
        $user->token ="";
        return ResponseFormatter::success('User created',$user, 201);

    }
    public function updateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|regex:/^09\d{8}$/|unique:users,phone_number',
            'password' => 'nullable|string|min:8',
            'role_id' => 'nullable|integer|exists:roles,id',
        ]);
        if ($validator->fails()){
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }
        $user = User::query()->find($request['id']);
        if (!$user){
            return ResponseFormatter::error('User not found',null, 404);
        }
        if ($request->filled('first_name')){
            $user->first_name = $request['first_name'];
        }
        if ($request->filled('last_name')){
            $user->last_name = $request['last_name'];
        }
        if ($request->filled('phone_number')){
            $user->phone_number = $request['phone_number'];
        }
        if ($request->filled('password')){
            $user->password = bcrypt($request['password']);
        }
         if ($request->filled('role_id')){
             $user->role_id = $request['role_id'];
         }
         $user->save();
         $this->givePermissions($request['role_id'],$user);
        return ResponseFormatter::success('User created',$user, 201);

    }

    public function deleteUser($user_id)
    {
        $user = User::query()->find($user_id);
        if (!$user)
            return ResponseFormatter::error('User not found',null, 404);
        $user->delete();
        return ResponseFormatter::success('User deleted successfully',null, 200);
    }
    public function getUser($user_id)
    {
        $user = User::query()->find($user_id);
        if (!$user)
            return ResponseFormatter::error('User not found',null, 404);

        return ResponseFormatter::success('User retrieved successfully',$user, 200);
    }
    public function getUsers()
    {
        return ResponseFormatter::success('Users retrieved successfully',User::all(), 200);
    }

    public function searchUserByPhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|regex:/^09\d{8}$/',
        ]);
        if ($validator->fails())
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);

        $user = User::query()->where('phone_number',$request['phone_number'])->first();
        if (!$user)
            return ResponseFormatter::error('User not found',null, 404);

        return ResponseFormatter::success('User retrieved successfully',$user, 200);
    }

    public function givePermissions($user,$role_id)
    {
        if ($role_id== 3) {
            $clientRole = Role::query()->where('name','client')->first();
            $user->assignRole($clientRole);
            $permissions = $clientRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }
        if ($role_id== 2) {
            $adminRole = Role::query()->where('name','admin')->first();
            $user->assignRole($adminRole);
            $permissions = $adminRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }
        if ( $role_id== 1) {
            $superAdminRole = Role::query()->where('name','superAdmin')->first();
            $user->assignRole($superAdminRole);
            $permissions = $superAdminRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }

    }
}
