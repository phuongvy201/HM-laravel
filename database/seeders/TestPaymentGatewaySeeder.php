<?php

namespace Database\Seeders;

use App\Models\PaymentGatewayTest;
use Illuminate\Database\Seeder;

class TestPaymentGatewaySeeder extends Seeder
{
    private $paypalCredentials = [
        [
            'client_id' => 'Aa5m5gh0HqDr7B8waX-hQ4ooOQp-eE35V-VpVKvN6Lif4T398OavmgQRUGu3n2LQXp_l9_BFS39TI7ud',
            'client_secret' => 'EJTAOJIelfgsTdokx5_VWFqBQtqm6Q21mDJ3eplrt8IAjAnp-x4c8cvgZZV3m08I1USqXd5W64tQlRPj'
        ],
        [
            'client_id' => 'ASVmsDur9fvqHvC_iUEeG6gZBz560sJEvoPeAg8RISRTmIUDdg4bUkSCEWUT5_L2XQd6E0A7lcjW0rn1',
            'client_secret' => 'EKVmLhUO8wMjDXVQCKxyh9SRfSZ4nBoFHaceKl4vN3upqx54cIW9AcF6MDOpHjls3Y4HabW5uS3UKccG'
        ],
        [
            'client_id' => 'AUUdNz56ukcOGSdXxhTSVwBtNWp6FPZqZcIpL5ayID_3QmB0rJsDRGn6A5bzT4NOTtbPxgOngjjfPrDb',
            'client_secret' => 'EEv-NR0C4tKSrL_hruDICoomo-7ysrJpxXAz4kCn9ALmfyUmeVYDJsFuEUtOL90aphUN3MRIsnANaOxh'
        ],
        [
            'client_id' => 'AWGy64AVvXoABTp9Fpz8Z5xDFuCpCogmIRYjarBzleUOHUjHz_cAcmhVDa5pykbEtAsMd7llM9f7zLBV',
            'client_secret' => 'EN6QKaDtJ1EpK8zN03XAQUUfQmM_hKDISJQKS9TwINyI1g0b3wdF0oAf6nJbN555s5hvth37naPplpOx'
        ],
        // Thêm 8 cặp credentials khác tương tự
    ];

    public function run()
    {
        foreach ($this->paypalCredentials as $index => $credentials) {
            PaymentGatewayTest::create([
                'name' => "PayPal Gateway " . ($index + 1),
                'sandbox_client_id' => $credentials['client_id'],
                'sandbox_client_secret' => $credentials['client_secret'],
                'daily_limit' => 3500,
                'current_daily_amount' => 0,
                'is_active' => true,
                'last_reset_at' => now()
            ]);
        }
    }
}
