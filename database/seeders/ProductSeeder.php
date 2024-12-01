<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <=  5; $i++) {
            for ($j = 1; $j <= 5; $j++) {
                Product::create([
                    'store_id' => $i,
                    'name' => 'product ' . $j,
                    'product_picture' =>  'http://127.0.0.1:8000/storage/uploads/'.$i.'.jpg',
                    'price' => '100' . $j,
                    'discount' => '0',
                    'description' => 'Description ' . $j,
                    'quantity' => '100'.$j,
                ]);
            }

        }
    }
}
