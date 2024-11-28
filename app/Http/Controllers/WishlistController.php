<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Tự động tạo wishlist nếu chưa có
        $wishlist = Wishlist::firstOrCreate(
            ['user_id' => $userId]
        );

        $wishlist->load(['items.product' => function ($query) {
            $query->select('id', 'name', 'price', 'image', 'slug', 'stock')
                ->with(['sale' => function ($saleQuery) {
                    $saleQuery->where('status', 1)
                        ->where('date_begin', '<=', now())
                        ->where('date_end', '>=', now());
                }]);
        }]);

        return view('wishlist.index', compact('wishlist'));
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
