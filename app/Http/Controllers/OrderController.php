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
            'seller_id' => 'required|exists:users,id',
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
                'customer_id' => Auth::id(),
                'seller_id' => $request->seller_id,
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
}
