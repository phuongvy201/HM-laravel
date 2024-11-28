<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WishlistItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy tất cả wishlists
        $wishlists = Wishlist::all();

        // Lấy danh sách product_id từ bảng products
        $products = Product::pluck('id')->toArray();

        // Tạo wishlist items cho từng wishlist
        foreach ($wishlists as $wishlist) {
            // Tạo số lượng mục ngẫu nhiên cho mỗi wishlist (vd: 1 đến 5 mục)
            $itemsCount = rand(1, 5);

            for ($i = 0; $i < $itemsCount; $i++) {
                WishlistItem::create([
                    'wishlist_id' => $wishlist->id,
                    'product_id'  => $products[array_rand($products)], // Chọn ngẫu nhiên product_id
                    'added_at'    => now(),
                ]);
            }
        }
    }
}
