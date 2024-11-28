<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewedProduct extends Model
{
    use HasFactory;

    protected $table = 'viewed_products';  // Đặt tên bảng nếu khác tên mặc định

    protected $fillable = [
        'user_id',
        'product_id',
        'viewed_at',
        'created_at',
        'updated_at',
    ];

    // Quan hệ với bảng users
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Quan hệ với bảng products
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
