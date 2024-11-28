<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        // Lấy user với role là 'customer'
        $customer = User::where('role', 'customer')->inRandomOrder()->first();

        // Lấy user với role là 'employee'
        $seller = User::where('role', 'employee')->inRandomOrder()->first();

        return [
            'customer_id' => $customer ? $customer->id : null,
            'seller_id' => $seller ? $seller->id : null,
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
