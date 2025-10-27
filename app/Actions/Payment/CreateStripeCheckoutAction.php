<?php

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use Stripe\StripeClient;

class CreateStripeCheckoutAction
{
    public function execute(Order $order, array $products, string $city, string $country)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));
        
        // Build line items from products
        $lineItems = [];
        foreach ($products as $item) {
            $product = Product::findOrFail($item['product_id']);
            $unitPrice = $this->getDiscountedPrice($product, 1);
            $lineItems[] = $this->formatLineItem($product, $unitPrice, $item['quantity']);
        }
        
        // Add shipping cost
        $shippingCost = ShippingZone::calculateShipping($city, $country);
        $lineItems[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Shipping',
                    'description' => 'Shipping cost',
                ],
                'unit_amount' => $shippingCost * 100,
            ],
            'quantity' => 1,
        ];
        
        // Create checkout session
        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success', [], true) . "?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => route('checkout.failed', [], true),
            'metadata' => [
                'order_id' => $order->id,
            ],
        ]);
        
        return [
            'session' => $session,
            'shipping_cost' => $shippingCost,
        ];
    }
    
    private function getDiscountedPrice(Product $product, $quantity)
    {
        $discount = $product->currentDiscount();
        $price = $product->price;
        
        if ($discount) {
            if ($discount->discount_type === 'percentage') {
                $price -= ($price * ($discount->discount_value / 100));
            } else {
                $price -= $discount->discount_value;
            }
        }
        
        return $price * $quantity;
    }
    
    private function formatLineItem($product, $unitPrice, $quantity)
    {
        return [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $product->name,
                    'description' => $product->description,
                ],
                'unit_amount' => $unitPrice * 100,
            ],
            'quantity' => $quantity,
        ];
    }
}
