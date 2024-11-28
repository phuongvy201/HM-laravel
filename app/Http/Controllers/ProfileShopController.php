<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\Product;
use App\Models\ProfileShop;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileShopController extends Controller
{
    public function getShopInfo($sellerId)
    {
        $shop = ProfileShop::where('owner_id', $sellerId)
            ->with('owner')
            ->withCount(['products' => function ($query) {
                $query->where('status', 1); // Chỉ đếm sản phẩm đang hoạt động
            }])
            ->withCount('followers')
            ->first();

        if (!$shop) {
            return response()->json([
                'message' => 'Không tìm thấy thông tin shop'
            ], 404);
        }

        return response()->json([
            'shop' => $shop,
            'products_count' => $shop->products_count,
            'followers_count' => $shop->followers_count
        ]);
    }

    public function getShopProducts($sellerId, Request $request)
    {
        try {
            // Lấy thông tin shop profile
            $shopProfile = ProfileShop::where('owner_id', $sellerId)
                ->with(['owner' => function ($q) {
                    $q->select('id', 'name', 'email');
                }])
                ->withCount('followers')
                ->withCount(['products' => function ($q) {
                    $q->where('status', 1)
                        ->where('stock', '>', 0);
                }])
                ->first();

            if (!$shopProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin shop'
                ], 404);
            }

            // Lấy danh sách sản phẩm
            $perPage = $request->input('per_page', 12);
            $categoryId = $request->input('category_id');
            $sortBy = $request->input('sort_by', 'latest');

            $query = Product::with([
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                },
                'category'
            ])
                ->where('seller_id', $sellerId)
                ->where('status', 1)
                ->where('stock', '>', 0);

            // Lọc theo danh mục
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            // Sắp xếp
            switch ($sortBy) {
                case 'best_selling':
                    $query->select([
                        'products.*',
                        DB::raw('(SELECT SUM(quantity) FROM order_details WHERE product_id = products.id) as total_sold')
                    ])
                        ->orderByDesc('total_sold');
                    break;
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $products = $query->paginate($perPage);

            // Kiểm tra user hiện tại có follow shop này chưa
            $isFollowing = false;
            if (Auth::check()) {
                $isFollowing = Follow::where('follower_id', Auth::user()->id)
                    ->where('followed_shop_id', $shopProfile->id)
                    ->exists();
            }

            // Nhóm sản phẩm theo danh mục để hiển thị
            $categories = $products->items();

            // Chuyển danh sách sản phẩm thành một collection để dễ thao tác
            $categories = collect($categories)->groupBy(function ($item) {
                return $item['category']['name']; // Nhóm theo tên danh mục
            })->map(function ($group) {
                // Thêm các thuộc tính khác từ danh mục
                $firstItem = $group->first();
                return [
                    'slug' => $firstItem['category']['slug'],
                    'id' => $firstItem['category']['id'],
                    'image' => $firstItem['category']['image'],
                    'products' => $group, // Danh sách sản phẩm trong nhóm
                    'name' => $firstItem['category']['name'], // Danh sách sản phẩm trong nhóm
                ];
            });
            


            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin shop và sản phẩm thành công',
                'data' => [
                    'shop' => [
                        'id' => $shopProfile->id,
                        'name' => $shopProfile->shop_name,
                        'description' => $shopProfile->description,
                        'logo_url' => $shopProfile->logo_url,
                        'banner_url' => $shopProfile->banner_url,
                        'owner' => $shopProfile->owner,
                        'followers_count' => $shopProfile->followers_count,
                        'products_count' => $shopProfile->products_count,
                        'is_following' => $isFollowing
                    ],
                    'categories' => $categories,
                    'products' => $products
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error getting shop info and products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin shop và sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
