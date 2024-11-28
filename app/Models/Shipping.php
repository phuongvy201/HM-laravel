<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $fillable = [
        'order_id',
        'tracking_number',
        'shipping_method',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'country',
        'city',
        'zip_code',
        'shipping_cost',
        'status',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipping_notes',
        'internal_notes'
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'estimated_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime'
    ];

    // Relationship với Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
