<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TemplateController extends Controller
{
    // Hiển thị danh sách templates
    public function index()
    {
        // Lấy danh sách templates cùng với các template values
        $templates = Template::with('templateValues')->get();

        $groupedTemplates = [];

        foreach ($templates as $template) {
            // Khởi tạo template nếu chưa có
            if (!isset($groupedTemplates[$template->id])) {
                $groupedTemplates[$template->id] = [
                    'id' => $template->id,
                    'user_id' => $template->user_id,
                    'template_name' => $template->template_name,
                    'description' => $template->description,
                    'category_id' => $template->category_id,
                    'image' => $template->image,
                    'template_values' => []
                ];
            }

            // Nhóm các thuộc tính giống nhau trong template
            foreach ($template->templateValues as $value) {
                $groupedTemplates[$template->id]['template_values'][$value->name][] = [
                    'id' => $value->id,
                    'value' => $value->value,
                    'additional_price' => $value->additional_price,
                    'image_url' => $value->image_url,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at,
                ];
            }
        }

        // Chuyển đổi lại thành mảng để dễ dàng sử dụng
        $groupedTemplates = array_values($groupedTemplates);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách sản phẩm thành công',
            'data' => $groupedTemplates
        ]);
    }

    // Tạo mới một template cùng với template values
    public function store(Request $request)
    {
        // Lấy user_id từ Auth
        $userId = Auth::id();

        // Xử lý hình ảnh
        $mainImagePath = null;
        if ($request->hasFile('image')) {
            $mainImage = $request->file('image');
            $mainImageName = time() . '_' . $mainImage->getClientOriginalName();
            $mainImage->move(public_path('images/templates'), $mainImageName);
            $mainImagePath = 'images/templates/' . $mainImageName;
        }

        // Tạo mới template
        $templateData = [
            'user_id' => $userId,
            'template_name' => $request->input('template_name'),
            'description' => $request->input('description'),
            'category_id' => $request->input('category_id'),
            'value_color' => $request->input('value_color'),
            'image' => $mainImagePath,
        ];
        $template = Template::create($templateData);

        // Kiểm tra nếu có template values trong yêu cầu
        if ($request->has('template_values')) {
            foreach ($request->template_values as $value) {
                // Xử lý hình ảnh cho từng template value
                if (isset($value['image_url'])) {
                    $valueImagePath = null;
                    if ($value['image_url']) {
                        $valueImage = $value['image_url'];
                        $valueImageName = time() . '_' . $valueImage->getClientOriginalName();
                        $valueImage->move(public_path('images/template_values'), $valueImageName);
                        $valueImagePath = 'images/template_values/' . $valueImageName;
                    }
                    $value['image_url'] = $valueImagePath;
                }
                $template->templateValues()->create($value);
            }
        }

        return response()->json($template->load('templateValues'), 201);
    }

    // Hiển thị một template cụ thể
    public function show($id)
    {
        $template = Template::with(['templateValues'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin template thành công',
            'data' => $template
        ]);
    }

    // Cập nhật một template
    public function update(Request $request, $id)
    {
        // Tìm template theo id
        $template = Template::findOrFail($id);

        // Cập nhật thông tin template
        $template->template_name = $request->input('template_name', $template->template_name);
        $template->description = $request->input('description', $template->description);
        $template->category_id = $request->input('category_id', $template->category_id);

        // Xử lý cập nhật hình ảnh chính nếu có
        if ($request->hasFile('image')) {
            $mainImage = $request->file('image');
            $mainImageName = time() . '_' . $mainImage->getClientOriginalName();
            $mainImage->move(public_path('images/templates'), $mainImageName);
            $template->image = 'images/templates/' . $mainImageName;
        }

        $template->save();

        // Cập nhật template values nếu có
        if ($request->has('template_values')) {
            foreach ($request->template_values as $valueData) {
                $value = TemplateValue::find($valueData['id']);
                if ($value) {
                    $value->value = $valueData['value'] ?? $value->value;
                    $value->additional_price = $valueData['additional_price'] ?? $value->additional_price;
                    $value->value_color = $valueData['value_color'] ?? $value->value_color;

                    // Xử lý cập nhật hình ảnh cho từng template value nếu có
                    if (isset($valueData['image_url']) && $valueData['image_url']) {
                        $valueImage = $valueData['image_url'];
                        $valueImageName = time() . '_' . $valueImage->getClientOriginalName();
                        $valueImage->move(public_path('images/template_values'), $valueImageName);
                        $value->image_url = 'images/template_values/' . $valueImageName;
                    }

                    $value->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật template thành công',
            'data' => $template->load('templateValues')
        ], 200);
    }

    // Xóa một template
    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa template thành công',
        ], 204);
    }

    public function copy($id)
    {
        // Tìm template gốc
        $originalTemplate = Template::with('templateValues')->findOrFail($id);

        // Tạo bản sao của template
        $newTemplate = $originalTemplate->replicate();
        $newTemplate->template_name = $originalTemplate->template_name . ' (Copy)';
        $newTemplate->save();

        // Sao chép các template values
        foreach ($originalTemplate->templateValues as $value) {
            $newValue = $value->replicate();
            $newValue->template_id = $newTemplate->id;
            $newValue->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sao chép template thành công',
            'data' => $newTemplate->load('templateValues')
        ], 201);
    }
}
