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
        $pictures = ['Shoes.svg', 'Perfumes.svg','Other.svg', 'Mobile Devices.svg', 'Laptops.svg', 'Health.svg', 'Groceries.svg',
                     'Food.svg','Electrical.svg','Drinks.svg','Clothing.svg', 'Beauty.svg','Accessories.svg'];

        foreach ($pictures as $picture)
        Category::query()->create([
            'name' =>$picture ,
            'category_picture' => 'http://127.0.0.1:8000/storage/uploads/'.$picture
        ]);

    }
}
