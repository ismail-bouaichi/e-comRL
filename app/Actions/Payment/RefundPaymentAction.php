<?php

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\Product;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class RefundPaymentAction
{
    public function execute(Order $order): array
    {
        // Check if already cancelled
        if ($order->status === Order::STATUS_CANCELLED) {
            return [
                'success' => false,
                'message' => 'Order is already canceled.',
                'code' => 400,
            ];
        }
        
        // Process refund if payment was made
        if ($order->status === Order::STATUS_PAID) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                
                // Get payment intent from session
                $session = $stripe->checkout->sessions->retrieve($order->session_id);
                
                if (!$session->payment_intent) {
                    return [
                        'success' => false,
                        'message' => 'No payment found for this order.',
                        'code' => 400,
                    ];
                }
                
                $refund = $stripe->refunds->create([
                    'payment_intent' => $session->payment_intent,
                ]);
                
                if ($refund->status !== 'succeeded') {
                    return [
                        'success' => false,
                        'message' => 'Failed to process refund. Please try again.',
                        'code' => 500,
                    ];
                }
                
                Log::info('Refund processed for order ' . $order->id);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                Log::error('Stripe refund error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Stripe API error: ' . $e->getMessage(),
                    'code' => 500,
                ];
            }
        }
        
        // Update order status
        $order->status = Order::STATUS_CANCELLED;
        $order->save();
        
        // Restore stock
        foreach ($order->orderDetails as $orderDetail) {
            $product = Product::find($orderDetail->product_id);
            $product->increment('stock_quantity', $orderDetail->quantity);
        }
        
        return [
            'success' => true,
            'message' => 'Order has been canceled and payment refunded successfully.',
            'code' => 200,
        ];
    }
}
