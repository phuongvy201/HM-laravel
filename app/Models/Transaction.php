<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Tên bảng trong database.
     */
    protected $table = 'transactions';

    /**
     * Khóa chính của bảng.
     */
    protected $primaryKey = 'id';

    /**
     * Trường khóa chính có kiểu dữ liệu không tự động tăng.
     */
    public $incrementing = true;

    /**
     * Kiểu dữ liệu của khóa chính.
     */
    protected $keyType = 'unsignedBigInteger';

    /**
     * Các cột có thể điền giá trị (mass assignable).
     */
    protected $fillable = [
        'order_id',
        'transaction_id',
        'amount',
        'payment_method',
        'status',
        'response_data',
        'bank_code',
        'card_type',
        'created_at',
        'updated_at',
    ];

    /**
     * Tự động quản lý timestamps.
     */
    public $timestamps = true;

    /**
     * Định nghĩa các cột datetime.
     */
    protected $dates = ['created_at', 'updated_at'];
}
