<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentTestService;
use App\Models\PaymentGatewayTest;

class ResetPaymentLimits extends Command
{
    protected $signature = 'payment:reset-limits';
    protected $description = 'Reset daily limits for payment gateways';

    public function handle()
    {
        try {
            $gateways = PaymentGatewayTest::where(function ($query) {
                $query->where('last_reset_at', '<', now()->startOfDay())
                    ->orWhereNull('last_reset_at');
            })->get();

            foreach ($gateways as $gateway) {
                $gateway->update([
                    'current_daily_amount' => 0,
                    'last_reset_at' => now(),
                    'is_active' => true
                ]);
            }

            $this->info('Successfully reset limits for ' . $gateways->count() . ' gateways');
        } catch (\Exception $e) {
            $this->error('Error resetting limits: ' . $e->getMessage());
        }
    }
}
