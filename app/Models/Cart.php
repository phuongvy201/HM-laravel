<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    // Tên bảng (nếu không phải dạng số nhiều tự động)
    protected $table = 'carts';

    // Các cột có thể điền giá trị hàng loạt
    protected $fillable = [
        'user_id', // Liên kết tới người dùng
        'created_at',
        'updated_at',
    ];

    // Nếu cần quan hệ tới User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
