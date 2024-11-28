<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    // Tên bảng (nếu Laravel không tự nhận dạng đúng)
    protected $table = 'cart_items';

    // Các cột có thể điền giá trị hàng loạt
    protected $fillable = [
        'cart_id',        // Liên kết tới giỏ hàng
        'product_id',     // Liên kết tới sản phẩm
        'quantity',       // Số lượng sản phẩm
        'attributes',     // Thuộc tính (JSON)
        'created_at',
        'updated_at',
    ];

    // Nếu cột `attributes` là JSON
    protected $casts = [
        'attributes' => 'array',
    ];

    // Quan hệ với Cart
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    // Quan hệ với Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
