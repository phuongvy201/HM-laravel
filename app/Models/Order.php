<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */

    use HasFactory;

    protected $fillable = [
        'customer_id',
        'seller_id',
        'total_amount',
        'status',
    ];

    // Quan hệ với User
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }


    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    public function shipping()
    {
        return $this->hasOne(Shipping::class);
    }
}
