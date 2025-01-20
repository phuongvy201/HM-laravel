<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);

            // Lấy tất cả danh mục với thông tin parent
            $categories = Category::with(['children', 'parent'])
                ->get()
                ->map(function ($category) {
                    return array_merge($category->toArray(), [
                        'parent_name' => $category->parent ? $category->parent->name : null
                    ]);
                });

            // Thực hiện phân trang
            $page = $request->input('page', 1);
            $total = count($categories);
            $items = array_slice($categories->toArray(), ($page - 1) * $perPage, $perPage);

            return response()->json([
                'success' => true,
                'data' => $items,
                'meta' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hàm đệ quy để xây dựng cây danh mục
     */
    private function buildCategoryTree($categories): array
    {
        $result = [];

        foreach ($categories as $category) {
            $categoryData = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image' => $category->image,
                'description' => $category->description,
                'status' => $category->status,
                'created_by' => $category->created_by,
                'updated_by' => $category->updated_by,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at
            ];

            // Nếu có danh mục con, đệ quy để lấy danh mục con
            if ($category->children && $category->children->count() > 0) {
                $categoryData['children'] = $this->buildCategoryTree($category->children);
            } else {
                $categoryData['children'] = [];
            }

            $result[] = $categoryData;
        }

        return $result;
    }

    /**
     * Lấy danh mục dạng phẳng (flat) với level
     */
    public function getFlatCategories(): JsonResponse
    {
        try {
            $categories = Category::whereNull('parent_id')->get();
            $flatCategories = $this->buildFlatCategoryList($categories);

            return response()->json([
                'success' => true,
                'data' => $flatCategories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hàm đệ quy để xây dựng danh sách phẳng với level
     */
    private function buildFlatCategoryList($categories, $level = 0, &$result = []): array
    {
        foreach ($categories as $category) {
            // Thêm dấu -- để thể hiện level
            $prefix = str_repeat('----', $level);

            $result[] = [
                'id' => $category->id,
                'name' => $prefix . ' ' . $category->name,
                'slug' => $category->slug,
                'image' => $category->image,
                'description' => $category->description,
                'status' => $category->status,
                'created_by' => $category->created_by,
                'updated_by' => $category->updated_by
            ];

            // Nếu có danh mục con thì đệ quy
            if ($category->children && $category->children->count() > 0) {
                $this->buildFlatCategoryList($category->children, $level + 1, $result);
            }
        }

        return $result;
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
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            // Tạo category mới
            $category = new Category();
            $category->name = $data['name'];
            $category->slug = Str::slug($data['name']);
            $category->parent_id = $data['parent_id'] ?? null;
            $category->description = $data['description'] ?? null;
            $category->status = $data['status'] ?? 1;
            $category->created_by =  $data['created_by'] ?? null;
            $category->updated_by =  $data['updated_by'] ?? null;

            $mainImagePath = null;
            if ($request->hasFile('image')) {
                $mainImage = $request->file('image');
                $mainImageName = time() . '_' . $mainImage->getClientOriginalName();
                $mainImage->move(public_path('images/categories'), $mainImageName);
                $mainImagePath = 'images/categories/' . $mainImageName;
            }
            $category->image = $mainImagePath;
            $category->save();
            return response()->json([
                'success' => true,
                'message' => 'Tạo danh mục thành công',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function getCategoryById(string $id): JsonResponse
    {
        try {
            // Tìm danh mục theo ID
            $category = Category::find($id);

            // Kiểm tra danh mục có tồn tại không
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }

            // Trả về thông tin danh mục
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin danh mục thành công',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            // Xử lý lỗi nếu có
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin danh mục',
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
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Lấy danh mục cần cập nhật
            $category = Category::find($id);
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ], 404);
            }

            // Validate dữ liệu đầu vào
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|numeric|exists:categories,id',
                'description' => 'nullable|string',
                'status' => 'nullable|in:0,1',
                'created_by' => 'nullable|integer',
                'updated_by' => 'nullable|integer',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Chuẩn bị dữ liệu cập nhật
            $updateData = [
                'name' => $validatedData['name'],
                'slug' => Str::slug($validatedData['name']),
                'description' => $validatedData['description'] ?? $category->description,
                'status' => $validatedData['status'] ?? $category->status,
                'created_by' => $validatedData['created_by'] ?? $category->created_by,
                'updated_by' => $validatedData['updated_by'] ?? $category->updated_by,
            ];

            // Xử lý parent_id
            if ($request->has('parent_id')) {
                $updateData['parent_id'] = $request->input('parent_id') === '' ? null : $request->input('parent_id');
            }

            // Xử lý cập nhật ảnh
            if ($request->hasFile('image')) {
                // Xóa ảnh cũ nếu tồn tại
                if ($category->image && file_exists(public_path($category->image))) {
                    unlink(public_path($category->image));
                }

                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images/categories'), $imageName);
                $updateData['image'] = 'images/categories/' . $imageName;
            }

            // Cập nhật danh mục
            $category->update($updateData);

            // Load relationship parent cho response
            $category->load('parent');

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật danh mục thành công',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            Log::error('Category update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Cập nhật trạng thái của danh mục
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);

            // Cập nhật trạng thái: chuyển đổi giữa 1 và 2
            $category->status = $category->status == 1 ? 2 : 1;
            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái danh mục thành công',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy 6 danh mục cha
     */
    public function getParentCategories(): JsonResponse
    {
        try {
            $categories = Category::whereNull('parent_id')
                ->where('status', 1)
                ->select([
                    'id',
                    'name',
                    'slug',
                    'image',
                    'description'
                ])
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục cha thành công',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách danh mục cha',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getAllParentCategories(): JsonResponse
    {
        try {
            $categories = Category::whereNull('parent_id')
                ->where('status', 1)
                ->select([
                    'id',
                    'name',
                    'slug',
                    'image',
                    'description'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục cha thành công',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách danh mục cha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy tất cả sản phẩm của category và category con theo slug có phân trang
     */
    public function getProductsByCategory(Request $request, string $slug): JsonResponse
    {
        try {
            // Lấy thông số phân trang từ request
            $perPage = $request->input('per_page', 12); // Mặc định 12 sản phẩm mỗi trang
            $page = $request->input('page', 1);

            // Tìm category theo slug
            $category = Category::where('slug', $slug)->firstOrFail();

            // Lấy tất cả ID của category con (bao gồm cả category hiện tại)
            $categoryIds = collect([$category->id]);

            // Lấy tất cả category con
            $childCategories = Category::where('parent_id', $category->id)->get();
            foreach ($childCategories as $child) {
                $categoryIds->push($child->id);
                // Tiếp tục lấy các category con của category con (nếu có)
                $grandChildren = Category::where('parent_id', $child->id)->pluck('id');
                $categoryIds = $categoryIds->concat($grandChildren);
            }

            // Query builder cho sản phẩm
            $productsQuery = Product::whereIn('category_id', $categoryIds)
                ->with(['category', 'discounts' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }, 'images' => function ($query) {
                    $query->orderBy('created_at', 'asc'); // Sắp xếp hình ảnh theo ngày tạo
                }])
                ->where('status', 1);

            // Tổng số sản phẩm (trước khi phân trang)
            $totalProducts = $productsQuery->count();

            // Thực hiện phân trang
            $products = $productsQuery->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function ($product) {
                    $currentDiscount = $product->discounts->first();
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'description' => $product->description,
                        'price' => $product->price,
                        'main_image' => $product->images->first()->image_url ?? null, // Lấy hình ảnh đầu tiên làm ảnh chính
                        'category_name' => $product->category->name,
                        'category_slug' => $product->category->slug,
                        'sale' => $currentDiscount ? [
                            'discount_value' => $currentDiscount->discount_value,
                            'discount_name' => $currentDiscount->discount_name,
                            'date_begin' => $currentDiscount->date_begin,
                            'date_end' => $currentDiscount->date_end
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách sản phẩm thành công',
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description
                    ],
                    'products' => $products,
                    'pagination' => [
                        'current_page' => (int)$page,
                        'per_page' => (int)$perPage,
                        'total' => $totalProducts,
                        'total_pages' => ceil($totalProducts / $perPage),
                        'has_more' => ($page * $perPage) < $totalProducts
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách category con cấp 1 của một category cha theo slug
     */
    public function getChildCategories(string $slug): JsonResponse
    {
        try {
            // Tìm category cha theo slug
            $parentCategory = Category::where('slug', $slug)->first();

            if (!$parentCategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục'
                ], 404);
            }

            $childCategories = Category::where('parent_id', $parentCategory->id)
                ->where('status', 1)
                ->select([
                    'id',
                    'name',
                    'slug',
                    'image',
                    'description'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách danh mục con thành công',
                'data' => $childCategories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách danh mục con',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
