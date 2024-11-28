<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Lấy danh sách sản phẩm để sử dụng trong các order detail
        $products = Product::all();

        // Lấy tất cả các order đã có
        $orders = Order::all();

        foreach ($orders as $order) {
            // Tạo ngẫu nhiên từ 2-5 order details cho mỗi order
            $orderDetailsCount = rand(2, 5);

            for ($i = 0; $i < $orderDetailsCount; $i++) {
                // Chọn một sản phẩm ngẫu nhiên
                $product = $products->random();

                // Tạo order detail
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'attributes' => json_encode([
                        'color' => $this->randomColor(),
                        'size' => $this->randomSize(),
                    ]),
                    'quantity' => rand(1, 10),
                    'price' => $product->price,
                ]);
            }
        }
    }

    private function randomColor()
    {
        return ['White', 'Blue', 'Green', 'Black', 'Green'][array_rand(['red', 'blue', 'green', 'black', 'white'])];
    }

    private function randomSize()
    {
        return ['S', 'M', 'L', 'XL', 'XXL', '3XL'][array_rand(['S', 'M', 'L', 'XL', 'XXL', '3XL'])];
    }
}
