<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <=  5; $i++) {
            User::create([
                'role_id' => 2,
                'first_name' => 'User ' . $i,
                'last_name' => 'User ' . $i,
                'phone_number' => '093675777' . $i,
                'password' => Hash::make('Aa@1234' . $i),
            ]);
        }
    }
}
