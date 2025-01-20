<?php

namespace App\Http\Controllers;

use App\Models\ProductTemplate;
use App\Models\TemplateVariant;
use App\Models\TemplateAttributeValue;
use App\Models\TemplateAttribute;
use App\Models\TemplateImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductTemplateController extends Controller
{
    /**
     * Generate a unique name for uploaded image
     *
     * @param string $originalName
     * @return string
     */
    private function generateUniqueImageName($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $extension;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'image' => 'nullable',
            'attributes' => 'nullable|array',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'nullable|integer|min:0',
            'variants.*.image' => 'nullable',
            'variants.*.attributes' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Tạo template
            $template = ProductTemplate::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'category_id' => $request->input('category_id'),
                'base_price' => $request->input('base_price'),
                'user_id' => Auth::id(),
            ]);

            // Thêm xử lý images
            if ($request->has('images')) {
                foreach ($request->file('images') as $image) {
                    if ($image->isValid()) {
                        $imageName = $this->generateUniqueImageName($image->getClientOriginalName());
                        $image->move(public_path('images/templates'), $imageName);
                        $imagePath = 'images/templates/' . $imageName;

                        TemplateImage::create([
                            'template_id' => $template->id,
                            'url' => $imagePath
                        ]);
                    }
                }
            }

            // Kiểm tra và xử lý attributes nếu có
            $attributesMap = [];
            if ($request->has('attributes') && !empty($request->input('attributes'))) {
                foreach ($request->input('attributes') as $attributeData) {
                    $templateAttribute = TemplateAttribute::create([
                        'product_template_id' => $template->id,
                        'name' => $attributeData['name']
                    ]);
                    $attributesMap[$attributeData['name']] = $templateAttribute->id;
                }
            }

            // Kiểm tra và xử lý variants nếu có
            if ($request->has('variants') && !empty($request->input('variants'))) {
                foreach ($request->input('variants') as $variantData) {
                    $variant = TemplateVariant::create([
                        'template_id' => $template->id,
                        'sku' => $variantData['sku'],
                        'price' => $variantData['price'],
                        'quantity' => $variantData['quantity'] ?? 30,
                        'image' => $variantData['image'] ?? null,
                    ]);

                    // Xử lý attribute values cho variant nếu có attributes
                    if (!empty($variantData['attributes']) && !empty($attributesMap)) {
                        foreach ($variantData['attributes'] as $attributeValue) {
                            if (isset($attributesMap[$attributeValue['attribute_name']])) {
                                $templateAttributeId = $attributesMap[$attributeValue['attribute_name']];

                                $templateAttributeValue = TemplateAttributeValue::firstOrCreate([
                                    'template_attribute_id' => $templateAttributeId,
                                    'value' => $attributeValue['value'],
                                ]);

                                $variant->attributeValues()->attach($templateAttributeValue->id);
                            }
                        }
                    }
                }
            }

            DB::commit();

            // Load relationships cho response
            $template->load([
                'attributes.templateAttributeValues',
                'variants.attributeValues',
                'images'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template tạo thành công',
                'data' => $template
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi tạo template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo template',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'base_price' => 'required|numeric|min:0',
            'image' => 'nullable',
            'attributes' => 'nullable|array',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'nullable|integer|min:0',
            'variants.*.image' => 'nullable|string',
            'variants.*.attributes' => 'nullable|array',
            'images' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Tìm template theo ID
            $template = ProductTemplate::findOrFail($id);

            // Cập nhật thông tin cơ bản của template
            $template->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'base_price' => $request->input('base_price'),
                'user_id' => Auth::id(),
            ]);

            // Xóa tất cả dữ liệu liên quan cũ
            $template->variants()->each(function ($variant) {
                $variant->attributeValues()->detach(); // Xóa quan hệ nhiều-nhiều
                $variant->delete();
            });
            $template->attributes()->each(function ($attribute) {
                $attribute->templateAttributeValues()->delete(); // Xóa attribute values
                $attribute->delete();
            });

            // Thêm lại images mới nếu có
            $template->images()->delete();

            // Thêm hình ảnh mới
            if ($request->has('images')) {
                // Xóa hình ảnh cũ một lần duy nhất
                $template->images()->delete();

                // Xử lý tất cả hình ảnh trong một vòng lặp duy nhất
                foreach ($request->images as $image) {
                    if ($image instanceof UploadedFile) {
                        // Xử lý file mới upload
                        $imageName = time() . '_' . $image->getClientOriginalName();
                        $image->move(public_path('images/templates'), $imageName);

                        TemplateImage::create([
                            'template_id' => $template->id,
                            'url' => 'images/templates/' . $imageName
                        ]);
                    } elseif (is_string($image)) {
                        // Xử lý URL hình ảnh cũ
                        TemplateImage::create([
                            'template_id' => $template->id,
                            'url' => $image
                        ]);
                    }
                }
            }
            // Thêm lại attributes và tạo mapping
            $attributesMap = [];
            if ($request->has('attributes') && !empty($request->input('attributes'))) {
                foreach ($request->input('attributes') as $attributeData) {
                    $templateAttribute = TemplateAttribute::create([
                        'product_template_id' => $template->id,
                        'name' => $attributeData['name']
                    ]);
                    $attributesMap[$attributeData['name']] = $templateAttribute->id;
                }
            }

            // Thêm lại variants và attribute values
            if ($request->has('variants') && !empty($request->input('variants'))) {
                foreach ($request->input('variants') as $variantData) {
                    $variant = TemplateVariant::create([
                        'template_id' => $template->id,
                        'sku' => $variantData['sku'],
                        'price' => $variantData['price'],
                        'quantity' => $variantData['quantity'] ?? 30,
                        'image' => $variantData['image'] ?? null,
                    ]);

                    if (!empty($variantData['attributes'])) {
                        foreach ($variantData['attributes'] as $attributeValue) {
                            if (isset($attributesMap[$attributeValue['attribute_name']])) {
                                $templateAttributeId = $attributesMap[$attributeValue['attribute_name']];

                                $templateAttributeValue = TemplateAttributeValue::firstOrCreate([
                                    'template_attribute_id' => $templateAttributeId,
                                    'value' => $attributeValue['value']
                                ]);

                                $variant->attributeValues()->attach($templateAttributeValue->id);
                            }
                        }
                    }
                }
            }

            DB::commit();

            // Load relationships cho response
            $template->load([
                'attributes.templateAttributeValues',
                'variants.attributeValues',
                'images'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template cập nhật thành công',
                'data' => $template
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật template',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function index()
    {
        try {
            $templates = ProductTemplate::with(['images'])
                ->where('user_id', Auth::id())
                ->latest()
                ->get()
                ->map(function ($template) {
                    // Lấy hình ảnh mới nhất cho mỗi template
                    $defaultImage = $template->images->sortByDesc('created_at')->first();
                    $template->default_image = $defaultImage ? $defaultImage->url : null; // Thêm trường hình ảnh mới nhất
                    return $template;
                });

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách template',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function show($id)
    {
        try {
            $template = ProductTemplate::with([
                'category',
                'attributes',
                'attributes.templateAttributeValues',
                'variants' => function ($query) {
                    $query->with(['attributeValues.attribute']);
                },
                'images'
            ])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Format lại variants
            $formattedVariants = $template->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'quantity' => $variant->quantity,
                    'image' => $variant->image,
                    'attributes' => $variant->attributeValues->map(function ($attributeValue) {
                        return [
                            'attribute_name' => $attributeValue->attribute->name,
                            'value' => $attributeValue->value
                        ];
                    })
                ];
            });

            // Tạo một cấu trúc để dễ dàng so sánh giá
            $variantPrices = [];
            foreach ($formattedVariants as $variant) {
                $variantKey = implode(", ", $variant['attributes']->pluck('value')->toArray());
                $variantPrices[$variantKey] = $variant['price'];
            }

            // Format lại attributes và thêm values
            $formattedAttributes = $template->attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'values' => $attribute->templateAttributeValues->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'value' => $value->value
                        ];
                    })->unique('value')->values()
                ];
            });

            // Lấy tất cả hình ảnh
            $formattedImages = $template->images->map(function ($image) {
                return [
                    'url' => $image->url,
                    'created_at' => $image->created_at
                ];
            });

            $response = [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category_id' => $template->category_id,
                'base_price' => $template->base_price,
                'images' => $formattedImages,
                'category' => $template->category,
                'attributes' => $formattedAttributes,
                'variants' => $formattedVariants,
                'variant_prices' => $variantPrices
            ];

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy thông tin template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Tìm và kiểm tra template có thuộc về user hiện tại
            $template = ProductTemplate::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Xóa template và các dữ liệu liên quan
            $template->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Template đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function duplicate($id)
    {
        try {
            DB::beginTransaction();

            // Tìm template gốc
            $originalTemplate = ProductTemplate::with([
                'attributes',
                'variants' => function ($query) {
                    $query->with(['attributeValues']);
                },
                'images'
            ])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Tạo template mới
            $newTemplate = $originalTemplate->replicate();
            $newTemplate->name = $originalTemplate->name . ' (Copy)';
            $newTemplate->save();

            // Sao chép attributes và lưu mapping
            $attributesMap = [];
            foreach ($originalTemplate->attributes as $attribute) {
                $newAttribute = $attribute->replicate();
                $newAttribute->product_template_id = $newTemplate->id;
                $newAttribute->save();
                $attributesMap[$attribute->id] = $newAttribute->id;
            }

            // Sao chép variants và attribute values
            foreach ($originalTemplate->variants as $variant) {
                $newVariant = $variant->replicate();
                $newVariant->template_id = $newTemplate->id;
                $newVariant->save();

                // Sao chép attribute values cho variant mới
                foreach ($variant->attributeValues as $attributeValue) {
                    $newAttributeValue = TemplateAttributeValue::firstOrCreate([
                        'template_attribute_id' => $attributesMap[$attributeValue->template_attribute_id],
                        'value' => $attributeValue->value,
                    ]);

                    $newVariant->attributeValues()->attach($newAttributeValue->id);
                }
            }

            // Sao chép hình ảnh
            foreach ($originalTemplate->images as $image) {
                $newImage = $image->replicate();
                $newImage->template_id = $newTemplate->id; // Gán ID của template mới
                $newImage->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Template đã được sao chép thành công',
                'data' => $newTemplate
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Không thể sao chép template',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
