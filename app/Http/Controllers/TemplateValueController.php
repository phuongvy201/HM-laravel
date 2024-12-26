<?php

namespace App\Http\Controllers;

use App\Models\TemplateValue;
use Illuminate\Http\Request;

class TemplateValueController extends Controller
{
    // Hiển thị danh sách template values
    public function index()
    {
        $templateValues = TemplateValue::all();
        return response()->json($templateValues);
    }

    // Tạo mới một template value
    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:templates,id',
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'additional_price' => 'nullable|numeric',
            'image_url' => 'nullable|string',
        ]);

        $templateValue = TemplateValue::create($request->all());
        return response()->json($templateValue, 201);
    }

    // Hiển thị một template value cụ thể
    public function show($id)
    {
        $templateValue = TemplateValue::findOrFail($id);
        return response()->json($templateValue);
    }

    // Cập nhật một template value
    public function update(Request $request, $id)
    {
        $templateValue = TemplateValue::findOrFail($id);
        $templateValue->update($request->all());
        return response()->json($templateValue);
    }

    // Xóa một template value
    public function destroy($id)
    {
        $templateValue = TemplateValue::findOrFail($id);
        $templateValue->delete();
        return response()->json(null, 204);
    }
}
