<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateVariant extends Model
{
    use HasFactory;

    protected $table = 'template_variants';

    protected $fillable = [
        'template_id',
        'sku',
        'price',
        'image',
        'quantity',
    ];

    public function template()
    {
        return $this->belongsTo(ProductTemplate::class, 'template_id');
    }

    public function attributeValues()
    {
        return $this->belongsToMany(TemplateAttributeValue::class, 'variant_template_attribute_values', 'variant_id', 'template_attribute_value_id');
    }
}
