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
            // Tìm gateway phù hợp với số tiền giao dịch
            $gateway = PaymentGatewayTest::where('is_active', true)
                ->where(function ($query) use ($amount) {
                    $query->whereRaw('(daily_limit - current_daily_amount) >= ?', [$amount])
                        ->orWhere(function ($q) {
                            $q->whereDate('last_reset_at', '<', now()->startOfDay());
                        });
                })
                ->orderBy('current_daily_amount') // Ưu tiên gateway có số dư nhiều nhất
                ->first();

            // Reset số tiền nếu sang ngày mới
            if ($gateway && $gateway->last_reset_at < now()->startOfDay()) {
                $gateway->update([
                    'current_daily_amount' => 0,
                    'last_reset_at' => now()
                ]);
            }

            return $gateway;
        } catch (\Exception $e) {
            Log::error('Find Gateway Error: ' . $e->getMessage());
            return null;
        }
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
