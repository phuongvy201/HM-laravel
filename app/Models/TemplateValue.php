<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateValue extends Model
{
    use HasFactory;

    protected $table = 'template_values';  // Tên bảng

    protected $fillable = [
        'template_id',
        'name',
        'value_color',
        'value',
        'additional_price',
        'image_url',
    ];

    // Quan hệ với bảng Templates
    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
