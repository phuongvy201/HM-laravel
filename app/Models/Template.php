<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $table = 'templates';  // Tên bảng

    protected $fillable = [
        'user_id',
        'template_name',
        'description',
 
        'category_id',
        'image',
    ];

    protected $casts = [
        'category_id' => 'integer',  // Chuyển đổi category_id thành kiểu số nguyên
        'value_color' => 'array'
    ];

    // Quan hệ với bảng TemplateValues
    public function templateValues()
    {
        return $this->hasMany(TemplateValue::class);
    }
}
