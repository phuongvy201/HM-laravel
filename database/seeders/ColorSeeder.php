<?php

namespace Database\Seeders;

use App\Models\Color;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run()
    {
        $colors = [
            ['name' => 'Black', 'code' => '#000000'],
            ['name' => 'White', 'code' => '#ffffff'],
            ['name' => 'Navy', 'code' => '#42495d'],
            ['name' => 'Red', 'code' => '#d2151f'],
            ['name' => 'Dark Heather', 'code' => '#38373b'],
            ['name' => 'Royal', 'code' => '#24508b'],
            ['name' => 'Light Blue', 'code' => '#a4c8e1'],
            ['name' => 'Sport Grey', 'code' => '#8b8b8b'],
            ['name' => 'Charcoal', 'code' => '#4d4d4f'],
            ['name' => 'Maroon', 'code' => '#6b2e4d'],
            ['name' => 'Light Pink', 'code' => '#e6bac7'],
            ['name' => 'Orange', 'code' => '#ff7a1d'],
            ['name' => 'Military green', 'code' => '#6d644b'],
            ['name' => 'Heliconia', 'code' => '#cd4592'],
            ['name' => 'Ash', 'code' => '#bfc0bd'],
            ['name' => 'Irish Green', 'code' => '#6aa268'],
            ['name' => 'Forest Green', 'code' => '#374641'],
            ['name' => 'Gold', 'code' => '#ebd230'],
            ['name' => 'Azalea', 'code' => '#e8fbb'],
            ['name' => 'Sapphire', 'code' => '#187cb0'],
            ['name' => 'Dark Chocolate', 'code' => '#291c19'],
            ['name' => 'Daisy', 'code' => '#e7c24d'],
            ['name' => 'Cardinal Red', 'code' => '#970a17'],
            ['name' => 'Turf Green', 'code' => '#607a53'],
            ['name' => 'Lime', 'code' => '#9fbf78']
        ];

        $products = Product::all();

        foreach ($products as $product) {
            // Mỗi sản phẩm có 3-6 màu ngẫu nhiên
            $randomColors = array_rand($colors, rand(3, 6));
            foreach ($randomColors as $index) {
                Color::create([
                    'product_id' => $product->id,
                    'color_value' => $colors[$index]['name'],
                    'color_code' => $colors[$index]['code'],
                    'image' => 'images/products/colors/default.jpg'
                ]);
            }
        }
    }
}
