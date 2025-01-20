<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductSale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        'status',
        'updated_by',
        'stock',
        'template_id'
    ];

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

    public function sale()
    {
        return $this->hasOne(ProductSale::class);
    }
    public function profileShop()
    {
        return $this->hasOne(ProfileShop::class, 'owner_id', 'seller_id');
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
    public function template()
    {
        return $this->belongsTo(ProductTemplate::class, 'template_id');
    }

    /**
     * Xóa sản phẩm và các bản ghi sale liên quan
     * @return bool
     */
    public function safeDelete()
    {
        try {
            DB::beginTransaction();

            // Xóa các bản ghi sale liên quan
            $this->sale()->delete();

            // Xóa sản phẩm
            $deleted = $this->delete();

            DB::commit();
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }
    public function productImages()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }
}
