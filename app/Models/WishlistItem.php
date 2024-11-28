<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = ['wishlist_id', 'product_id', 'added_at'];

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }
}
