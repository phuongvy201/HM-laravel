<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin accounts
        User::factory()->count(3)->create([
            'role' => 'admin',
            'status' => 1
        ]);

        // Create employee accounts
        User::factory()->count(33)->create([
            'role' => 'employee',
            'status' => 1
        ]);

        // Create customer accounts 
        User::factory()->count(66)->create([
            'role' => 'customer',
            'status' => 1
        ]);
    }
}
