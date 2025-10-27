<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class GetBestSellingProductsAction
{
    protected $discountAction;
    
    public function __construct(CalculateProductDiscountAction $discountAction)
    {
        $this->discountAction = $discountAction;
    }
    
    public function execute(int $limit = 10)
    {
        $cacheKey = "best_selling_products_{$limit}";
        
        return Cache::remember($cacheKey, 600, function() use ($limit) {
            $products = Product::with([
                    'images' => function($query) {
                        $query->select('id', 'product_id', 'file_path')->limit(1);
                    },
                    'category:id,name',
                    'brand:id,name'
                ])
                ->select('products.*')
                ->join('order_details', 'products.id', '=', 'order_details.product_id')
                ->groupBy('products.id')
                ->orderByRaw('SUM(order_details.quantity) DESC')
                ->limit($limit)
                ->get();
            
            return $products->map(function ($product) {
                $product = $this->discountAction->execute($product);
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'category' => [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ],
                    'brand' => [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name
                    ],
                    'image' => $product->images->first() ? [
                        'file_path' => $product->images->first()->file_path
                    ] : null,
                    'is_discounted' => $product->is_discounted,
                    'discounted_price' => $product->discounted_price,
                ];
            });
        });
    }
}
