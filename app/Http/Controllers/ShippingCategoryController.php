<?php

namespace App\Http\Controllers;

use App\Models\ShippingCategory;
use App\Models\Category;
use Illuminate\Http\Request;

class ShippingCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'base_rate' => 'required|numeric|min:0',
            'additional_rate' => 'required|numeric|min:0'
        ]);

        try {
            // Kiểm tra xem đã tồn tại shipping category cho category này chưa
            $existingShipping = ShippingCategory::where('category_id', $validated['category_id'])->first();

            if ($existingShipping) {
                // Nếu đã tồn tại thì cập nhật
                $existingShipping->update($validated);
                $message = 'Đã cập nhật giá vận chuyển thành công';
                $shippingCategory = $existingShipping;
            } else {
                // Nếu chưa tồn tại thì tạo mới
                $shippingCategory = ShippingCategory::create($validated);
                $message = 'Đã thêm giá vận chuyển thành công';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $shippingCategory
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý giá vận chuyển',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   

    public function getRootCategories()
    {
        try {
            $categories = Category::getRootCategoriesWithShipping();
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
