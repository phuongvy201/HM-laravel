<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy danh sách ID của user có vai trò 'employee'
        $employeeIds = User::where('role', 'employee')->pluck('id')->toArray();
        $topics = Topic::all();

        // Kiểm tra nếu không có user nào có vai trò employee
        if (empty($employeeIds)) {
            $this->command->warn('No users with role "employee" found. Please add some users first.');
            return;
        }

        // Tạo 20 bài viết giả
        for ($i = 1; $i <= 200; $i++) {
            Post::create([
                'title' => fake()->sentence(3, true),
                'topic_id' => $topics->random()->id,
                'slug' => Str::slug(fake()->sentence(3, true)),
                'created_by' => $employeeIds[array_rand($employeeIds)], // Chọn ngẫu nhiên từ danh sách employee
                'updated_by' => null, // Giữ giá trị null
                'status' => rand(0, 1), // Trạng thái ngẫu nhiên

                'image' => "images/posts/default.jpg", // Có thể thay bằng đường dẫn ảnh nếu cần
                'type' => rand(0, 1) ? 'post' : 'page', // Ngẫu nhiên giữa 'post' và 'page'
                'detail' => fake()->paragraphs(10, true),
                'description' => fake()->paragraphs(3, true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
