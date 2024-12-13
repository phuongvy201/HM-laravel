<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    // Tên bảng (nếu không phải dạng số nhiều tự động)
    protected $table = 'carts';
}
