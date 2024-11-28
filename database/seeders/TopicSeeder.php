<?php

namespace Database\Seeders;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{

    public function run(): void
    {
        // Lấy danh sách tất cả ID người dùng từ bảng `users`
        $userIds = User::pluck('id')->toArray();

        // Kiểm tra nếu danh sách ID không có giá trị
        if (empty($userIds)) {
            $this->command->warn('No users found. Please seed the users table first.');
            return;
        }

        // Tạo 10 topics giả
        for ($i = 1; $i <= 10; $i++) {
            $baseSlug = Str::slug('Topic ' . $i);
            $slug = $baseSlug;

            // Tìm `slug` duy nhất
            $count = 1;
            while (Topic::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            Topic::create([
                'name' => 'Topic ' . $i,
                'slug' => $slug,
                'created_by' => $userIds[array_rand($userIds)],
                'updated_by' => null,
                'status' => rand(1, 2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
