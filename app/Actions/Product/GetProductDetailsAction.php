<?php

namespace App\Actions\Product;

use App\Models\Product;

class GetProductDetailsAction
{
    protected $discountAction;
    
    public function __construct(CalculateProductDiscountAction $discountAction)
    {
        $this->discountAction = $discountAction;
    }
    
    public function execute(int $productId): array
    {
        $product = Product::with(['images', 'category', 'brand'])
            ->withAvg('ratings as avg_rating', 'rating')
            ->withCount('ratings')
            ->find($productId);
        
        if (!$product) {
            throw new \Exception('Product not found');
        }
        
        // Format rating
        $product->avg_rating = number_format($product->avg_rating, 1);
        
        // Apply discount
        $product = $this->discountAction->execute($product);
        
        // Get related products
        $relatedByCategory = $this->getRelatedProducts($product, 'category_id');
        $relatedByBrand = $this->getRelatedProducts($product, 'brand_id');
        
        return [
            'product' => $product,
            'relatedByCategory' => $relatedByCategory,
            'relatedByBrand' => $relatedByBrand,
        ];
    }
    
    protected function getRelatedProducts(Product $product, string $field, int $limit = 5)
    {
        $related = Product::where($field, $product->$field)
            ->where('id', '!=', $product->id)
            ->with(['images', 'category', 'brand'])
            ->withAvg('ratings as avg_rating', 'rating')
            ->withCount('ratings')
            ->limit($limit)
            ->get();
        
        $related->each(function ($relatedProduct) {
            $relatedProduct->avg_rating = number_format($relatedProduct->avg_rating, 1);
            $this->discountAction->execute($relatedProduct);
        });
        
        return $related;
    }
}
