<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = (int)$request->get('per_page', 100); // Ép kiểu về số
            $page = (int)$request->get('page', 1);

            // Thêm điều kiện sắp xếp rõ ràng
            $query = Product::query()
                ->select([
                    'id',
                    'name',
                    'slug',
                    'seller_id',
                    'category_id',
                    'price',
                    'image',
                    'status',
                    'stock',
                    'created_at',
                    'description'
                ]);

            // Thêm relationships
            $query->with([
                'seller:id,name',
                'category' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'colors:id,product_id,color_value,color_code,image',
                'sizes:id,product_id,size_value,price'
            ]);

            // Sắp xếp theo nhiều tiêu chí
            $query->orderByDesc('created_at')
                ->orderByDesc('id');  // Thêm id để đảm bảo thứ tự nhất quán

            // Thực hiện phân trang
            $products = $query->paginate($perPage)->withQueryString();

            // Kiểm tra và log số lượng sản phẩm
            Log::info('Total products: ' . $products->total());
            Log::info('Current page products: ' . count($products->items()));

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công',
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'from' => $products->firstItem(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'to' => $products->lastItem(),
                    'total' => $products->total(),
                    'path' => $request->url(),
                ],
                'links' => [
                    'first' => $products->url(1),
                    'last' => $products->url($products->lastPage()),
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Product index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
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
            // Validate dữ liệu
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'seller_id' => 'required|exists:users,id',
                'category_id' => 'required|exists:categories,id',
                'price' => 'required|numeric|min:0',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif', // Validate file ảnh
                'description' => 'required|string',
                'stock' => 'required|integer|min:0',
                'status' => 'required|in:0,1,2',

                'sizes' => 'nullable|array',
                'sizes.*.size_value' => 'required_with:sizes|string',
                'sizes.*.price' => 'required_with:sizes|numeric|min:0',

                'colors' => 'nullable|array',
                'colors.*.color_value' => 'required_with:colors|string',
                'colors.*.color_code' => 'required_with:colors|string',
                'colors.*.image' => 'required_with:colors|image|mimes:jpeg,png,jpg,gif'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Xử lý upload ảnh chính
            $mainImagePath = null;
            if ($request->hasFile('image')) {
                $mainImage = $request->file('image');
                $mainImageName = time() . '_' . $mainImage->getClientOriginalName();
                $mainImage->move(public_path('images/products'), $mainImageName);
                $mainImagePath = 'images/products/' . $mainImageName;
            }

            // Tạo slug
            $slug = Str::slug($request->name);
            $count = 1;
            $originalSlug = $slug;
            while (Product::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-{$count}";
                $count++;
            }

            // Tạo sản phẩm
            $product = Product::create([
                'name' => $request->name,
                'slug' => $slug,
                'seller_id' => $request->seller_id,
                'category_id' => $request->category_id,
                'price' => $request->price,
                'image' => $mainImagePath,
                'description' => $request->description,
                'stock' => $request->stock,
                'status' => $request->status
            ]);

            // Xử lý sizes
            if ($request->filled('sizes')) {
                try {
                    $sizesData = $request->input('sizes');
                    // Kiểm tra nếu là string thì mới decode
                    $sizes = is_string($sizesData) ? json_decode($sizesData, true) : $sizesData;

                    Log::info('Sizes data type:', [
                        'type' => gettype($sizesData),
                        'data' => $sizesData
                    ]);

                    if (!is_array($sizes)) {
                        throw new \Exception('Invalid sizes data format');
                    }

                    // Xóa sizes cũ
                    $product->sizes()->delete();

                    // Thêm sizes mới
                    foreach ($sizes as $size) {
                        $product->sizes()->create([
                            'size_value' => $size['size_value'],
                            'price' => $size['price']
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing sizes:', [
                        'message' => $e->getMessage(),
                        'data' => $sizesData
                    ]);
                    throw $e;
                }
            }

            // Xử lý colors
            if ($request->has('colors')) {
                try {
                    Log::info('=== Bắt đầu xử lý Colors ===');

                    $colors = is_string($request->colors) ?
                        json_decode($request->colors, true) :
                        $request->colors;

                    // Lấy danh sách ID colors hiện tại
                    $existingColorIds = $product->colors()->pluck('id')->toArray();
                    $newColorIds = [];

                    foreach ($colors as $index => $color) {
                        $colorData = [
                            'color_value' => $color['color_value'],
                            'color_code' => $color['color_code']
                        ];

                        // Nếu có ID, cập nhật color hiện tại
                        if (isset($color['id'])) {
                            $existingColor = Color::find($color['id']);
                            if ($existingColor) {
                                // Xử lý ảnh nếu có
                                if ($request->hasFile("colors.{$index}.image")) {
                                    // Xóa ảnh cũ nếu không phải ảnh mặc định
                                    if (
                                        $existingColor->image &&
                                        $existingColor->image !== 'images/products/colors/default.jpg' &&
                                        file_exists(public_path($existingColor->image))
                                    ) {
                                        unlink(public_path($existingColor->image));
                                    }

                                    // Upload ảnh mới
                                    $colorFile = $request->file("colors.{$index}.image");
                                    $colorFileName = time() . '_' . Str::random(10) . '.' . $colorFile->getClientOriginalExtension();
                                    $colorPath = public_path('images/products/colors');

                                    if (!file_exists($colorPath)) {
                                        mkdir($colorPath, 0755, true);
                                    }

                                    $colorFile->move($colorPath, $colorFileName);
                                    $colorData['image'] = 'images/products/colors/' . $colorFileName;
                                }

                                $existingColor->update($colorData);
                                $newColorIds[] = $existingColor->id;
                            }
                        }
                        // Nếu không có ID, tạo color mới
                        else {
                            $colorData['image'] = 'images/products/colors/default.jpg';

                            // Xử lý ảnh mới nếu có
                            if ($request->hasFile("colors.{$index}.image")) {
                                $colorFile = $request->file("colors.{$index}.image");
                                $colorFileName = time() . '_' . Str::random(10) . '.' . $colorFile->getClientOriginalExtension();
                                $colorPath = public_path('images/products/colors');

                                if (!file_exists($colorPath)) {
                                    mkdir($colorPath, 0755, true);
                                }

                                $colorFile->move($colorPath, $colorFileName);
                                $colorData['image'] = 'images/products/colors/' . $colorFileName;
                            }

                            $newColor = $product->colors()->create($colorData);
                            $newColorIds[] = $newColor->id;
                        }
                    }

                    // Xóa những color không còn trong danh sách mới
                    $colorsToDelete = array_diff($existingColorIds, $newColorIds);
                    if (!empty($colorsToDelete)) {
                        // Xóa ảnh của những color bị xóa
                        $deletedColors = Color::whereIn('id', $colorsToDelete)->get();
                        foreach ($deletedColors as $deletedColor) {
                            if (
                                $deletedColor->image &&
                                $deletedColor->image !== 'images/products/colors/default.jpg' &&
                                file_exists(public_path($deletedColor->image))
                            ) {
                                unlink(public_path($deletedColor->image));
                            }
                        }
                        // Xóa record trong database
                        Color::whereIn('id', $colorsToDelete)->delete();
                    }

                    Log::info('=== Kết thúc xử lý Colors ===');
                } catch (\Exception $e) {
                    Log::error('Lỗi xử lý colors:', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            DB::commit();

            // Load relationships và trả về response
            $product->load(['sizes', 'colors']);

            return response()->json([
                'success' => true,
                'message' => 'Thêm sản phẩm thành công',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Xóa ảnh đã upload nếu có lỗi
            if (isset($mainImagePath) && file_exists(public_path($mainImagePath))) {
                unlink(public_path($mainImagePath));
            }

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm sản phẩm',
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
            // Tìm sản phẩm với các relationship cơ bản
            $product = Product::with([
                'seller:id,name',
                'category' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                }
            ])
                ->select([
                    'id',
                    'name',
                    'slug',
                    'seller_id',
                    'category_id',
                    'price',
                    'image',
                    'status',
                    'stock',
                    'created_at',
                    'description'
                ])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin sản phẩm thành công',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product colors
     */
    public function getColors(string $id)
    {
        try {
            // Kiểm tra sản phẩm tồn tại
            $product = Product::findOrFail($id);

            // Lấy tất cả colors của sản phẩm
            $colors = Color::where('product_id', $id)
                ->select([
                    'id',
                    'product_id',
                    'color_value',
                    'color_code',
                    'image',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách màu sắc thành công',
                'data' => $colors
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
                'error' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách màu sắc',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product sizes
     */
    public function getSizes(string $id)
    {
        try {
            // Kiểm tra sản phẩm tồn tại
            $product = Product::findOrFail($id);

            // Lấy tất cả sizes của sản phẩm
            $sizes = Size::where('product_id', $id)
                ->select([
                    'id',
                    'product_id',
                    'size_value',
                    'price',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách kích thước thành công',
                'data' => $sizes
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
                'error' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách kích thước',
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
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Tìm sản phẩm
            $product = Product::findOrFail($id);

            // Validate dữ liệu
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'seller_id' => 'sometimes|required|exists:users,id',
                'category_id' => 'sometimes|required|exists:categories,id',
                'price' => 'sometimes|required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif', // Validate file ảnh
                'description' => 'sometimes|required|string',
                'stock' => 'sometimes|required|integer|min:0',
                'status' => 'sometimes|required|in:0,1,2',

                'sizes' => 'nullable|array',
                'sizes.*.id' => 'nullable|exists:sizes,id',
                'sizes.*.size_value' => 'required_with:sizes|string',
                'sizes.*.price' => 'required_with:sizes|numeric|min:0',

                'colors' => 'nullable|array',
                'colors.*.id' => 'nullable|exists:colors,id',
                'colors.*.color_value' => 'required_with:colors|string',
                'colors.*.color_code' => 'required_with:colors|string',
                'colors.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Xử lý upload ảnh chính nếu có
            if ($request->hasFile('image')) {
                $mainImage = $request->file('image');
                $mainImageName = time() . '_' . $mainImage->getClientOriginalName();
                $mainImage->move(public_path('images/products'), $mainImageName);

                // Xóa ảnh cũ nếu có
                if ($product->image && file_exists(public_path($product->image))) {
                    unlink(public_path($product->image));
                }

                $product->image = 'images/products/' . $mainImageName;
            }

            // Cập nhật slug nếu tên thay đổi
            if ($request->has('name') && $request->name !== $product->name) {
                $slug = Str::slug($request->name);
                $count = 1;
                $originalSlug = $slug;
                while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = "{$originalSlug}-{$count}";
                    $count++;
                }
                $product->slug = $slug;
            }

            // Cập nhật các thuộc tính sản phẩm
            $product->update($request->only([
                'name',
                'seller_id',
                'category_id',
                'price',
                'description',
                'stock',
                'status'
            ]));

            // Xử lý sizes
            $sizes = is_string($request->sizes) ?
                json_decode($request->sizes, true) :
                $request->sizes;
            Log::info('Sizes Data:', ['sizes' => $sizes]);

            // Lấy danh sách ID sizes hiện tại
            $existingSizeIds = $product->sizes()->pluck('id')->toArray();
            $newSizeIds = [];

            if ($sizes) {
                foreach ($sizes as $index => $size) {
                    Log::info('Processing size:', [
                        'index' => $index,
                        'size' => $size
                    ]);

                    // Khởi tạo sizeData
                    $sizeData = [
                        'size_value' => $size['size_value'],
                        'price' => $size['price']
                    ];

                    // Nếu có ID, cập nhật size hiện tại
                    if (isset($size['id'])) {
                        $existingSize = Size::find($size['id']);
                        if ($existingSize) {
                            $existingSize->update($sizeData);
                            $newSizeIds[] = $existingSize->id;
                        }
                    }
                    // Nếu không có ID, tạo size mới
                    else {
                        $newSize = $product->sizes()->create($sizeData);
                        $newSizeIds[] = $newSize->id;
                    }
                }
            }

            // Xóa những sizes không còn trong danh sách mới
            $sizesToDelete = array_diff($existingSizeIds, $newSizeIds);
            if (!empty($sizesToDelete)) {
                Size::whereIn('id', $sizesToDelete)->delete();
            }

            // Xử lý colors
            $colors = is_string($request->colors) ?
                json_decode($request->colors, true) :
                $request->colors;
            Log::info('Colors Data:', ['colors' => $colors]);

            // Lấy danh sách ID colors hiện tại
            $existingColorIds = $product->colors()->pluck('id')->toArray();
            $newColorIds = [];

            if ($colors) {
                foreach ($colors as $index => $color) {
                    Log::info('Processing color:', [
                        'index' => $index,
                        'color' => $color
                    ]);

                    // Khởi tạo colorData
                    $colorData = [
                        'color_value' => $color['color_value'],
                        'color_code' => $color['color_code']
                    ];

                    // Nếu có ID, cập nhật color hiện tại
                    if (isset($color['id'])) {
                        $existingColor = Color::find($color['id']);
                        if ($existingColor) {
                            // Xử lý upload file nếu có
                            $imageKey = "colors.{$index}.image";
                            if ($request->hasFile($imageKey)) {
                                $colorFile = $request->file($imageKey);
                                $colorFileName = time() . '_' . $colorFile->getClientOriginalName();
                                $colorFile->move(public_path('images/products/colors'), $colorFileName);
                                $colorData['image'] = 'images/products/colors/' . $colorFileName;
                            }

                            $existingColor->update($colorData);
                            $newColorIds[] = $existingColor->id;
                        }
                    }
                    // Nếu không có ID, tạo color mới
                    else {
                        $colorData['image'] = 'images/products/colors/default.jpg';

                        // Xử lý upload file nếu có
                        $imageKey = "colors.{$index}.image";
                        if ($request->hasFile($imageKey)) {
                            $colorFile = $request->file($imageKey);
                            $colorFileName = time() . '_' . $colorFile->getClientOriginalName();
                            $colorFile->move(public_path('images/products/colors'), $colorFileName);
                            $colorData['image'] = 'images/products/colors/' . $colorFileName;
                        }

                        $newColor = $product->colors()->create($colorData);
                        $newColorIds[] = $newColor->id;
                    }
                }
            }

            // Xóa những color không còn trong danh sách mới
            $colorsToDelete = array_diff($existingColorIds, $newColorIds);
            if (!empty($colorsToDelete)) {
                Color::whereIn('id', $colorsToDelete)->delete();
            }

            DB::commit();

            // Load relationships và trả về response
            $product->load(['sizes', 'colors']);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công',
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            $product->sizes()->delete();
            $product->colors()->delete();
            $product->discounts()->delete();
            $product->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Xóa sản phẩm thành công!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa sản phẩm!']);
        }
    }

    /**
     * Get all parent categories recursively
     */
    private function getParentCategories($category)
    {
        $parents = collect([]);
        $currentCategory = $category;

        while ($currentCategory->parent) {
            $parents->push([
                'id' => $currentCategory->parent->id,
                'name' => $currentCategory->parent->name
            ]);
            $currentCategory = $currentCategory->parent;
        }

        return $parents;
    }

    /**
     * Get products by seller ID
     */
    public function getProductsBySeller(Request $request, string $sellerId)
    {
        try {
            $perPage = (int)$request->get('per_page', 50);

            // Tạo query cơ bản
            $query = Product::query()
                ->where('seller_id', $sellerId)
                ->select([
                    'id',
                    'name',
                    'slug',
                    'seller_id',
                    'category_id',
                    'price',
                    'image',
                    'status',
                    'stock',
                    'created_at',
                    'description'
                ]);

            // Thêm relationships
            $query->with([
                'seller:id,name',
                'category' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'colors:id,product_id,color_value,color_code,image',
                'sizes:id,product_id,size_value,price'
            ]);

            // Sắp xếp
            $query->orderByDesc('created_at')
                ->orderByDesc('id');

            // Phân trang
            $products = $query->paginate($perPage)->withQueryString();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm theo người bán thành công',
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'from' => $products->firstItem(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'to' => $products->lastItem(),
                    'total' => $products->total(),
                    'path' => $request->url(),
                ],
                'links' => [
                    'first' => $products->url(1),
                    'last' => $products->url($products->lastPage()),
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error getting products by seller: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm theo người bán',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product status
     */
    public function updateStatus(string $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            // Toggle status giữa 0 và 1
            $product->status = $product->status == 1 ? 0 : 1;
            $product->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái sản phẩm thành công',
                'data' => $product
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating product status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get limited products with active discount
     */
    public function getNewProducts()
    {
        try {
            $products = Product::with([
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }
            ])
                ->select([
                    'id',
                    'name',
                    'slug',
                    'seller_id',
                    'category_id',
                    'price',
                    'image',
                    'status',
                    'stock',
                    'created_at',
                    'description'
                ])
                ->where('status', 1)
                ->where('stock', '>', 0)
                ->limit(8)
                ->latest()
                ->get();

            // Thêm thông tin discount vào response


            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công',
                'data' => $products
            ]);
        } catch (Exception $e) {
            Log::error('Error getting limited products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get best selling products
     */
    public function getBestSellingProducts()
    {
        try {
            $products = Product::with([
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }
            ])
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.seller_id',
                    'products.category_id',
                    'products.price',
                    'products.image',
                    'products.status',
                    'products.stock',
                    'products.created_at',
                    'products.description',
                    DB::raw('SUM(order_details.quantity) as total_sold')
                ])
                ->leftJoin('order_details', 'products.id', '=', 'order_details.product_id')
                ->where('products.status', 1)
                ->where('products.stock', '>', 0)
                ->groupBy([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.seller_id',
                    'products.category_id',
                    'products.price',
                    'products.image',
                    'products.status',
                    'products.stock',
                    'products.created_at',
                    'products.description'
                ])
                ->orderByDesc('total_sold')
                ->limit(8)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm bán chạy thành công',
                'data' => $products
            ]);
        } catch (Exception $e) {
            Log::error('Error getting best selling products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm bán chạy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recently viewed products by customer
     */
    public function getRecentlyViewedProducts(string $customerId)
    {
        try {
            $products = Product::with([
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }
            ])
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.seller_id',
                    'products.category_id',
                    'products.price',
                    'products.image',
                    'products.status',
                    'products.stock',
                    'products.created_at',
                    'products.description'
                ])
                ->join('viewed_products', 'products.id', '=', 'viewed_products.product_id')
                ->where('viewed_products.user_id', $customerId)
                ->where('products.status', 1)
                ->where('products.stock', '>', 0)
                ->orderBy('viewed_products.viewed_at', 'desc')
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm đã xem thành công',
                'data' => $products
            ]);
        } catch (Exception $e) {
            Log::error('Error getting recently viewed products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm đã xem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductDetailBySlug(string $slug)
    {
        try {
            // Tìm sản phẩm với slug và các relationship cần thiết
            $product = Product::with([
                'category' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'colors:id,product_id,color_value,color_code,image',
                'sizes:id,product_id,size_value,price',
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                },
                'profileShop'
            ])
                ->where('slug', $slug)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin chi tiết sản phẩm thành công',
                'data' => $product
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
                'error' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin chi tiết sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get related products by category
     */
    public function getRelatedProducts(string $productId)
    {
        try {
            // Lấy category_id của sản phẩm hiện tại
            $currentProduct = Product::findOrFail($productId);
            $categoryId = $currentProduct->category_id;

            // Lấy các sản phẩm cùng danh mục
            $products = Product::with([
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }
            ])
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.seller_id',
                    'products.category_id',
                    'products.price',
                    'products.image',
                    'products.status',
                    'products.stock',
                    'products.created_at',
                    'products.description'
                ])
                ->where('products.category_id', $categoryId)
                ->where('products.id', '!=', $productId) // Loại trừ sản phẩm hiện tại
                ->where('products.status', 1)
                ->where('products.stock', '>', 0)
                ->latest()
                ->limit(8)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm liên quan thành công',
                'data' => $products
            ]);
        } catch (Exception $e) {
            Log::error('Error getting related products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm liên quan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductsBySameSeller(string $productId)
    {
        try {
            // Lấy seller_id của sản phẩm hiện tại
            $currentProduct = Product::findOrFail($productId);
            $sellerId = $currentProduct->seller_id;

            // Lấy các sản phẩm cùng người bán
            $products = Product::with([
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }
            ])
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.seller_id',
                    'products.category_id',
                    'products.price',
                    'products.image',
                    'products.status',
                    'products.stock',
                    'products.created_at',
                    'products.description'
                ])
                ->where('products.seller_id', $sellerId)
                ->where('products.id', '!=', $productId) // Loại trừ sản phẩm hiện tại
                ->where('products.status', 1)
                ->where('products.stock', '>', 0)
                ->latest()
                ->limit(8)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm cùng người bán thành công',
                'data' => $products
            ]);
        } catch (Exception $e) {
            Log::error('Error getting products by same seller: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm cùng người bán',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getAllProducts(Request $request)
    {
        try {
            $perPage = (int)$request->get('per_page', 10); // Số sản phẩm mỗi trang, mặc định là 10

            $query = Product::with([
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }
            ])
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.seller_id',
                    'products.category_id',
                    'products.price',
                    'products.image',
                    'products.status',
                    'products.stock',
                    'products.created_at',
                    'products.description',
                    DB::raw('SUM(COALESCE(order_details.quantity, 0)) as total_sold')
                ])
                ->leftJoin('order_details', 'products.id', '=', 'order_details.product_id')
                ->where('products.status', 1)
                ->where('products.stock', '>', 0)
                ->groupBy([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.seller_id',
                    'products.category_id',
                    'products.price',
                    'products.image',
                    'products.status',
                    'products.stock',
                    'products.created_at',
                    'products.description'
                ])
                ->orderByDesc('total_sold');

            // Thực hiện phân trang
            $products = $query->paginate($perPage)->withQueryString();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công',
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'from' => $products->firstItem(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'to' => $products->lastItem(),
                    'total' => $products->total(),
                    'path' => $request->url(),
                ],
                'links' => [
                    'first' => $products->url(1),
                    'last' => $products->url($products->lastPage()),
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error getting products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchBySeller(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $createdFrom = $request->input('created_from');
        $createdTo = $request->input('created_to');
        $products = Product::searchBySeller($id, $name, $createdFrom, $createdTo);
        return response()->json(['success' => true, 'message' => 'Kết quả tìm kiếm sản phẩm', 'data' => $products], 200);
    }
}
