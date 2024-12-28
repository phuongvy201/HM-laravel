<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    protected $table = 'types'; // Tên bảng trong cơ sở dữ liệu

    protected $fillable = [
        'product_id',
        'type_value',
        'price',
    ];

    // Nếu cần, bạn có thể thêm các phương thức quan hệ ở đây
    // Ví dụ: public function styles() { return $this->hasMany(Style::class); }
}
