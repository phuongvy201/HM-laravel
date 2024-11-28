<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Size extends Model
{
    protected $fillable = [
        'product_id',
        'size_value',
        'price'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2'
    ];

    /**
     * Get the product that owns the size
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', '.') . ' đ';
    }

    /**
     * Get price difference from base product price
     */
    public function getPriceDifferenceAttribute()
    {
        $difference = $this->price - $this->product->price;
        if ($difference > 0) {
            return '+' . number_format($difference, 0, ',', '.') . ' đ';
        }
        return number_format($difference, 0, ',', '.') . ' đ';
    }

    /**
     * Scope a query to order by size value
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('size_value');
    }

    /**
     * Scope a query to get sizes with stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
}
