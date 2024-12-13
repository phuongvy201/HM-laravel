<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayTest extends Model
{
    protected $table = 'payment_gateways_test';

    protected $fillable = [
        'name',
        'sandbox_client_id',
        'sandbox_client_secret',
        'daily_limit',
        'current_daily_amount', 
        'is_active',
        'last_reset_at'
    ];

    protected $casts = [
        'last_reset_at' => 'datetime',
    ];
}
