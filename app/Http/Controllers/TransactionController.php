<?php

namespace Database\Seeders;

use App\Models\PaymentGatewayTest;
use Illuminate\Database\Seeder;

class TestPaymentGatewaySeeder extends Seeder
{
    public function run()
    {
        $testGateways = [
            [
                'name' => 'Test Gateway 1',
                'sandbox_client_id' => 'your_sandbox_client_id_1',
                'sandbox_client_secret' => 'your_sandbox_client_secret_1',
                'daily_limit' => 2000,
                'last_reset_at' => now()
            ],
            [
                'name' => 'Test Gateway 2',
                'sandbox_client_id' => 'your_sandbox_client_id_2',
                'sandbox_client_secret' => 'your_sandbox_client_secret_2',
                'daily_limit' => 2000,
                'last_reset_at' => now()
            ]
        ];

        foreach ($testGateways as $gateway) {
            PaymentGatewayTest::create($gateway);
        }
    }
}
