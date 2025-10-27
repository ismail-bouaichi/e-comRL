<?php

namespace App\Actions\Product;

use App\Models\Product;

class CalculateProductDiscountAction
{
    /**
     * Calculate and apply discount information to a product
     */
    public function execute(Product $product): Product
    {
        $currentDiscount = $product->currentDiscount();
        
        if ($currentDiscount) {
            $product->is_discounted = true;
            $product->discount_name = $currentDiscount->name;
            $product->discount_code = $currentDiscount->code;
            
            if ($currentDiscount->discount_type === 'percentage') {
                $product->discounted_price = $product->price * (1 - $currentDiscount->discount_value / 100);
            } else {
                $product->discounted_price = $product->price - $currentDiscount->discount_value;
            }
        } else {
            $product->is_discounted = false;
            $product->discounted_price = $product->price;
        }
        
        return $product;
    }
    
    /**
     * Apply discount to multiple products
     */
    public function executeMany($products)
    {
        return $products->transform(function ($product) {
            return $this->execute($product);
        });
    }
    
    /**
     * Calculate discounted price without modifying product
     */
    public function calculatePrice(float $price, string $discountType, float $discountValue): float
    {
        if ($discountType === 'percentage') {
            return $price * (1 - $discountValue / 100);
        }
        
        return $price - $discountValue;
    }
}
