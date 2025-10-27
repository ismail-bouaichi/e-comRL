<?php

namespace App\Actions\Payment;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWebhookAction
{
    public function execute(string $sessionId): array
    {
        $order = Order::where('session_id', $sessionId)->first();
        
        if (!$order) {
            Log::error('Webhook: Order not found for session ' . $sessionId);
            return [
                'success' => false,
                'message' => 'Order not found',
                'code' => 404,
            ];
        }
        
        // Prevent double-processing
        if ($order->status === Order::STATUS_PAID) {
            return [
                'success' => true,
                'message' => 'Already processed',
                'code' => 200,
            ];
        }
        
        DB::beginTransaction();
        try {
            // Update order status
            $order->status = Order::STATUS_PAID;
            $order->save();
            
            // Decrement stock
            foreach ($order->orderDetails as $detail) {
                $detail->product->decrement('stock_quantity', $detail->quantity);
            }
            
            DB::commit();
            Log::info('Webhook: Order ' . $order->id . ' marked as paid and stock decremented');
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'code' => 200,
                'order' => $order,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook: Failed to process order ' . $order->id . ': ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to process payment',
                'code' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }
}
