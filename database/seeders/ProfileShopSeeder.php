<?php

namespace Database\Seeders;

use App\Models\ProfileShop;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileShopSeeder extends Seeder
{
    public function run()
    {
        // Lấy danh sách user có role là 'employee'
        $employeeUsers = User::where('role', 'employee')->get();

        // Duyệt qua từng employee và tạo profile_shop
        foreach ($employeeUsers as $employee) {
            ProfileShop::factory()->create([
                'owner_id' => $employee->id, // Gắn owner_id theo user id của employee
            ]);
        }
    }
}
