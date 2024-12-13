<?php

namespace App\Http\Controllers;

use App\Models\Shipping;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
    public function updateTracking(Request $request, $id)
    {
        // Log toàn bộ request
        Log::info('Request Data:', $request->all());

        // Validate request
        $request->validate([
            'tracking_number' => 'required|string|max:255',
        ]);

        // Tìm Shipping record
        $shipping = Shipping::find($id);

        if (!$shipping) {
            Log::error('Shipping record not found for ID: ' . $id);
            return response()->json(['message' => 'Shipping record not found'], 404);
        }

        // Cập nhật tracking number
        $shipping->tracking_number = $request->tracking_number;
        $shipping->save();

        // Log kết quả lưu
        Log::info('Updated Shipping:', $shipping->toArray());

        // Gửi email thông báo mã tracking
        try {
            Mail::to('recipient@example.com')->send(new \App\Mail\TrackingMail($request->tracking_number));
            Log::info('Email sent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
            return response()->json([
                'message' => 'Tracking number updated but failed to send email',
                'data' => $shipping,
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Tracking number updated and email sent successfully',
            'data' => $shipping,
        ], 200);
    }
}
