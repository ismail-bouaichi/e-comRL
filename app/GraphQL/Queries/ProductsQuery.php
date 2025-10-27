<?php

namespace App\GraphQL\Queries;

use App\Models\Product;
use App\Actions\Product\CalculateProductDiscountAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductsQuery
{
    protected $discountAction;
    
    public function __construct(CalculateProductDiscountAction $discountAction)
    {
        $this->discountAction = $discountAction;
    }
    
    public function __invoke($_, array $args)
    {
        $page = $args['page'] ?? 1;
        $first = $args['first'] ?? 9;
        $sortOption = $args['sort'] ?? 'all';
        $categoryId = $args['category_id'] ?? null;
        $brandId = $args['brand_id'] ?? null;
        $selectedCategories = $args['selectedCategories'] ?? [];
        $selectedBrands = $args['selectedBrands'] ?? [];
        $minPrice = $args['minPrice'] ?? 0;
        $maxPrice = $args['maxPrice'] ?? 10000;
        $searchKey = $args['searchKey'] ?? '';
        
        $cacheKey = "graphql_products_{$page}_{$first}_{$sortOption}_{$categoryId}_{$brandId}_" . md5(
            implode('_', $selectedCategories) . "_" .
            implode('_', $selectedBrands) . "_{$minPrice}_{$maxPrice}_{$searchKey}"
        );
        
        $products = Cache::remember($cacheKey, 600, function() use (
            $sortOption, $page, $first, $categoryId, $brandId, $selectedCategories, $selectedBrands, $minPrice, $maxPrice, $searchKey
        ) {
            $query = Product::with([
                'images' => function ($q) {
                    $q->select('id', 'product_id', 'file_path')->limit(1);
                },
                'category:id,name',
                'brand:id,name',
            ]);
            
            // Apply filters
            // Single category filter (by ID)
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
            
            // Single brand filter (by ID)
            if ($brandId) {
                $query->where('brand_id', $brandId);
            }
            
            // Multiple categories filter (by names)
            if (!empty($selectedCategories)) {
                $query->whereHas('category', function($q) use ($selectedCategories) {
                    $q->whereIn('name', $selectedCategories);
                });
            }
            
            // Multiple brands filter (by names)
            if (!empty($selectedBrands)) {
                $query->whereHas('brand', function($q) use ($selectedBrands) {
                    $q->whereIn('name', $selectedBrands);
                });
            }
            
            $query->whereBetween('price', [$minPrice, $maxPrice]);
            
            if (!empty($searchKey)) {
                $query->where('name', 'like', '%' . $searchKey . '%');
            }
            
            // Apply sorting
            switch ($sortOption) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'new':
                    $query->orderBy('created_at', 'desc');
                    break;
            }
            
            return $query->paginate($first, ['*'], 'page', $page);
        });
        
        // Apply discounts to each product
        $productsCollection = $products->getCollection()->map(function ($product) {
            try {
                return $this->discountAction->execute($product);
            } catch (\Exception $e) {
                // If discount calculation fails, return product as-is
                Log::error('Discount calculation failed for product ' . $product->id, [
                    'error' => $e->getMessage()
                ]);
                return $product;
            }
        });
        
        $products->setCollection($productsCollection);
        
        // Return in GraphQL-compatible format
        return [
            'data' => $products->items(), // Convert to array
            'paginatorInfo' => [
                'count' => $products->count(),
                'currentPage' => $products->currentPage(),
                'firstItem' => $products->firstItem(),
                'hasMorePages' => $products->hasMorePages(),
                'lastItem' => $products->lastItem(),
                'lastPage' => $products->lastPage(),
                'perPage' => $products->perPage(),
                'total' => $products->total(),
            ],
        ];
    }
}
