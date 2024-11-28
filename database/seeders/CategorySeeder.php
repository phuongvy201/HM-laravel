<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{

    public function run()
    {
        // Lấy người dùng có role là admin
        $admin = User::where('role', 'admin')->first(); // Giả sử role "admin" có giá trị là 'admin'

        if ($admin) {
            $categories = [
                'Điện thoại & Phụ kiện' => [
                    'Điện thoại di động',
                    'Máy tính bảng',
                    'Pin dự phòng',
                    'Ốp lưng & Bao da',
                    'Cáp & Củ sạc',
                ],
                'Máy tính & Laptop' => [
                    'Laptop',
                    'PC - Máy tính bàn',
                    'Màn hình',
                    'Linh kiện máy tính',
                    'Thiết bị mạng',
                ],
                'Thiết bị điện tử' => [
                    'Tivi',
                    'Loa & Âm thanh',
                    'Máy ảnh',
                    'Tai nghe',
                    'Đồng hồ thông minh',
                ],
                'Phụ kiện thời trang' => [
                    'Đồng hồ',
                    'Kính mắt',
                    'Túi xách',
                    'Ví bóp',
                ],
                'Mỹ phẩm' => [
                    'Son môi',
                    'Kem dưỡng da',
                    'Trang sức',
                    'Nước hoa',
                    'Chấm nhỏ'
                ],
                'Thực phẩm' => [
                    'Thực phẩm chay',
                    'Thực phẩm tự nhiên',
                    'Thực phẩm chế biến',
                    'Thực phẩm dinh dưỡng',
                    'Thực phẩm chế biến sẵn'
                ]
            ];

            foreach ($categories as $parent => $children) {
                // Tạo danh mục cha
                $parentCategory = Category::create([
                    'name' => $parent,
                    'slug' => Str::slug($parent),
                    'status' => 1, // Giả sử trạng thái là 1 (hoạt động)
                    'created_by' => $admin->id, // Lấy ID của admin từ bảng users
                    'description' => fake()->sentence(),
                    'image' => 'categories/default.jpg', // Hình ảnh mặc định
                    'created_at' => now(),
                    'updated_at' => now(),
                    'parent_id' => null, // Danh mục cha không có parent_id
                ]);

                // Tạo các danh mục con
                foreach ($children as $child) {
                    Category::create([
                        'name' => $child,
                        'slug' => Str::slug($child),
                        'status' => 1, // Giả sử trạng thái là 1 (hoạt động)
                        'created_by' => $admin->id, // Lấy ID của admin từ bảng users
                        'description' => fake()->sentence(),
                        'image' => 'categories/default.jpg', // Hình ảnh mặc định
                        'created_at' => now(),
                        'updated_at' => now(),
                        'parent_id' => $parentCategory->id, // Gán parent_id là ID của danh mục cha
                    ]);
                }
            }
        } else {
            // Nếu không tìm thấy user với role là admin, có thể log hoặc xử lý lỗi
            echo "No admin user found!";
        }
    }
}
