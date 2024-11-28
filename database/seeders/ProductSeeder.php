<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $categories = Category::whereNotNull('parent_id')->get();
        $sellers = User::where('role', 'employee')->get();

        // Tạo 100 sản phẩm
        for ($i = 1; $i <= 3000; $i++) {
            $name = fake()->unique()->words(3, true);
            Product::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'seller_id' => $sellers->random()->id,
                'category_id' => $categories->random()->id,
                'description' => fake()->paragraphs(3, true),
                'price' => fake()->numberBetween(10, 200),
                'image' => 'images/products/default.jpg',
                'status' => fake()->randomElement([0, 1]),
                'stock' => fake()->numberBetween(0, 1000),
                'updated_by' => $sellers->random()->id
            ]);
        }
    }
}
