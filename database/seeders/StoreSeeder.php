<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <=  5; $i++) {

            Store::create([
                'user_id' => $i,
                'category_id' => $i,
                'name' => 'Store ' . $i,
                'store_picture' => 'http://127.0.0.1:8000/storage/uploads/'.$i.'.jpg',
               'location' => 'location ' . $i,
               'description' => 'Description ' . $i,
            ]);
        }
    }
}
