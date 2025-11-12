<?php

namespace App\Services;

use App\Models\Product;
use App\Actions\Product\CalculateProductDiscountAction;

class PricingService
{
    protected $discountAction;

    public function __construct(CalculateProductDiscountAction $discountAction)
    {
        $this->discountAction = $discountAction;
    }

    /**
     * Calculate the discounted price for a product
     */
    public function getDiscountedPrice(Product $product, int $quantity): float
    {
        $productWithDiscount = $this->discountAction->execute($product);
        return $productWithDiscount->discounted_price * $quantity;
    }

    /**
     * Calculate total price for multiple products
     */
    public function calculateOrderTotal(array $products): float
    {
        $total = 0;

        foreach ($products as $item) {
            $product = Product::findOrFail($item['product_id']);
            $total += $this->getDiscountedPrice($product, $item['quantity']);
        }

        return $total;
    }

    /**
     * Get price breakdown for an order
     */
    public function getPriceBreakdown(array $products, float $shippingCost = 0): array
    {
        $subtotal = 0;
        $totalDiscount = 0;
        $items = [];

        foreach ($products as $item) {
            $product = Product::findOrFail($item['product_id']);
            $originalPrice = $product->price * $item['quantity'];
            $discountedPrice = $this->getDiscountedPrice($product, $item['quantity']);
            $itemDiscount = $originalPrice - $discountedPrice;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
                'original_total' => $originalPrice,
                'discounted_total' => $discountedPrice,
                'discount_amount' => $itemDiscount,
            ];

            $subtotal += $discountedPrice;
            $totalDiscount += $itemDiscount;
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'total_discount' => $totalDiscount,
            'grand_total' => $subtotal + $shippingCost,
        ];
    }
}
