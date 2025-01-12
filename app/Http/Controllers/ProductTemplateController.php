<?php

namespace App\Http\Controllers;

use App\Models\ProductTemplate;
use App\Models\TemplateVariant;
use App\Models\TemplateAttributeValue;
use App\Models\TemplateAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductTemplateController extends Controller
{
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
            'variants.*.attributes' => 'nullable|array'
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
                'image' => $request->input('image'),
            ]);

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
                'variants.attributeValues'
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

    public function index()
    {
        try {
            $templates = ProductTemplate::where('user_id', Auth::id())
                ->latest()
                ->get();

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

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'image' => 'nullable',
            'attributes' => 'nullable|array',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'required_with:variants|string',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.quantity' => 'required_with:variants|integer|min:0',
            'variants.*.image' => 'nullable',
            'variants.*.attributes' => 'required_with:variants|array'
        ]);

        try {
            DB::beginTransaction();

            // Kiểm tra và lấy template
            $template = ProductTemplate::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Cập nhật thông tin template
            $template->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'category_id' => $request->input('category_id'),
                'base_price' => $request->input('base_price'),
                'image' => $request->input('image'),
            ]);

            // Xóa attributes và variants cũ
            $template->attributes()->delete();
            $template->variants()->delete();

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
                        'quantity' => $variantData['quantity'],
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
                'variants.attributeValues'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template cập nhật thành công',
                'data' => $template
            ]);
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

    public function show($id)
    {
        try {
            $template = ProductTemplate::with([
                'category',
                'attributes',
                'variants' => function ($query) {
                    $query->with(['attributeValues.attribute']);
                }
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

            // Format lại attributes
            $formattedAttributes = $template->attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name
                ];
            });

            $response = [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category_id' => $template->category_id,
                'base_price' => $template->base_price,
                'image' => $template->image,
                'category' => $template->category,
                'attributes' => $formattedAttributes,
                'variants' => $formattedVariants
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
                }
            ])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Tạo template mới
            $newTemplate = $originalTemplate->replicate();
            $newTemplate->name = $originalTemplate->name . ' (Copy)';
            $newTemplate->save();

            // Copy attributes và lưu mapping
            $attributesMap = [];
            foreach ($originalTemplate->attributes as $attribute) {
                $newAttribute = $attribute->replicate();
                $newAttribute->product_template_id = $newTemplate->id;
                $newAttribute->save();
                $attributesMap[$attribute->id] = $newAttribute->id;
            }

            // Copy variants và attribute values
            foreach ($originalTemplate->variants as $variant) {
                $newVariant = $variant->replicate();
                $newVariant->template_id = $newTemplate->id;
                $newVariant->save();

                // Copy attribute values cho variant mới
                foreach ($variant->attributeValues as $attributeValue) {
                    $newAttributeValue = TemplateAttributeValue::firstOrCreate([
                        'template_attribute_id' => $attributesMap[$attributeValue->template_attribute_id],
                        'value' => $attributeValue->value,
                    ]);

                    $newVariant->attributeValues()->attach($newAttributeValue->id);
                }
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
