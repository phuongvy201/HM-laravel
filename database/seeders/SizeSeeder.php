<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Size;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $sizes = ["S", "M", "L", "XL", "2XL", "3XL", "4XL", "5XL"];
        $products = Product::all();

        foreach ($products as $product) {
            // Mỗi sản phẩm có 3-6 màu ngẫu nhiên
            $randomSizes = array_rand($sizes, rand(3, 6));
            foreach ($randomSizes as $index) {
                Size::create([
                    'product_id' => $product->id, // Chỉ định product_id của sản phẩm tương ứng
                    'size_value' => $sizes[$index], // Giá trị kích thước
                    'price' => fake()->numberBetween(20, 200), // Bạn có thể thay đổi giá trị này theo yêu cầu
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
