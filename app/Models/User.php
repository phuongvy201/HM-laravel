<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone_number',
        'address',
        'status',
        'gender',
        'verification_code'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Helper method để check role
    public function hasRole($ability)
    {
        $token = $this->currentAccessToken();
        return $token && (
            $token->can($ability) ||
            $token->abilities === ['*']
        );
    }

    /**
     * Scope để lấy ra tất cả nhân viên
     */
    public function scopeEmployees($query)
    {
        return $query->where('role', 'employee');
    }

    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }
    public function profileShop()
    {
        return $this->hasOne(ProfileShop::class, 'owner_id');
    }
}
