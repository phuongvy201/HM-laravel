<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;

    protected $table = 'follow';

    protected $fillable = [
        'follower_id',
        'followed_shop_id',
        'follow_date',
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function followedShop()
    {
        return $this->belongsTo(ProfileShop::class, 'followed_shop_id');
    }
}
