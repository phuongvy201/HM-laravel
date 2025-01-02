<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateAttribute extends Model
{
    use HasFactory;

    protected $table = 'template_attributes';

    protected $fillable = [
        'product_template_id',
        'name'
    ];

    public function productTemplate()
    {
        return $this->belongsTo(ProductTemplate::class, 'product_template_id');
    }

    public function templateAttributeValues()
    {
        return $this->hasMany(TemplateAttributeValue::class, 'template_attribute_id');
    }
}
