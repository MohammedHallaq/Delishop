<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pictures = ['Shoes', 'Perfumes','Other', 'Mobile Devices', 'Laptops', 'Health', 'Groceries',
                     'Food','Electrical','Drinks','Clothing', 'Beauty','Accessories'];

        foreach ($pictures as $picture)
        Category::query()->create([
            'name' =>$picture ,
            'category_picture' => 'http://127.0.0.1:8000/storage/uploads/'.$picture.'.svg'
        ]);

    }
}
