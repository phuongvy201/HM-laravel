<?php

namespace App\Http\Controllers;

use App\Models\Shipping;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    /**
     * Hiển thị danh sách shipping
     */
    public function index()
    {
        $shippings = Shipping::paginate(10);
        return response()->json([
            'success' => true,
            'data' => $shippings
        ]);
    }

    /**
     * Lưu thông tin shipping mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'shipping_method' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip_code' => 'required|string|max:255',
            'shipping_cost' => 'required|numeric|min:0',
            'estimated_delivery_date' => 'nullable|date',
            'shipping_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $shipping = Shipping::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $shipping
        ], Response::HTTP_CREATED);
    }

    /**
     * Hiển thị chi tiết shipping
     */
    public function show(Shipping $shipping)
    {
        return response()->json([
            'success' => true,
            'data' => $shipping
        ]);
    }

    /**
     * Cập nhật thông tin shipping
     */
    public function update(Request $request, Shipping $shipping)
    {
        $validator = Validator::make($request->all(), [
            'shipping_method' => 'string',
            'tracking_number' => 'nullable|string',
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'phone' => 'string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'string|max:255',
            'country' => 'string|max:255',
            'city' => 'string|max:255',
            'zip_code' => 'string|max:255',
            'shipping_cost' => 'numeric|min:0',
            'status' => 'string',
            'estimated_delivery_date' => 'nullable|date',
            'actual_delivery_date' => 'nullable|date',
            'shipping_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $shipping->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $shipping
        ]);
    }

    /**
     * Xóa shipping
     */
    public function destroy(Shipping $shipping)
    {
        $shipping->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipping deleted successfully'
        ]);
    }

    /**
     * Cập nhật trạng thái shipping
     */
    public function updateStatus(Request $request, Shipping $shipping)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $shipping->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'data' => $shipping
        ]);
    }

    /**
     * Cập nhật tracking number
     */
    public function updateTracking(Request $request, Shipping $shipping)
    {
        $validator = Validator::make($request->all(), [
            'tracking_number' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $shipping->update([
            'tracking_number' => $request->tracking_number
        ]);

        return response()->json([
            'success' => true,
            'data' => $shipping
        ]);
    }
}
