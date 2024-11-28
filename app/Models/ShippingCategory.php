<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ShippingCategory extends Model
{
    protected $fillable = [
        'category_id',
        'base_rate',
        'additional_rate'
    ];

    /**
     * Tính giá vận chuyển dựa trên số lượng sản phẩm
     * 
     * @param int $quantity Số lượng sản phẩm
     * @return float Giá vận chuyển
     */
    public function calculateShippingCost($quantity)
    {
        if ($quantity <= 0) return 0;

        // Phí cơ bản cho sản phẩm đầu tiên
        $cost = $this->base_rate;

        // Cộng thêm phí cho mỗi sản phẩm bổ sung
        if ($quantity > 1) {
            $cost += ($quantity - 1) * $this->additional_rate;
        }

        return $cost;
    }

    /**
     * Quan hệ với bảng categories
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Tính tổng phí vận chuyển cho nhiều sản phẩm theo danh mục
     * 
     * @param array $items Mảng các item với format [['category_id' => x, 'quantity' => y],...]
     * @return float Tổng phí vận chuyển
     */
    public static function calculateTotalShipping($items)
    {
        if (empty($items)) return 0;

        try {
            $totalShipping = 0;
            $itemsByRootCategory = [];

            // Nhóm các sản phẩm theo danh mục gốc
            foreach ($items as $item) {
                $category = Category::with('ancestors')->find($item['category_id']);
                if (!$category) continue;

                // Tìm danh mục gốc
                $rootCategory = $category->ancestors
                    ->where('parent_id', null)
                    ->first() ?? $category;

                if (!isset($itemsByRootCategory[$rootCategory->id])) {
                    $itemsByRootCategory[$rootCategory->id] = [
                        'category_id' => $rootCategory->id,
                        'quantity' => 0
                    ];
                }
                $itemsByRootCategory[$rootCategory->id]['quantity'] += $item['quantity'];
            }

            // Tính phí vận chuyển
            $isFirstCategory = true;
            foreach ($itemsByRootCategory as $rootCategoryData) {
                $shippingCategory = self::where('category_id', $rootCategoryData['category_id'])->first();
                if (!$shippingCategory) continue;

                if ($isFirstCategory) {
                    // Với mỗi danh mục, tính base_rate cho sản phẩm đầu tiên
                    foreach ($itemsByRootCategory as $data) {
                        $shipping = self::where('category_id', $data['category_id'])->first();
                        if ($shipping) {
                            $totalShipping += $shipping->base_rate;
                        }
                    }
                    $isFirstCategory = false;
                }

                // Tính additional_rate cho các sản phẩm thêm trong cùng danh mục
                if ($rootCategoryData['quantity'] > 1) {
                    $totalShipping += ($rootCategoryData['quantity'] - 1) * $shippingCategory->additional_rate;
                }
            }

            return $totalShipping;
        } catch (\Exception $e) {
            Log::error('Error calculating total shipping: ' . $e->getMessage());
            return 0;
        }
    }
}
