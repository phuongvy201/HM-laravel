<?php

namespace App\Services;

use App\Models\PaymentGatewayTest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentTransaction;

class PaymentTestService
{
    public function findAvailableGateway($amount)
    {
        try {
            // Reset giới hạn hàng ngày lúc 00:00
            $this->resetDailyLimits();

            // Tìm cổng đầu tiên
            $primaryGateway = PaymentGatewayTest::where('is_active', true)
                ->orderBy('id') // Lấy cổng đầu tiên
                ->first();

            // Kiểm tra nếu cổng đầu tiên đủ hạn mức để thanh toán
            if ($primaryGateway && ($primaryGateway->daily_limit - $primaryGateway->current_daily_amount) >= $amount) {
                return $primaryGateway;
            }

            // Nếu không đủ hạn mức, tìm cổng khác
            $alternativeGateway = PaymentGatewayTest::where('is_active', true)
                ->whereRaw('(daily_limit - current_daily_amount) >= ?', [$amount])
                ->where('id', '>', $primaryGateway->id) // Chỉ lấy cổng sau cổng đầu tiên
                ->orderBy('id') // Lấy cổng tiếp theo
                ->first();

            // Sau khi thanh toán cổng khác, kiểm tra lại cổng đầu tiên
            if ($alternativeGateway) {
                return $alternativeGateway;
            }

            return $primaryGateway; // Nếu không có cổng khác, trả về cổng đầu tiên
        } catch (\Exception $e) {
            Log::error('Find Gateway Error: ' . $e->getMessage());
            return null;
        }
    }

    private function resetDailyLimits()
    {
        PaymentGatewayTest::whereDate('last_reset_at', '<', now()->startOfDay())
            ->update([
                'current_daily_amount' => 0,
                'daily_limit' => 3500,
                'last_reset_at' => now(),
            ]);
    }


    public function updateGatewayAmount($gatewayId, $amount)
    {
        try {
            $gateway = PaymentGatewayTest::find($gatewayId);
            if ($gateway) {
                $gateway->increment('current_daily_amount', $amount);

                // Log nếu gần đạt giới hạn
                if ($gateway->current_daily_amount >= ($gateway->daily_limit * 0.8)) {
                    Log::warning("Gateway {$gateway->id} sắp đạt giới hạn. Đã sử dụng: {$gateway->current_daily_amount}");
                }
            }
        } catch (\Exception $e) {
            Log::error('Update Gateway Amount Error: ' . $e->getMessage());
        }
    }

    public function getGatewayStatus($gatewayId = null)
    {
        try {
            if ($gatewayId) {
                $gateway = PaymentGatewayTest::find($gatewayId);
                return [
                    'id' => $gateway->id,
                    'name' => $gateway->name,
                    'available_amount' => $gateway->daily_limit - $gateway->current_daily_amount,
                    'used_amount' => $gateway->current_daily_amount,
                    'is_active' => $gateway->is_active
                ];
            }

            return PaymentGatewayTest::all()->map(function ($gateway) {
                return [
                    'id' => $gateway->id,
                    'name' => $gateway->name,
                    'available_amount' => $gateway->daily_limit - $gateway->current_daily_amount,
                    'used_amount' => $gateway->current_daily_amount,
                    'is_active' => $gateway->is_active
                ];
            });
        } catch (\Exception $e) {
            Log::error('Get Gateway Status Error: ' . $e->getMessage());
            return [];
        }
    }

    public function createTransaction($data)
    {
        try {
            DB::beginTransaction();

            // Tạo transaction
            $transaction = PaymentTransaction::create([
                'payment_gateway_test_id' => $data['gateway_id'],
                'order_id' => $data['paypal_order_id'],
                'amount' => $data['amount'],
                'currency' => 'USD',
                'status' => PaymentTransaction::STATUS_PENDING,
                'paypal_response' => $data['paypal_response'] ?? null
            ]);

            // Cập nhật số tiền đã sử dụng của gateway
            $this->updateGatewayAmount($data['gateway_id'], $data['amount']);

            DB::commit();
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create Transaction Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateTransactionStatus($orderId, $status)
    {
        try {
            $transaction = PaymentTransaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }

            $transaction->update([
                'status' => $status
            ]);

            return $transaction;
        } catch (\Exception $e) {
            Log::error('Update Transaction Status Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
