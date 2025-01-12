<?php

namespace App\Http\Controllers;

use App\Models\BulkOrder;
use Illuminate\Http\Request;

class BulkOrderController extends Controller
{
    public function index()
    {
        $bulkOrders = BulkOrder::all();
        return view('bulk_orders.index', compact('bulkOrders'));
    }

    public function create()
    {
        return view('bulk_orders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'quantity' => 'required',
            'products' => 'required',
            'name' => 'required',
            'email' => 'required|email',
            'file_path' => 'required|file|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $fileName);
            $filePath = 'uploads/' . $fileName;
        }

        BulkOrder::create([
            'quantity' => $request->input('quantity'),
            'products' => $request->input('products'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'company' => $request->input('company'),
            'phone' => $request->input('phone'),
            'file_path' => $filePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bulk order created successfully.'
        ], 201);
    }

    // Các phương thức khác như show, edit, update, destroy có thể được thêm vào đây
}
