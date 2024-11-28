<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    /**
     * Bảng liên kết với model này.
     *
     * @var string
     */
    protected $table = 'topics';

    /**
     * Khóa chính của bảng.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Các cột có thể điền thông qua model.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'created_by',
        'updated_by',
        'status',
        'created_at',
        'updated_at',
    ];
}
