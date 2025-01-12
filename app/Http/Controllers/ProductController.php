<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\Size;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Template;
use App\Models\Type;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = (int)$request->get('per_page', 10);

            // Xây dựng query với relationships cần thiết
            $query = Product::with([
                'seller' => function ($query) {
                    $query->select('id', 'name');
                },
                'category' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'template' => function ($query) {
                    $query->select('id', 'name');
                },
                'profileShop' => function ($query) {
                    $query->select('id', 'owner_id', 'shop_name', 'description', 'logo_url', 'banner_url');
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
                    'template_id',
                    'status',
                    'stock',
                    'created_at',
                    'description'
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('id');

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


    /**
     * Store a newly created resource in storage.
     */


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
                },
                'template' => function ($query) {
                    $query->select('id', 'name');
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
                    'template_id',
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
        try {
            $product = Product::findOrFail($id);

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'template_id' => 'nullable|exists:product_templates,id',
                'image' => 'nullable',
                'status' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate new slug if name changed
            if ($request->name !== $product->name) {
                $slug = Str::slug($request->name);
                $slugCount = Product::where('slug', $slug)
                    ->where('id', '!=', $id)
                    ->count();
                if ($slugCount > 0) {
                    $slug = $slug . '-' . time();
                }
                $product->slug = $slug;
            }
            if ($request->hasFile('image')) {
                // Xóa ảnh cũ nếu tồn tại
                if ($product->image && file_exists(public_path($product->image))) {
                    unlink(public_path($product->image));
                }

                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images/products'), $imageName);
                $product->image = 'images/products/' . $imageName;
            } else {
                // Nếu không có ảnh mới, giữ lại ảnh cũ
                $product->image = $product->getOriginal('image');
            }
            // Update basic information
            $product->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'price' => $request->price,
                'stock' => $request->stock,
                'template_id' => $request->template_id,
                'description' => $request->description,
                'status' => $request->status
            ]);

            // Load category relationship for response
            $product->load(['category:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin sản phẩm thành công',
                'data' => $product
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
                'error' => 'Product not found'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error updating product basic info: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Kiểm tra quyền (chỉ seller của sản phẩm mới được xóa)
            if ($product->seller_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa sản phẩm này!'
                ], 403);
            }

            // Sử dụng phương thức safeDelete
            $product->safeDelete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa sản phẩm thành công!'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm!'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa sản phẩm!',
                'error' => $e->getMessage()
            ], 500);
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
            // Tìm sản phẩm với các relationship cần thiết
            $product = Product::where('slug', $slug)->with([
                'category' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'seller' => function ($query) {
                    $query->select([
                        'id',
                        'name'
                    ]);
                },
                'profileShop' => function ($query) {
                    $query->select([
                        'id',
                        'shop_name',
                        'owner_id',
                        'description',
                        'logo_url',
                        'banner_url'
                    ]);
                },
                'template' => function ($query) {
                    $query->with([
                        'attributes' => function ($query) {
                            $query->select([
                                'id',
                                'product_template_id',
                                'name'
                            ])->with([
                                'templateAttributeValues' => function ($query) {
                                    $query->select([
                                        'id',
                                        'template_attribute_id',
                                        'value'
                                    ]);
                                }
                            ]);
                        },
                        'variants' => function ($query) {
                            $query->select([
                                'id',
                                'template_id',
                                'sku',
                                'price',
                                'image',
                                'quantity'
                            ])->with([
                                'attributeValues' => function ($query) {
                                    $query->select([
                                        'template_attribute_values.id',
                                        'template_attribute_values.template_attribute_id',
                                        'template_attribute_values.value'
                                    ]);
                                }
                            ]);
                        }
                    ])->select([
                        'id',
                        'name',
                        'category_id',
                        'image',
                        'description',
                        'base_price'
                    ]);
                },
                'sale' => function ($query) {
                    $query->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }
            ])->firstOrFail();

            // Tổ chức lại dữ liệu variants
            $variants = [];
            if ($product->template) {
                foreach ($product->template->variants as $variant) {
                    $variantData = [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price' => $variant->price,
                        'image' => $variant->image,
                        'quantity' => $variant->quantity,
                        'attributes' => []
                    ];

                    foreach ($variant->attributeValues as $attributeValue) {
                        $attribute = $product->template->attributes
                            ->first(function ($attr) use ($attributeValue) {
                                return $attr->id === $attributeValue->template_attribute_id;
                            });

                        if ($attribute) {
                            $variantData['attributes'][] = [
                                'name' => $attribute->name,
                                'value' => $attributeValue->value
                            ];
                        }
                    }

                    $variants[] = $variantData;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin chi tiết sản phẩm thành công',
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'seller_id' => $product->seller_id,
                        'description' => $product->description,
                        'image' => $product->image,
                        'base_price' => $product->price,
                        'stock' => $product->stock,
                        'status' => $product->status,
                    ],
                    'template_info' => $product->template ? [
                        'id' => $product->template->id,
                        'name' => $product->template->name,
                        'description' => $product->template->description,
                        'base_price' => $product->template->base_price,
                        'attributes' => $product->template->attributes->map(function ($attr) {
                            return [
                                'name' => $attr->name,
                                'values' => $attr->templateAttributeValues->pluck('value')
                            ];
                        }),
                    ] : null,
                    'variants' => $variants,
                    'category' => [
                        'current' => $product->category,
                        'parent' => $product->category->parent ?? null,
                        'grand_parent' => $product->category->parent ? ($product->category->parent->parent ?? null) : null,
                        'hierarchy' => $this->buildCategoryHierarchy($product->category)
                    ],
                    'pricing' => [
                        'base_price' => $product->price,
                        'discount_info' => $product->sale
                            ? [
                                'discount_value' => $product->sale->discount_value,
                                'discount_name' => $product->sale->discount_name,
                                'date_begin' => $product->sale->date_begin,
                                'date_end' => $product->sale->date_end
                            ]
                            : null,
                        'final_price' => $product->sale
                            ? $product->price * (1 - $product->sale->discount_value / 100)
                            : $product->price
                    ],
                    'seller_info' => [
                        'seller' => [
                            'id' => $product->seller->id,
                            'name' => $product->seller->name,
                            'email' => $product->seller->email ?? null,
                            'phone' => $product->seller->phone ?? null,
                            'avatar' => $product->seller->avatar ?? null,
                        ],
                        'shop' => $product->profileShop ? [
                            'shop_name' => $product->profileShop->shop_name,
                            'description' => $product->profileShop->description,
                            'logo_url' => $product->profileShop->logo_url,
                            'banner_url' => $product->profileShop->banner_url,
                        ] : null
                    ]
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm',
                'error' => 'Product not found'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error getting product details: ' . $e->getMessage());
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
            // Lấy thông tin sản phẩm hiện tại
            $currentProduct = Product::with('category')->findOrFail($productId);

            // Xây dựng query cơ bản
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
                    'products.description'
                ])
                ->where('products.id', '!=', $productId)
                ->where('products.status', 1)
                ->where('products.stock', '>', 0);

            // Lấy danh sách category_ids để tìm sản phẩm liên quan
            $relatedCategoryIds = collect([$currentProduct->category_id]);

            // Thêm category cha nếu có
            if ($currentProduct->category && $currentProduct->category->parent_id) {
                $relatedCategoryIds->push($currentProduct->category->parent_id);
            }

            // Tìm sản phẩm theo thứ tự ưu tiên
            $products = $query->where(function ($q) use ($currentProduct, $relatedCategoryIds) {
                // 1. Sản phẩm cùng danh mục và cùng seller
                $q->where(function ($subQ) use ($currentProduct) {
                    $subQ->where('category_id', $currentProduct->category_id)
                        ->where('seller_id', $currentProduct->seller_id);
                })
                    // 2. Sản phẩm cùng danh mục
                    ->orWhere('category_id', $currentProduct->category_id)
                    // 3. Sản phẩm thuộc danh mục cha
                    ->orWhereIn('category_id', $relatedCategoryIds);
            })
                ->orderBy(DB::raw('CASE 
                WHEN category_id = ' . $currentProduct->category_id . ' AND seller_id = ' . $currentProduct->seller_id . ' THEN 1
                WHEN category_id = ' . $currentProduct->category_id . ' THEN 2
                ELSE 3 
            END'))
                ->orderBy('created_at', 'desc')
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
            $perPage = (int)$request->get('per_page', 10);

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
                ->orderByDesc('products.created_at');

            $products = $query->paginate($perPage)->withQueryString();

            return response()->json([
                'success' => true,
                'message' => 'Get products list successfully',
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
                'message' => 'Error occurred while getting products list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchBySeller(Request $request)
    {
        try {
            $perPage = (int)$request->get('per_page', 10);
            $sellerId = Auth::id();
            $name = $request->input('name');
            $templateId = $request->input('template_id');
            $createdFrom = $request->input('created_from');
            $createdTo = $request->input('created_to');
            $id = $request->input('id');
            $query = Product::with([
                'category' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'template' => function ($query) {
                    $query->select('id', 'name');
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
                    'template_id',
                    'status',
                    'stock',
                    'created_at',
                    'description'
                ])
                ->where('seller_id', $sellerId);

            // Tìm kiếm theo tên
            if ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            }
            if ($templateId) {
                $query->where('template_id', $templateId);
            }

            // Lọc theo ngày tạo
            if ($createdFrom) {
                $query->whereDate('created_at', '>=', $createdFrom);
            }
            if ($createdTo) {
                $query->whereDate('created_at', '<=', $createdTo);
            }
            if ($id) {
                $query->where('id', $id);
            }

            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('created_at', 'desc');

            // Phân trang
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
            Log::error('Error searching seller products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tìm kiếm sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function search(Request $request)
    {
        try {
            $perPage = (int)$request->get('per_page', 10);

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
                    'products.description'
                ])
                ->where('products.status', 1)
                ->where('products.stock', '>', 0);

            // Tìm kiếm theo từ khóa trong tên hoặc mô tả
            if ($request->has('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('products.name', 'like', "%{$keyword}%")
                        ->orWhere('products.description', 'like', "%{$keyword}%");
                });
            }

            // Sắp xếp theo thời gian tạo mới nhất
            $query->orderBy('products.created_at', 'desc');

            $products = $query->paginate($perPage)->withQueryString();

            return response()->json([
                'success' => true,
                'message' => 'Tìm kiếm sản phẩm thành công',
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
            Log::error('Error searching products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tìm kiếm sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function searchAllProducts(Request $request)
    {
        $name = $request->input('name');
        $createdFrom = $request->input('created_from');
        $createdTo = $request->input('created_to');

        $query = Product::query();

        if ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if ($createdFrom) {
            $query->whereDate('created_at', '>=', $createdFrom);
        }
        if ($createdTo) {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        $products = $query->get();

        return response()->json(['success' => true, 'message' => 'Kết quả tìm kiếm tất cả sản phẩm', 'data' => $products], 200);
    }

    /**
     * Copy the specified resource.
     */


    private function generateUniqueImageName($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueName = $fileName . '_' . time() . '_' . uniqid();
        return $uniqueName . '.' . $extension;
    }

    public function copyProduct($id)
    {
        try {
            $originalProduct = Product::findOrFail($id);

            // Tạo slug mới
            $slug = Str::slug($originalProduct->name) . '-copy';
            $slugCount = Product::where('slug', 'like', $slug . '%')->count();
            if ($slugCount > 0) {
                $slug = $slug . '-' . ($slugCount + 1);
            }

            // Copy và lưu hình ảnh mới
            $newImagePath = null;
            if ($originalProduct->image) {
                $originalImagePath = public_path($originalProduct->image);
                if (file_exists($originalImagePath)) {
                    $newFileName = $this->generateUniqueImageName(basename($originalProduct->image));
                    $newImagePath = 'images/products/' . $newFileName;
                    copy($originalImagePath, public_path($newImagePath));
                } else if (filter_var($originalProduct->image, FILTER_VALIDATE_URL)) {
                    $newImagePath = $originalProduct->image;
                }
            }

            // Tạo sản phẩm mới
            $newProduct = $originalProduct->replicate();
            $newProduct->slug = $slug;
            $newProduct->image = $newImagePath;
            $newProduct->save();

            return response()->json([
                'success' => true,
                'message' => 'Sản phẩm đã được sao chép thành công',
                'data' => $newProduct
            ], 201);
        } catch (Exception $e) {
            Log::error('Error copying product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi sao chép sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import products from an Excel file.
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            $file = $request->file('file');
            $fileName = time() . '-' . $file->getClientOriginalName();
            $filePath = public_path('uploads');
            $file->move($filePath, $fileName);

            $spreadsheet = IOFactory::load($filePath . '/' . $fileName);
            $worksheet = $spreadsheet->getActiveSheet();

            // Lấy template_id từ ô A1
            $templateId = $worksheet->getCell('A1')->getValue();
            if (!$templateId) {
                throw new \Exception("Không tìm thấy template_id trong file");
            }

            // Validate và lấy template
            $template = ProductTemplate::findOrFail($templateId);

            // Lấy dữ liệu từ dòng 3 trở đi (bỏ qua dòng template_id và header)
            $sheetData = $worksheet->rangeToArray('A3:F' . $worksheet->getHighestRow(), null, true, true);

            $successCount = 0;
            $errors = [];

            foreach ($sheetData as $index => $row) {
                try {
                    // Kiểm tra dữ liệu bắt buộc (name, price, image)
                    if (empty($row[0]) || empty($row[1]) || empty($row[2])) {
                        throw new \Exception("Thiếu dữ liệu bắt buộc ở dòng " . ($index + 3));
                    }

                    // Tạo slug
                    $baseSlug = Str::slug($row[0]);
                    $slug = $baseSlug;
                    $count = 1;
                    while (Product::where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $count;
                        $count++;
                    }

                    // Xử lý hình ảnh
                    $mainImagePath = null;
                    if (filter_var($row[2], FILTER_VALIDATE_URL)) {
                        $mainImagePath = $row[2];
                    } else {
                        throw new \Exception("URL ảnh không hợp lệ ở dòng " . ($index + 3));
                    }

                    DB::beginTransaction();

                    // Tạo sản phẩm
                    $product = Product::create([
                        'name' => $row[0],
                        'slug' => $slug,
                        'seller_id' => Auth::id(),
                        'category_id' => $template->category_id,
                        'template_id' => $template->id,
                        'price' => $row[1],
                        'image' => $mainImagePath,
                        'description' => $row[3] ?? $template->description,
                        'stock' => $row[4] ?? 0,
                        'status' => 1,
                    ]);

                    DB::commit();
                    $successCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errors[] = "Lỗi ở dòng " . ($index + 3) . ": " . $e->getMessage();
                    Log::error("Error processing row " . ($index + 3) . ": " . $e->getMessage());
                }
            }

            // Xóa file sau khi xử lý xong
            if (file_exists($filePath . '/' . $fileName)) {
                unlink($filePath . '/' . $fileName);
            }

            if ($successCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có sản phẩm nào được thêm thành công',
                    'errors' => $errors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã thêm thành công $successCount sản phẩm",
                'warnings' => count($errors) > 0 ? $errors : null
            ], 200);
        } catch (Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi import',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Thêm sản phẩm bằng template.
     */
    public function addProductByTemplate(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:product_templates,id',
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'image' => 'required',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:0,1,2',
        ]);

        try {
            DB::beginTransaction();

            $template = ProductTemplate::findOrFail($request->template_id);

            // Tạo slug độc nhất
            $baseSlug = Str::slug($request->name);
            $slug = $baseSlug;
            $count = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            // Xử lý hình ảnh với tên độc nhất
            $mainImagePath = null;
            if (filter_var($request->image, FILTER_VALIDATE_URL)) {
                $mainImagePath = $request->image;
            } elseif ($request->hasFile('image')) {
                $mainImage = $request->file('image');
                if ($mainImage->isValid()) {
                    $mainImageName = $this->generateUniqueImageName($mainImage->getClientOriginalName());
                    $mainImage->move(public_path('images/products'), $mainImageName);
                    $mainImagePath = 'images/products/' . $mainImageName;
                }
            }

            // Tạo sản phẩm mới
            $product = Product::create([
                'name' => $request->name,
                'slug' => $slug,
                'seller_id' => Auth::id(),
                'category_id' => $template->category_id,
                'template_id' => $template->id,
                'price' => $request->price ?? $template->base_price,
                'image' => $mainImagePath,
                'description' => $request->description ?? $template->description,
                'stock' => $request->stock,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sản phẩm đã được thêm thành công từ template',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($mainImagePath) && file_exists(public_path($mainImagePath))) {
                unlink(public_path($mainImagePath));
            }
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm sản phẩm từ template',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getProductsBySeller(Request $request)
    {
        try {
            $perPage = (int)$request->get('per_page', 10);
            $sellerId = Auth::id();

            $query = Product::with([
                'category' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'category.parent.parent' => function ($query) {
                    $query->select('id', 'name', 'parent_id');
                },
                'template' => function ($query) {
                    $query->select('id', 'name');
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
                    'template_id',
                    'status',
                    'stock',
                    'created_at',
                    'description'
                ])
                ->where('seller_id', $sellerId)
                ->orderBy('created_at', 'desc');

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
            Log::error('Error getting seller products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function buildCategoryHierarchy($category)
    {
        $hierarchy = [];
        $current = $category;

        // Duyệt ngược từ danh mục hiện tại lên các danh mục cha
        while ($current !== null) {
            array_unshift($hierarchy, $current->name);
            $current = $current->parent;
        }

        // Nối các tên danh mục bằng dấu ">"
        return implode(' > ', $hierarchy);
    }
}
