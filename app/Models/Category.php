<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'parent_id',
        'description',
        'created_by',
        'updated_by',
        'status'
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parents()
    {
        return $this->belongsTo(Category::class, 'parent_id')
            ->with('parents');
    }
    public function getFullPathAttribute()
    {
        $path = [];
        $category = $this;

        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }

        return implode(' > ', $path);
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function shippingCategory()
    {
        return $this->hasOne(ShippingCategory::class);
    }

    public static function getRootCategoriesWithShipping()
    {
        return self::select('categories.*')
            ->leftJoin('shipping_categories', 'categories.id', '=', 'shipping_categories.category_id')
            ->whereNull('categories.parent_id')
            ->with(['shippingCategory'])
            ->get()
            ->map(function ($category) {
                if (!$category->shippingCategory) {
                    $category->shippingCategory = (object)[
                        'base_rate' => 0,
                        'additional_rate' => 0,
                        'category_id' => $category->id
                    ];
                }
                return $category;
            });
    }
    public function ancestors()
{
        return $this->hasMany(Category::class, 'id', 'parent_id');
    }   
        
}
