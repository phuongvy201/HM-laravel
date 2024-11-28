<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Color extends Model
{
    protected $fillable = [
        'product_id',
        'color_value',
        'color_code',
        'image'
    ];

    /**
     * Get the product that owns the color
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get color with image url
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }

    /**
     * Get formatted color info
     */
    public function getFormattedColorAttribute()
    {
        return "{$this->color_value} ({$this->color_code})";
    }
}
