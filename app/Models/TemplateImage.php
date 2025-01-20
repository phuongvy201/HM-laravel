<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateImage extends Model
{
    use HasFactory;

    protected $table = 'template_images'; // Tên bảng

    protected $fillable = [
        'template_id', // ID của template
        'url', // Đường dẫn đến hình ảnh
    ];

    // Định nghĩa mối quan hệ với model ProductTemplate
    public function template()
    {
        return $this->belongsTo(ProductTemplate::class, 'template_id');
    }
}
