<?php

namespace App\Actions\Payment;

use App\Models\Order;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class VerifyPaymentAction
{
    public function execute(string $sessionId): array
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $session = $stripe->checkout->sessions->retrieve($sessionId);
            
            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Session not found',
                ];
            }
            
            $order = Order::where('session_id', $sessionId)->first();
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order not found',
                ];
            }
            
            // Update order status if payment was successful
            if ($session->payment_status === 'paid' && $order->status !== Order::STATUS_PAID) {
                $order->status = Order::STATUS_PAID;
                $order->save();
            }
            
            return [
                'success' => true,
                'session' => $session,
                'order' => $order,
                'payment_status' => $session->payment_status,
            ];
        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage(),
            ];
        }
    }
}
