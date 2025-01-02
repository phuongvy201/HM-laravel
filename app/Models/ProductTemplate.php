<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTemplate extends Model
{
    use HasFactory;

    protected $table = 'product_templates';

    protected $fillable = [
        'name',
        'category_id',
        'image',
        'user_id',
        'description',
        'base_price',
    ];

    public function attributes()
    {
        return $this->hasMany(TemplateAttribute::class, 'product_template_id');
    }

    public function variants()
    {
        return $this->hasMany(TemplateVariant::class, 'template_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
