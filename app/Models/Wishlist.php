<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = ['user_id'];

    public function items()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getWishlistByUser($userId)
    {
        return self::with(['items.product' => function($query) {
            $query->select('id', 'name', 'price', 'thumbnail', 'slug');
        }])
        ->where('user_id', $userId)
        ->first();
    }

    public function getItemsCountAttribute()
    {
        return $this->items()->count();
    }
}
