<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateAttributeValue extends Model
{
    use HasFactory;

    protected $table = 'template_attribute_values';

    protected $fillable = [
        'template_attribute_id',
        'value',
    ];

    public function variants()
    {
        return $this->belongsToMany(TemplateVariant::class, 'variant_template_attribute_values', 'template_attribute_value_id', 'variant_id');
    }

    public function attribute()
    {
        return $this->belongsTo(TemplateAttribute::class, 'template_attribute_id');
    }
}
