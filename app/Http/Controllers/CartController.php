<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ShippingCategory;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Lấy người dùng hiện tại
        $user = Auth::user();

        // Tìm hoặc tạo giỏ hàng của người dùng
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Duyệt qua các sản phẩm trong yêu cầu
        foreach ($request->items as $item) {
            // Tìm mục giỏ hàng trùng `cart_id`, `product_id`, và `attributes`
            $existingCartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $item['product_id'])
                ->where('attributes', json_encode($item['attributes']))
                ->first();

            if ($existingCartItem) {
                // Nếu đã tồn tại, tăng số lượng
                $existingCartItem->update([
                    'quantity' => $existingCartItem->quantity + $item['quantity'],
                    'updated_at' => now(),
                ]);
            } else {
                // Nếu không tồn tại, thêm mới
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'attributes' => json_encode($item['attributes']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Lấy lại danh sách các mục trong giỏ hàng
        $cartItems = CartItem::where('cart_id', $cart->id)
            ->with('product')
            ->get();

        // Trả về response với dữ liệu giỏ hàng
        return response()->json([
            'message' => 'Cart updated successfully',
            'cart_items' => $cartItems,
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Đếm số lượng item trong giỏ hàng của người dùng hiện tại
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function countItems()
    {
        // Lấy người dùng hiện tại
        $user = Auth::user();

        // Tìm giỏ hàng của người dùng
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'count' => 0
            ]);
        }

        // Đếm tổng số lượng các items trong giỏ hàng
        $totalItems = CartItem::where('cart_id', $cart->id)
            ->sum('quantity');

        return response()->json([
            'count' => $totalItems
        ]);
    }

    /**
     * Lấy tất cả sản phẩm trong giỏ hàng của người dùng hiện tại
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCartItems()
    {
        try {
            // Kiểm tra user đã đăng nhập
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng chưa đăng nhập'
                ], 401);
            }

            // Log để debug
            Log::info('User ID: ' . $user->id);

            // Tìm giỏ hàng
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                return response()->json([
                    'success' => true,
                    'items' => [],
                    'message' => 'Giỏ hàng trống'
                ]);
            }

            // Log để debug
            Log::info('Cart ID: ' . $cart->id);

            // Lấy cart items với relationships
            $cartItems = CartItem::where('cart_id', $cart->id)
                ->with([
                    'product' => function ($query) {
                        $query->select('id', 'name', 'price', 'image', 'slug', 'seller_id', 'category_id')
                            ->with([
                                'discounts' => function ($q) {
                                    $q->where('status', 1)
                                        ->where('date_begin', '<=', now())
                                        ->where('date_end', '>=', now());
                                },
                                'colors',
                                'sizes'
                            ]);
                    }
                ])
                ->get();

            // Log số lượng items tìm được
            Log::info('Found cart items: ' . $cartItems->count());

            // Transform data
            $transformedItems = $cartItems->map(function ($item) {
                if (!$item->product) {
                    return null;
                }

                $attributes = json_decode($item->attributes, true);
                $selectedColor = null;
                $selectedSize = null;
                $originalPrice = $item->product->price;
                $salePrice = $originalPrice;
                $productImage = $item->product->image;

                // Nếu có size, set original_price là giá của size đó
                if (isset($attributes['size'])) {
                    $selectedSize = $item->product->sizes
                        ->where('size_value', $attributes['size'])
                        ->first();
                    if ($selectedSize) {
                        $originalPrice = $selectedSize->price;
                        $salePrice = $originalPrice;
                    }
                }

                // Tính discount theo phần trăm nếu có
                if ($item->product->discounts->isNotEmpty()) {
                    $discount = $item->product->discounts->first();
                    // Chỉ xử lý discount dạng phần trăm
                    $salePrice = round($originalPrice * (1 - $discount->discount_value / 100));
                }

                // Phần code xử lý màu sắc
                if (isset($attributes['color'])) {
                    $selectedColor = $item->product->colors
                        ->where('color_value', $attributes['color'])
                        ->first();
                    if ($selectedColor && $selectedColor->image) {
                        $productImage = $selectedColor->image;
                    }
                }

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'attributes' => $attributes,
                    'seller_id' => $item->product->seller_id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'original_price' => $originalPrice,
                        'sale_price' => $salePrice,
                        'image' => $productImage,
                        'slug' => $item->product->slug,
                        'category_id' => $item->category_id,
                        'selected_color' => $selectedColor ? [
                            'id' => $selectedColor->id,
                            'color_value' => $selectedColor->color_value,
                            'color_code' => $selectedColor->color_code,
                            'image_url' => $selectedColor->image_url
                        ] : null,
                        'selected_size' => $selectedSize ? [
                            'id' => $selectedSize->id,
                            'size_value' => $selectedSize->size_value,
                            'original_price' => $selectedSize->price,
                            'sale_price' => $salePrice,
                            'price_difference' => $selectedSize->price_difference
                        ] : null
                    ],
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ];
            })->filter();

            $items = $cartItems->map(function($item) {
                return [
                    'category_id' => $item->product->category_id,
                    'quantity' => $item->quantity
                ];
            })->toArray();

            $shippingCost = ShippingCategory::calculateTotalShipping($items);

            return response()->json([
                'success' => true,
                'items' => $transformedItems,
                'shipping_cost' => $shippingCost,
                'message' => 'Lấy dữ liệu giỏ hàng thành công'
            ]);
        } catch (\Exception $e) {
            // Log lỗi để debug
            Log::error('Error in getCartItems: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request)
    {
        try {
            // Validate đầu vào
            $request->validate([
                'itemId' => 'required|exists:cart_items,id',
                'quantity' => 'required|integer|min:1'
            ]);

            // Lấy user hiện tại
            $user = Auth::user();

            // Tìm cart item
            $cartItem = CartItem::where('id', $request->itemId)
                ->whereHas('cart', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
                ], 404);
            }

            // Cập nhật số lượng
            $cartItem->update([
                'quantity' => $request->quantity,
                'updated_at' => now()
            ]);

            // Lấy tất cả cart items để tính shipping cost
            $items = CartItem::where('cart_id', $cartItem->cart_id)
                ->with('product')
                ->get()
                ->map(function($item) {
                    return [
                        'category_id' => $item->product->category_id,
                        'quantity' => $item->quantity
                    ];
                })->toArray();

            // Tính phí vận chuyển mới
            $shippingCost = ShippingCategory::calculateTotalShipping($items);

            // Lấy tổng số lượng sản phẩm trong giỏ hàng
            $totalItems = CartItem::where('cart_id', $cartItem->cart_id)
                ->sum('quantity');

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật số lượng thành công',
                'count' => $totalItems,
                'shipping_cost' => $shippingCost
            ]);
        } catch (\Exception $e) {
            Log::error('Error in updateQuantity: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật số lượng'
            ], 500);
        }
    }

    /**
     * Xóa sản phẩm khỏi giỏ hàng
     * 
     * @param string $id ID của cart item
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromCart(string $id)
    {
        try {
            // Lấy user hiện tại
            $user = Auth::user();

            // Tìm cart item và kiểm tra quyền sở hữu
            $cartItem = CartItem::where('id', $id)
                ->whereHas('cart', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
                ], 404);
            }

            // Lưu cart_id trước khi xóa item
            $cartId = $cartItem->cart_id;
            
            // Xóa cart item
            $cartItem->delete();

            // Lấy các items còn lại để tính shipping cost
            $remainingItems = CartItem::where('cart_id', $cartId)
                ->with('product')
                ->get()
                ->map(function($item) {
                    return [
                        'category_id' => $item->product->category_id,
                        'quantity' => $item->quantity
                    ];
                })->toArray();

            // Tính phí vận chuyển mới
            $shippingCost = ShippingCategory::calculateTotalShipping($remainingItems);

            // Lấy tổng số lượng sản phẩm còn lại trong giỏ hàng
            $totalItems = CartItem::where('cart_id', $cartId)
                ->sum('quantity');

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
                'count' => $totalItems,
                'shipping_cost' => $shippingCost
            ]);
        } catch (\Exception $e) {
            Log::error('Error in removeFromCart: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa sản phẩm'
            ], 500);
        }
    }

    /**
     * Xóa tất cả sản phẩm trong giỏ hàng của người dùng
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart()
    {
        try {
            // Lấy user hiện tại
            $user = Auth::user();

            // Tìm giỏ hàng của user
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giỏ hàng'
                ], 404);
            }

            // Xóa tất cả cart items của giỏ hàng này
            CartItem::where('cart_id', $cart->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa tất cả sản phẩm trong giỏ hàng',
                'count' => 0
            ]);
        } catch (\Exception $e) {
            Log::error('Error in clearCart: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa giỏ hàng'
            ], 500);
        }
    }
}
