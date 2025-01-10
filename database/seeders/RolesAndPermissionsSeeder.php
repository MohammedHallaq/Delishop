<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Roles
        $superAdminRole = Role::create(['name' => 'superAdmin']);
        $adminRole = Role::create(['name' => 'admin']);
        $clientRole = Role::create(['name' => 'client']);



        // Define permissions
        $permissions = [
            // Categories
            'category.create',
            'category.update',
            'category.delete',
            'category.get',

            // Stores
            'store.create',
            'store.update',
            'store.delete',
            'store.getByCategory',
            'store.search',
            'store.getByIds',
            'store.get',

            // Products
            'product.create',
            'product.update',
            'product.delete',
            'product.getByStore',
            'product.get',
            'product.search',

            // Favorites
            'favorite.add',
            'favorite.remove',
            'favorite.get',

            // Store Ratings
            'storeRating.add',
            'storeRating.update',
            'storeRating.delete',
            'storeRating.get',
            'storeRating.getMyUser',
            'storeRating.getValue',

            // Product Ratings
            'productRating.add',
            'productRating.update',
            'productRating.delete',
            'productRating.get',
            'productRating.getMyUser',
            'productRating.getValue',

            // Locations
            'location.add',
            'location.get',
            'location.getLastUsed',
            'location.delete',

            // Orders
            'order.getMyStore',
            'order.add',
            'order.remove',
            'order.updateStatus',
            'order.create',
            'order.get',

            // Wallet
            'wallet.deposit',
            'wallet.getMyBalance',

            // Profile
            'profile.create',
            'profile.update',
            'profile.get',

            // Users
            'user.create',
            'user.update',
            'user.delete',
            'user.gets',
            'user.get',
            'user.searchByPhoneNumber',
        ];
        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName,'web');
        }


        // Assign permissions to roles
        // توزيع الصلاحيات
        $adminPermissions = [
            'category.create', 'category.update', 'category.delete', 'category.get',
            'store.create', 'store.update', 'store.delete', 'store.get', 'store.search', 'store.getByIds', 'store.getByCategory',
            'product.create', 'product.update', 'product.delete', 'product.get', 'product.search', 'product.getByStore',
            'storeRating.get', 'storeRating.getValue',
            'productRating.get', 'productRating.getValue',
            'order.getMyStore', 'order.updateStatus',
            'wallet.deposit',
        ];

        $clientPermissions = [
            'category.get',
            'store.get', 'store.search', 'store.getByIds', 'store.getByCategory',
            'product.get', 'product.search', 'product.getByStore',
            'favorite.add', 'favorite.remove', 'favorite.get',
            'storeRating.add', 'storeRating.update', 'storeRating.delete', 'storeRating.get', 'storeRating.getMyUser', 'storeRating.getValue',
            'productRating.add', 'productRating.update', 'productRating.delete', 'productRating.get', 'productRating.getMyUser', 'productRating.getValue',
            'location.add', 'location.getLastUsed', 'location.delete', 'location.get',
            'order.add', 'order.remove', 'order.updateStatus', 'order.get', 'order.create',
            'wallet.getMyBalance',
            'profile.update', 'profile.create', 'profile.get',
        ];

        $superAdminRole->syncPermissions($permissions);
        $adminRole->givePermissionTo($adminPermissions);

        $clientRole->givePermissionTo($clientPermissions);


        //----------------//


        // create users and assign roles
        $superAdminUser = User::query()->create([
            'role_id' => 1,
            'first_name' => 'Mohammad',
            'last_name' => 'Al-Hallaq',
            'phone_number' => '0936757771',
            'password' => Hash::make('Aa@54321'),
        ]);
        Profile::query()->create([
            'user_id'=>$superAdminUser ,
            'first_name'=>$superAdminUser->first_name,
            'last_name'=>$superAdminUser->last_name,
            'profile_picture'=>null,
            'phone_number'=>$superAdminUser->phone_number,
        ]);
        $superAdminUser->assignRole($superAdminRole);

        // Assign permissions associated with the role to the user
        $permissions = $superAdminRole->permissions()->pluck('name')->toArray();
        $superAdminUser->givePermissionTo($permissions);

        for ( $x = 1; $x <= 9; $x++) {
        $adminUser = User::query()->create([
            'role_id' => 2,
            'first_name' => 'adminUser ' . $x,
            'last_name' => 'User ' . $x,
            'phone_number' => '093675776' . $x,
            'password' => Hash::make('Aa@1234' . $x),
        ]);
            Profile::query()->create([
                'user_id'=>$adminUser->id ,
                'first_name'=>$adminUser->first_name,
                'last_name'=>$adminUser->last_name,
                'profile_picture'=>null,
                'phone_number'=>$adminUser->phone_number,
            ]);
        $adminUser->assignRole($adminRole);

        // Assign permissions associated with the role to the user
        $permissions = $adminRole->permissions()->pluck('name')->toArray();
        $adminUser->givePermissionTo($permissions);
    }
        for ( $x = 1; $x <= 9; $x++) {
            $clientUser = User::query()->create([
                'role_id' => 3,
                'first_name' => 'clientUser ' . $x,
                'last_name' => 'User ' . $x,
                'phone_number' => '093675775' . $x,
                'password' => Hash::make('Aa@1234' . $x),
            ]);
            Profile::query()->create([
                'user_id'=>$clientUser ,
                'first_name'=>$clientUser->first_name,
                'last_name'=>$clientUser->last_name,
                'profile_picture'=>null,
                'phone_number'=>$clientUser->phone_number,
            ]);
            $clientUser->assignRole($clientRole);

            // Assign permissions associated with the role to the user
            $permissions = $clientRole->permissions()->pluck('name')->toArray();
            $clientUser->givePermissionTo($permissions);
        }



    }
}
