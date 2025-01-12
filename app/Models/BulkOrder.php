<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'products',
        'name',
        'email',
        'company',
        'phone',
        'file_path',
    ];
}
