<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'order_id',
        'attributes',
        'quantity',
        'price',
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    // Quan hệ với Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Quan hệ với Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function seller()
    {
        return $this->hasOneThrough(
            User::class,  // Model của Seller
            Product::class, // Model của Product
            'id',           // Khóa chính của bảng Product
            'id',           // Khóa chính của bảng Seller
            'product_id',   // Khóa ngoại ở OrderDetail trỏ đến Product
            'seller_id'     // Khóa ngoại ở Product trỏ đến Seller
        );
    }
}
