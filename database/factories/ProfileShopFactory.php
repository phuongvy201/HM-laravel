<?php

namespace Database\Factories;

use App\Models\ProfileShop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileShop>
 */
class ProfileShopFactory extends Factory
{
    protected $model = ProfileShop::class;

    public function definition()
    {
        return [
            'shop_name' => $this->faker->company(), // Tên shop ngẫu nhiên
            'description' => $this->faker->sentence(10), // Mô tả ngắn
            'logo_url' => $this->faker->imageUrl(200, 200, 'business', true, 'logo'), // URL logo giả
            'banner_url' => $this->faker->imageUrl(800, 200, 'business', true, 'banner'), // URL banner giả
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
