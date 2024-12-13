<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Shipping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'total_amount' => 'required|numeric|min:0',
            'order_details' => 'required|array|min:1',
            'order_details.*.product_id' => 'required|exists:products,id',
            'order_details.*.quantity' => 'required|integer|min:1',
            'order_details.*.price' => 'required|numeric|min:0',
            'order_details.*.attributes' => 'nullable|array',

            // Validate shipping data
            'shipping.shipping_method' => 'required|string',
            'shipping.first_name' => 'required|string|max:255',
            'shipping.last_name' => 'required|string|max:255',
            'shipping.phone' => 'required|string|max:255',
            'shipping.email' => 'nullable|email|max:255',
            'shipping.address' => 'required|string|max:255',
            'shipping.country' => 'required|string|max:255',
            'shipping.city' => 'required|string|max:255',
            'shipping.zip_code' => 'required|string|max:255',
            'shipping.shipping_cost' => 'required|numeric|min:0',
            'shipping.shipping_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Tạo order mới
            $order = Order::create([
                'seller_id' => $request->seller_id,
                'customer_id' => $request->customer_id,
                'total_amount' => $request->total_amount,
                'status' => '1'
            ]);

            // Tạo các order details
            foreach ($request->order_details as $detail) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'attributes' => $detail['attributes'] ?? null
                ]);
            }

            // Tạo shipping
            $shippingData = $request->shipping;
            Shipping::create([
                'order_id' => $order->id,
                'shipping_method' => $shippingData['shipping_method'],
                'first_name' => $shippingData['first_name'],
                'last_name' => $shippingData['last_name'],
                'phone' => $shippingData['phone'],
                'email' => $shippingData['email'] ?? null,
                'address' => $shippingData['address'],
                'country' => $shippingData['country'],
                'city' => $shippingData['city'],
                'zip_code' => $shippingData['zip_code'],
                'shipping_cost' => $shippingData['shipping_cost'],
                'shipping_notes' => $shippingData['shipping_notes'] ?? null,
                'status' => 'pending', // Hoặc trạng thái mặc định
            ]);

            DB::commit();

            // Load relationships và trả về response
            $order->load(['orderDetails', 'shipping']); // Load cả shipping nếu cần

            return response()->json([
                'success' => true,
                'message' => 'Đơn hàng đã được tạo thành công',
                'order' => $order
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getCustomerOrders()
    {
        try {
            // Lấy tất cả đơn hàng của khách hàng đang đăng nhập
            $orders = Order::with(['orderDetails.product', 'seller'])
                ->where('customer_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Lấy danh sách đơn hàng thành công',
                'orders' => $orders
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy danh sách đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getAllOrders()
    {
        try {
            // Lấy tất cả đơn hàng kèm seller và shipping
            $orders = Order::with([
                'seller:id,name,email', // Lấy thông tin seller
                'shipping:id,order_id,shipping_cost', // Lấy thông tin shipping
                'orderDetails.product:id,name,image' // Lấy thông tin sản phẩm từ OrderDetail
            ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Chuẩn bị dữ liệu trả về
            $ordersWithDetails = $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'created_at' => $order->created_at,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'seller' => [
                        'seller_id' => $order->seller->id ?? null,
                        'seller_name' => $order->seller->name ?? null,
                        'seller_email' => $order->seller->email ?? null,
                    ],
                    'shipping_cost' => $order->shipping->shipping_cost ?? 0,
                    'details' => $order->orderDetails->map(function ($detail) {
                        return [
                            'product_id' => $detail->product_id,
                            'product_name' => optional($detail->product)->name,
                            'image' => optional($detail->product)->image,
                            'quantity' => $detail->quantity,
                            'price' => $detail->price,
                            'attributes' => $detail->attributes,
                            'status' => $detail->status,
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách đơn hàng thành công',
                'data' => $ordersWithDetails
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy danh sách đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSellerOrders()
    {
        try {
            // Lấy tất cả đơn hàng của seller đang đăng nhập
            $orders = OrderDetail::with([
                'product:id,name,seller_id,image', // Lấy thông tin sản phẩm
            ])
                ->whereHas('product', function ($query) {
                    // Lọc sản phẩm theo seller đang đăng nhập
                    $query->where('seller_id', Auth::id());
                })
                ->orderBy('order_id', 'desc') // Sắp xếp theo thời gian tạo đơn hàng
                ->get();

            // Gom nhóm chi tiết đơn hàng theo order_id
            $groupedByOrder = $orders->groupBy('order_id')->map(function ($details) {
                $order = $details->first()->order; // Lấy thông tin đơn hàng
                $order->details = $details->map(function ($detail) {
                    return [
                        'product_id' => $detail->product_id,
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'attributes' => $detail->attributes,
                        'product_name' => $detail->product->name,
                        'image' => $detail->product->image,
                        'status' => $detail->status, // Thêm trạng thái của chi tiết đơn hàng
                    ];
                });

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách đơn hàng của seller thành công',
                'data' => $groupedByOrder->values() // Trả về danh sách đã gom nhóm theo order_id
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy danh sách đơn hàng của seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getOrderDetails($orderId)
    {
        try {
            // Lấy thông tin đơn hàng kèm theo chi tiết và shipping
            $order = Order::with([
                'orderDetails.product', // Lấy thông tin sản phẩm
                'shipping',             // Lấy thông tin shipping
                'customer'              // Lấy thông tin khách hàng
            ])->findOrFail($orderId);

            // Map dữ liệu chi tiết đơn hàng
            $orderDetails = $order->orderDetails->map(function ($detail) {
                return [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name,
                    'quantity' => $detail->quantity,
                    'price' => $detail->price,
                    'attributes' => $detail->attributes,
                    'image' => $detail->product->image,
                    'status' => $detail->status,
                ];
            });

            // Trả về thông tin đơn hàng và các chi tiết liên quan
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin chi tiết đơn hàng thành công',
                'data' => [
                    'order' => [
                        'order_id' => $order->id,
                        'created_at' => $order->created_at,

                        'shipping' => [
                            'shipping_method' => $order->shipping->shipping_method,
                            'tracking_number' => $order->shipping->tracking_number,
                            'id' => $order->shipping->id,
                            'first_name' => $order->shipping->first_name,
                            'last_name' => $order->shipping->last_name,
                            'phone' => $order->shipping->phone,
                            'address' => $order->shipping->address,
                            'city' => $order->shipping->city,
                            'country' => $order->shipping->country,
                            'zip_code' => $order->shipping->zip_code,
                            'shipping_cost' => $order->shipping->shipping_cost,
                            'notes' => $order->shipping->shipping_notes,
                        ],
                        'total_amount' => $order->total_amount,
                    ],
                    'order_details' => $orderDetails,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin chi tiết đơn hàng',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, $orderId)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'status' => 'required|integer|in:1,2,3,4,5', // Trạng thái phải là một trong các giá trị 1, 2, 3, 4, 5
        ]);

        try {
            // Tìm đơn hàng cần cập nhật
            $order = Order::findOrFail($orderId);

            // Cập nhật trạng thái của đơn hàng
            $order->status = $request->status;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái đơn hàng thành công',
                'order' => $order
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
