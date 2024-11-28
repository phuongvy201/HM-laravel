<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    /**
     * Tên bảng trong cơ sở dữ liệu.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * Khóa chính của bảng.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Các cột có thể điền dữ liệu qua model.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'created_by',
        'updated_by',
        'status',
        'topic_id',
        'image',
        'type',
        'detail',
        'description',
        'created_at',
        'updated_at',
    ];


    // Quan hệ với bảng topics
    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Các mối quan hệ.
     */
}
