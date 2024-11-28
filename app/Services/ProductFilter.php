<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProductFilter
{
    protected $query;
    protected $filters;

    public function __construct($query, array $filters)
    {
        $this->query = $query;
        $this->filters = $filters;
    }

    public function apply()
    {
        foreach ($this->filters as $filter => $value) {
            if (method_exists($this, $filter) && !empty($value)) {
                $this->$filter($value);
            }
        }
        return $this->query;
    }

    protected function price($value)
    {
        if (isset($value['min'])) {
            $this->query->where('price', '>=', $value['min']);
        }
        if (isset($value['max'])) {
            $this->query->where('price', '<=', $value['max']);
        }
    }

    protected function colors($value)
    {
        $this->query->whereHas('colors', function ($q) use ($value) {
            $q->whereIn('color_code', $value);
        });
    }

    protected function sizes($value)
    {
        $this->query->whereHas('sizes', function ($q) use ($value) {
            $q->whereIn('size_value', $value);
        });
    }

    protected function sort($value)
    {
        $sortBy = $value['by'] ?? 'created_at';
        $sortOrder = $value['order'] ?? 'desc';

        switch ($sortBy) {
            case 'price':
                $this->query->orderBy('price', $sortOrder);
                break;
            case 'name':
                $this->query->orderBy('name', $sortOrder);
                break;
            case 'sold':
                $this->query->leftJoin('order_details', 'products.id', '=', 'order_details.product_id')
                    ->groupBy('products.id')
                    ->orderBy(DB::raw('COALESCE(SUM(order_details.quantity), 0)'), $sortOrder);
                break;
            default:
                $this->query->orderBy('created_at', $sortOrder);
        }
    }
}
