<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileShop extends Model
{
    use HasFactory;

    protected $table = 'profile_shop';

    protected $fillable = [
        'shop_name',
        'owner_id',
        'description',
        'logo_url',
        'banner_url',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'followed_shop_id');
    }

    public function products()
    {
        // Sửa lại relationship
        return $this->hasMany(Product::class, 'seller_id', 'owner_id');
    }
}
