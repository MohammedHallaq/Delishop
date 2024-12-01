<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\Category::factory(),
            'name' => $this->faker->company,
            'store_picture' => $this->faker->imageUrl(640, 480, 'business'),
            'location' => $this->faker->address,
            'description' => $this->faker->paragraph,
        ];
    }
}
