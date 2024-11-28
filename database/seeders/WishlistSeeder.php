<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WishlistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();

        // Tạo wishlist cho từng customer
        foreach ($customers as $customer) {
            Wishlist::create([
                'user_id' => $customer->id,
            ]);
        }
    }
}
