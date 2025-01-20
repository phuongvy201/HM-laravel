<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_images'; // Tên bảng

    protected $fillable = [
        'product_id', // Khóa ngoại liên kết với bảng products
        'image_url',  // Trường lưu trữ URL hình ảnh
    ];

    // Định nghĩa mối quan hệ với model Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
