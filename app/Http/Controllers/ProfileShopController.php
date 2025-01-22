<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\Product;
use App\Models\ProfileShop;
use App\Models\User;
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
                'category',
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image_url', 'created_at')
                        ->orderBy('created_at', 'asc');
                }

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

            // Lấy hình ảnh chính cho từng sản phẩm
            $productsArray = $products->items(); // Lấy danh sách sản phẩm

            foreach ($productsArray as $product) {
                $product->main_image = $product->images->first()->image_url ?? null; // Lấy hình ảnh đầu tiên
            }

            // Kiểm tra user hiện tại có follow shop này chưa
            $isFollowing = false;
            if (Auth::check()) {
                $isFollowing = Follow::where('follower_id', Auth::user()->id)
                    ->where('followed_shop_id', $shopProfile->id)
                    ->exists();
            }

            // Nhóm sản phẩm theo danh mục để hiển thị
            $categories = collect($productsArray)->groupBy(function ($item) {
                return $item['category']['name']; // Nhóm theo tên danh mục
            })->map(function ($group) {
                // Thêm các thuộc tính khác từ danh mục
                $firstItem = $group->first();
                // Lấy hình ảnh đầu tiên từ mối quan hệ images
                $firstImage = $firstItem->images->sortBy('created_at')->first(); // Lấy hình ảnh được tạo sớm nhất
                return [
                    'slug' => $firstItem['category']['slug'],
                    'id' => $firstItem['category']['id'],
                    'image' => $firstImage ? $firstImage->image_url : null, // Sử dụng hình ảnh đầu tiên
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
    public function createOrUpdateProfile(Request $request)
    {
        $request->validate([
            'shop_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|mimes:jpeg,jpg,png,gif', // Kiểm tra đuôi tệp logo
            'banner_url' => 'nullable|mimes:jpeg,jpg,png,gif', // Kiểm tra đuôi tệp banner
        ]);

        try {
            DB::beginTransaction();

            // Lấy owner_id từ người dùng hiện tại
            $ownerId = Auth::id();

            // Tìm profile shop theo owner_id
            $profileShop = ProfileShop::where('owner_id', $ownerId)->first();

            // Xử lý tải lên tệp logo và banner
            $logoPath = $profileShop->logo_url ?? null;
            $bannerPath = $profileShop->banner_url ?? null;

            if ($request->hasFile('logo_url')) {
                $logo = $request->file('logo_url');
                $logoName = time() . '_logo_' . $logo->getClientOriginalName();
                $logo->move(public_path('images/profiles'), $logoName);
                $logoPath = 'images/profiles/' . $logoName;
            }

            if ($request->hasFile('banner_url')) {
                $banner = $request->file('banner_url');
                $bannerName = time() . '_banner_' . $banner->getClientOriginalName();
                $banner->move(public_path('images/profiles'), $bannerName);
                $bannerPath = 'images/profiles/' . $bannerName;
            }

            if ($profileShop) {
                // Nếu đã tồn tại, cập nhật profile
                $profileShop->update([
                    'shop_name' => $request->input('shop_name'),
                    'description' => $request->input('description'),
                    'logo_url' => $logoPath,
                    'banner_url' => $bannerPath,
                ]);

                $message = 'Profile shop updated successfully';
            } else {
                // Nếu chưa tồn tại, tạo profile mới
                $profileShop = ProfileShop::create([
                    'shop_name' => $request->input('shop_name'),
                    'owner_id' => $ownerId,
                    'description' => $request->input('description'),
                    'logo_url' => $logoPath,
                    'banner_url' => $bannerPath,
                ]);

                // Thêm follow cho shop vừa tạo
                Follow::create([
                    'follower_id' => $ownerId,
                    'followed_shop_id' => $profileShop->id,
                    'follow_date' => now(),
                ]);

                $message = 'Profile shop created successfully';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $profileShop,
                'follow_count' => $profileShop->followers()->count(),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating or updating profile shop',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getSellersWithoutProfileShop()
    {
        try {
            $sellersWithoutProfileShop = User::where('role', 'employee')->whereDoesntHave('profileShop')->get();
            return response()->json([
                'success' => true,
                'message' => 'Danh sách seller chưa có profile shop',
                'data' => $sellersWithoutProfileShop
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function checkProfileExists()
    {
        $ownerId = Auth::id();
        $profileExists = ProfileShop::where('owner_id', $ownerId)->exists();

        return response()->json([
            'success' => true,
            'profile_exists' => $profileExists,
        ]);
    }
    public function getProfileShopInfo()
    {
        $ownerId = Auth::id();
        $shop = ProfileShop::where('owner_id', $ownerId)
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
}
