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
        if ($validator->fails())
            return ResponseFormatter::error('Validation error', $validator->errors(),422);

        $user = User::query()->create([
            'first_name' => $validator['first_name'],
            'last_name' => $validator['last_name'],
            'phone_number' => $validator['phone_number'],
            'password' => bcrypt($validator['password']),
            'role_id' => $validator['role_id'],
        ]);

        if ($validator['role_id']== 3) {
            $clientRole = Role::query()->where('name','client')->first();
            $user->assignRole($clientRole);
            $permissions = $clientRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }
        if ($validator['role_id']== 2) {
            $adminRole = Role::query()->where('name','admin')->first();
            $user->assignRole($adminRole);
            $permissions = $adminRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }
        if ($validator['role_id']== 1) {
            $superAdminRole = Role::query()->where('name','superAdmin')->first();
            $user->assignRole($superAdminRole);
            $permissions = $superAdminRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }



        return ResponseFormatter::success('User created',$user, 201);

    }
    public function updateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|regex:/^09\d{8}$/|unique:users,phone_number',
            'password' => 'required|string|min:8',
            'role_id' => 'required|integer|exists:roles,id',
        ]);
        if ($validator->fails())
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        $user = User::query()->find($validator['id']);
        if (!$user)
            return ResponseFormatter::error('User not found',null, 404);

            $user->first_name = $validator['first_name'];
            $user->last_name = $validator['last_name'];
            $user->phone_number = $validator['phone_number'];
            $user->password = bcrypt($validator['password']);
            $user->role_id = $validator['role_id'];
            $user->save();


        if ($user->role_id == 3) {
            $clientRole = Role::query()->where('name','client')->first();
            $user->assignRole($clientRole);
            $permissions = $clientRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }
        if ($user->role_id == 2) {
            $adminRole = Role::query()->where('name','admin')->first();
            $user->assignRole($adminRole);
            $permissions = $adminRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }
        if ($user->role_id == 1) {
            $superAdminRole = Role::query()->where('name','superAdmin')->first();
            $user->assignRole($superAdminRole);
            $permissions = $superAdminRole->permissions()->pluck('name')->toArray();
            $user->givePermissionTo($permissions);
        }
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

        $user = User::query()->where('phone_number',$validator['phone_number'])->first();
        if (!$user)
            return ResponseFormatter::error('User not found',null, 404);

        return ResponseFormatter::success('User retrieved successfully',$user, 200);
    }
    private function appendRoleAndPermissions($user)
    {

        $roles = $user->roles->pluck('name')->toArray();
        $permissions = $user->permissions->pluck('name')->toArray();

        // وضع الأدوار والصلاحيات في المستخدم
        $user->role = $roles;
        $user->permission = $permissions;

        // إزالة الحقول الزائدة
        unset($user['roles']);
        unset($user['permissions']);

        return $user;
    }
}
