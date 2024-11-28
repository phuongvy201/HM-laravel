<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Follow>
 */

namespace Database\Factories;

use App\Models\User;
use App\Models\ProfileShop;  // Thêm import ProfileShop
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Follow>
 */
class FollowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Lấy một user có role là 'customer' làm người theo dõi
        $follower = User::where('role', 'customer')->inRandomOrder()->first();

        // Lấy một shop hợp lệ từ bảng ProfileShop
        $followedShop = ProfileShop::inRandomOrder()->first();

        return [
            'follower_id' => $follower->id,
            'followed_shop_id' => $followedShop->id,  // Sử dụng ID từ bảng ProfileShop
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
