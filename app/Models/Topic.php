<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

    /**
     * Thêm một topic mới.
     *
     * @param array $data
     * @return Topic
     */
    public static function addTopic(array $data): Topic
    {
        $userId = Auth::id(); // Lấy ID của người dùng đang đăng nhập

        $topic = new self();
        $topic->fill($data); // Điền dữ liệu vào model
        $topic->slug = Str::slug($data['name']); // Tạo slug từ tên
        $topic->created_by = $userId; // Gán người tạo
        $topic->updated_by = $userId; // Gán người cập nhật
        $topic->created_at = now(); // Thời gian tạo
        $topic->updated_at = now(); // Thời gian cập nhật
        $topic->save(); // Lưu topic mới

        return $topic; // Trả về topic vừa được thêm
    }
}
