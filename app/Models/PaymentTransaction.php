<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{

    protected $fillable = [
        'payment_gateway_test_id',
        'order_id',
        'amount',
        'currency',
        'tip_amount',
        'handling_fee',
        'status',
        'paypal_response'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paypal_response' => 'array'
    ];

    // Quan hệ với PaymentGatewayTest
    public function gateway()
    {
        return $this->belongsTo(PaymentGatewayTest::class, 'payment_gateway_test_id');
    }

    // Các trạng thái giao dịch
    const STATUS_PENDING = 'PENDING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_FAILED = 'FAILED';

    // Helper methods
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    // Scope queries
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeByGateway($query, $gatewayId)
    {
        return $query->where('payment_gateway_test_id', $gatewayId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }
}
