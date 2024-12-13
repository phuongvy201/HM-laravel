<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductSale;
use Exception;
use Illuminate\Support\Facades\Log;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10); // Mặc định 10 item mỗi trang

            $productSales = ProductSale::select('product_sales.*', 'products.name as product_name', 'users.name as seller_name')
                ->leftJoin('products', 'product_sales.product_id', '=', 'products.id')
                ->leftJoin('users', 'products.seller_id', '=', 'users.id')
                ->orderBy('product_sales.created_at', 'desc')
                ->paginate($perPage)
                ->through(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'discount_name' => $sale->discount_name,
                        'discount_value' => $sale->discount_value . '%',
                        'product_name' => $sale->product_name ?? 'N/A',
                        'product_id' => $sale->product_id,
                        'product_image' => $sale->product?->image,
                        'seller_name' => $sale->seller_name ?? 'N/A',
                        'seller_id' => $sale->product?->seller_id,
                        'date_begin' => date('d/m/Y', strtotime($sale->date_begin)),
                        'date_end' => date('d/m/Y', strtotime($sale->date_end)),
                        'status' => $sale->status,
                        'created_at' => date('d/m/Y H:i', strtotime($sale->created_at)),
                        'updated_at' => date('d/m/Y H:i', strtotime($sale->updated_at))
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách khuyến mãi thành công',
                'data' => $productSales->items(),
                'meta' => [
                    'current_page' => $productSales->currentPage(),
                    'from' => $productSales->firstItem(),
                    'last_page' => $productSales->lastPage(),
                    'per_page' => $productSales->perPage(),
                    'to' => $productSales->lastItem(),
                    'total' => $productSales->total(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách khuyến mãi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách khuyến mãi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getDiscountsBySeller(Request $request, $sellerId)
    {
        try {
            // Lấy số lượng item mỗi trang, mặc định là 10
            $perPage = $request->input('per_page', 50);

            // Truy vấn khuyến mãi của các sản phẩm của seller nhất định
            $productSales = ProductSale::select('product_sales.*', 'products.name as product_name', 'users.name as seller_name')
                ->leftJoin('products', 'product_sales.product_id', '=', 'products.id')
                ->leftJoin('users', 'products.seller_id', '=', 'users.id')
                ->where('products.seller_id', $sellerId)  // Lọc theo seller_id
                ->orderBy('product_sales.created_at', 'desc')
                ->paginate($perPage)
                ->through(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'discount_name' => $sale->discount_name,
                        'discount_value' => $sale->discount_value . '%',
                        'product_name' => $sale->product_name ?? 'N/A',
                        'product_id' => $sale->product_id,
                        'product_image' => $sale->product?->image,
                        'seller_name' => $sale->seller_name ?? 'N/A',
                        'seller_id' => $sale->product?->seller_id,
                        'date_begin' => date('d/m/Y', strtotime($sale->date_begin)),
                        'date_end' => date('d/m/Y', strtotime($sale->date_end)),
                        'status' => $sale->status,
                        'created_at' => date('d/m/Y H:i', strtotime($sale->created_at)),
                        'updated_at' => date('d/m/Y H:i', strtotime($sale->updated_at))
                    ];
                });

            // Trả về kết quả
            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách khuyến mãi của seller thành công',
                'data' => $productSales->items(),
                'meta' => [
                    'current_page' => $productSales->currentPage(),
                    'from' => $productSales->firstItem(),
                    'last_page' => $productSales->lastPage(),
                    'per_page' => $productSales->perPage(),
                    'to' => $productSales->lastItem(),
                    'total' => $productSales->total(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy danh sách khuyến mãi theo seller: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách khuyến mãi theo seller',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getCategoryPath($category)
    {
        $path = [];
        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }
        return implode(' > ', $path);
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
        DB::beginTransaction();
        try {
            // 1. Validate với thông báo lỗi chi tiết
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'discount_value' => 'required|numeric|min:0|max:100',
                'discount_name' => 'required|string|max:255',
                'date_begin' => 'required|date',
                'date_end' => 'required|date|after:date_begin',
                'status' => 'required|in:0,1,2',
            ], [
                // Thông báo lỗi tiếng Việt chi tiết
                'product_id.required' => 'Vui lòng chọn sản phẩm',
                'product_id.exists' => 'Sản phẩm không tồn tại trong hệ thống',
                'discount_value.required' => 'Vui lòng nhập giá trị giảm giá',
                'discount_value.numeric' => 'Giá trị giảm giá phải là số',
                'discount_value.min' => 'Giá trị giảm giá không được nhỏ hơn 0%',
                'discount_value.max' => 'Giá trị giảm giá không được vượt quá 100%',
                'discount_name.required' => 'Vui lòng nhập tên chương trình giảm giá',
                'discount_name.string' => 'Tên chương trình giảm giá phải là chuỗi ký tự',
                'discount_name.max' => 'Tên chương trình giảm giá không được vượt quá 255 ký tự',
                'date_begin.required' => 'Vui lòng chọn ngày bắt đầu',
                'date_begin.date' => 'Ngày bắt đầu không đúng định dạng',
                'date_end.required' => 'Vui lòng chọn ngày kết thúc',
                'date_end.date' => 'Ngày kết thúc không đúng định dạng',
                'date_end.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
                'status.required' => 'Vui lòng chọn trạng thái',
                'status.in' => 'Trạng thái không hợp lệ',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()->all() // Trả về tất cả lỗi dưới dạng mảng
                ], 422);
            }


            // 3. Kiểm tra trùng lặp khuyến mãi
            $existingDiscount = ProductSale::where('product_id', $request->product_id)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('date_begin', [$request->date_begin, $request->date_end])
                        ->orWhereBetween('date_end', [$request->date_begin, $request->date_end]);
                })->first();

            if ($existingDiscount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm đã có khuyến mãi trong khoảng thời gian này',
                    'existing_discount' => [
                        'name' => $existingDiscount->discount_name,
                        'date_begin' => $existingDiscount->date_begin,
                        'date_end' => $existingDiscount->date_end
                    ]
                ], 400);
            }

            // 4. Kiểm tra ngày bắt đầu không được nhỏ hơn ngày hiện tại
            if (strtotime($request->date_begin) < strtotime(date('Y-m-d'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ngày bắt đầu không được nhỏ hơn ngày hiện tại'
                ], 400);
            }

            try {
                $discount = ProductSale::create([
                    'product_id' => $request->product_id,
                    'discount_value' => $request->discount_value,
                    'discount_name' => $request->discount_name,
                    'date_begin' => $request->date_begin,
                    'date_end' => $request->date_end,
                    'status' => $request->status,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);

                DB::commit();

                // 5. Trả về thông báo thành công với đầy đủ thông tin
                return response()->json([
                    'success' => true,
                    'message' => 'Thêm khuyến mãi thành công',
                    'data' => $discount->load(['product', 'creator', 'updater']),
                    'discount_info' => [
                        'name' => $discount->discount_name,
                        'value' => $discount->discount_value . '%',
                        'period' => 'Từ ' . date('d/m/Y', strtotime($discount->date_begin)) .
                            ' đến ' . date('d/m/Y', strtotime($discount->date_end))
                    ]
                ], 201);
            } catch (Exception $e) {
                DB::rollBack();
                // 6. Log lỗi để debug
                Log::error('Lỗi khi tạo khuyến mãi: ' . $e->getMessage());
                Log::error($e->getTraceAsString());

                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi thêm khuyến mãi',
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode()
                ], 500);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $discount = ProductSale::with(['product', 'creator', 'updater'])
                ->select('product_sales.*', 'products.name as product_name', 'users.name as seller_name')
                ->leftJoin('products', 'product_sales.product_id', '=', 'products.id')
                ->leftJoin('users', 'products.seller_id', '=', 'users.id')
                ->where('product_sales.id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin khuyến mãi thành công',
                'data' => [
                    'id' => $discount->id,
                    'discount_name' => $discount->discount_name,
                    'discount_value' => $discount->discount_value,
                    'product_name' => $discount->product_name ?? 'N/A',
                    'product_id' => $discount->product_id,
                    'product_image' => $discount->product?->image,
                    'seller_name' => $discount->seller_name ?? 'N/A',
                    'seller_id' => $discount->product?->seller_id,
                    'date_begin' => $discount->date_begin,
                    'date_end' => $discount->date_end,
                    'status' => $discount->status,
                    'created_at' => date('d/m/Y H:i', strtotime($discount->created_at)),
                    'updated_at' => date('d/m/Y H:i', strtotime($discount->updated_at)),
                    'created_by' => $discount->creator?->name,
                    'updated_by' => $discount->updater?->name
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy thông tin khuyến mãi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin khuyến mãi',
                'error' => $e->getMessage()
            ], 500);
        }
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
        DB::beginTransaction();
        try {
            // 1. Validation with detailed error messages
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'discount_value' => 'required|numeric|min:0|max:100',
                'discount_name' => 'required|string|max:255',
                'date_begin' => 'required|date',
                'date_end' => 'required|date|after:date_begin',
                'status' => 'required|in:0,1,2',
            ], [
                'product_id.required' => 'Please select a product',
                'product_id.exists' => 'Product does not exist in the system',
                'discount_value.required' => 'Please enter discount value',
                'discount_value.numeric' => 'Discount value must be a number',
                'discount_value.min' => 'Discount value cannot be less than 0%',
                'discount_value.max' => 'Discount value cannot exceed 100%',
                'discount_name.required' => 'Please enter discount program name',
                'discount_name.string' => 'Discount program name must be a string',
                'discount_name.max' => 'Discount program name cannot exceed 255 characters',
                'date_begin.required' => 'Please select start date',
                'date_begin.date' => 'Invalid start date format',
                'date_end.required' => 'Please select end date',
                'date_end.date' => 'Invalid end date format',
                'date_end.after' => 'End date must be after start date',
                'status.required' => 'Please select status',
                'status.in' => 'Invalid status',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data',
                    'errors' => $validator->errors()->all()
                ], 422);
            }

            // 2. Find discount to update
            $discount = ProductSale::findOrFail($id);

            // 3. Check for overlapping discounts (excluding itself)
            $existingDiscount = ProductSale::where('product_id', $request->product_id)
                ->where('id', '!=', $id)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('date_begin', [$request->date_begin, $request->date_end])
                        ->orWhereBetween('date_end', [$request->date_begin, $request->date_end]);
                })->first();

            if ($existingDiscount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product already has another discount during this period',
                    'existing_discount' => [
                        'name' => $existingDiscount->discount_name,
                        'date_begin' => $existingDiscount->date_begin,
                        'date_end' => $existingDiscount->date_end
                    ]
                ], 400);
            }

            // 4. Check if start date is not less than current date
            if (strtotime($request->date_begin) < strtotime(date('Y-m-d'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Start date cannot be earlier than current date'
                ], 400);
            }

            try {
                // 5. Update discount information
                $discount->update([
                    'product_id' => $request->product_id,
                    'discount_value' => $request->discount_value,
                    'discount_name' => $request->discount_name,
                    'date_begin' => $request->date_begin,
                    'date_end' => $request->date_end,
                    'status' => $request->status,
                    'updated_by' => Auth::id()
                ]);

                DB::commit();

                // 6. Return success message
                return response()->json([
                    'success' => true,
                    'message' => 'Discount updated successfully',
                    'data' => $discount->load(['product', 'creator', 'updater']),
                    'discount_info' => [
                        'name' => $discount->discount_name,
                        'value' => $discount->discount_value . '%',
                        'period' => 'From ' . date('d/m/Y', strtotime($discount->date_begin)) .
                            ' to ' . date('d/m/Y', strtotime($discount->date_end))
                    ]
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error updating discount: ' . $e->getMessage());
                Log::error($e->getTraceAsString());

                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the discount',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'System error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $discount = ProductSale::findOrFail($id);
            $discount->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Xóa chương trình khuyến mãi thành công'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa khuyến mãi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa khuyến mãi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(string $id)
    {
        try {
            $discount = ProductSale::findOrFail($id);

            // Đổi status từ 1 -> 2 hoặc 2 -> 1
            $discount->status = $discount->status == 1 ? 2 : 1;
            $discount->updated_by = Auth::id();
            $discount->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'data' => $discount
            ]);
        } catch (Exception $e) {
            Log::error('Lỗi khi cập nhật trạng thái khuyến mãi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
