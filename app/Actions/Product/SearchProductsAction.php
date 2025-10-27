<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class SearchProductsAction
{
    protected $discountAction;
    
    public function __construct(CalculateProductDiscountAction $discountAction)
    {
        $this->discountAction = $discountAction;
    }
    
    public function execute(string $searchKey)
    {
        $cacheKey = "search_products_" . md5($searchKey);
        
        $products = Cache::remember($cacheKey, 600, function() use ($searchKey) {
            return Product::with(['images', 'category', 'discounts', 'brand'])
                ->search($searchKey)
                ->get();
        });
        
        return $this->discountAction->executeMany($products);
    }
}
