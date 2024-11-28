<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy danh sách products và admin users
        $products = Product::all();
        $adminUsers = User::where('role', 'employee')->get();

        // Danh sách tên các đợt giảm giá
        $saleNames = [
            'Flash Sale',
            'Summer Promotion',
            'Weekend Discount',
            'Special Sale',
            'Monthly Promotion',
            'Shocking Discount',
            'Exclusive Offer'
        ];

        // Tạo ngẫu nhiên các đợt giảm giá cho 30% số sản phẩm
        $selectedProducts = $products->random($products->count() * 0.3);

        foreach ($selectedProducts as $product) {
            $dateBegin = fake()->dateTimeBetween('-1 month', '+1 month');
            $dateEnd = fake()->dateTimeBetween($dateBegin, '+3 months');

            ProductSale::create([
                'product_id' => $product->id,
                'discount_value' => fake()->randomFloat(2, 5, 50), // Giảm giá từ 5% đến 50%
                'discount_name' => fake()->randomElement($saleNames),
                'date_begin' => $dateBegin,
                'date_end' => $dateEnd,
                'status' => 1,
                'created_by' => $adminUsers->random()->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
