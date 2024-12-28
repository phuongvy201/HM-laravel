<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductSale;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    // Định nghĩa tên bảng trong cơ sở dữ liệu
    protected $table = 'products';

    // Các trường có thể gán giá trị (mass assignable)
    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'image',
        'status',
        'updated_by',
        'stock'
    ];
    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }
    public function discounts()
    {
        return $this->hasMany(ProductSale::class, 'product_id', 'id')
            ->orderBy('created_at', 'desc');
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
    public function sizes()
    {
        return $this->hasMany(Size::class);
    }
    public function colors()
    {
        return $this->hasMany(Color::class);
    }
    public function sale()
    {
        return $this->hasOne(ProductSale::class);
    }
    public function profileShop()
    {
        return $this->hasOne(ProfileShop::class, 'owner_id', 'seller_id');
    }
    public function types()
    {
        return $this->hasMany(Type::class);
    }
    public static function searchBySeller($id = null, $name = null, $createdFrom = null, $createdTo = null)
    {
        $query = self::query();
        $query->where('seller_id', Auth::id());
        if ($id !== null) {
            $query->where('id', $id);
        }
        if ($name !== null) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if ($createdFrom !== null && $createdTo !== null) {
            $query->whereBetween('created_at', [$createdFrom, $createdTo]);
        }
        return $query->get();
    }
}
