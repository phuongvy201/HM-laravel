<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\ViewedProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ViewedProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy tất cả người dùng có role 'customer'
        $customers = User::where('role', 'customer')->get();

        foreach ($customers as $customer) {
            // Lấy ngẫu nhiên từ 2 đến 6 sản phẩm cho mỗi customer
            $products = Product::inRandomOrder()->take(rand(2, 6))->get();

            foreach ($products as $product) {
                // Tạo bản ghi trong bảng viewed_products cho mỗi customer và sản phẩm
                ViewedProduct::create([
                    'user_id' => $customer->id,
                    'product_id' => $product->id,
                    'viewed_at' => now(), // Cập nhật thời gian xem
                ]);
            }
        }
    }
}
