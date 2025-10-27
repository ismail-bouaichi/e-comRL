<?php

namespace App\GraphQL\Mutations;

use App\Models\Order;
use App\Actions\Payment\RefundPaymentAction;

class CancelOrderMutation
{
    protected $refundPayment;
    
    public function __construct(RefundPaymentAction $refundPayment)
    {
        $this->refundPayment = $refundPayment;
    }
    
    public function __invoke($_, array $args, $context)
    {
        $order = Order::findOrFail($args['order_id']);
        
        // Verify ownership
        if ($order->customer_id !== $context->user()->id) {
            throw new \Exception('Unauthorized');
        }
        
        $result = $this->refundPayment->execute($order);
        
        return [
            'message' => $result['message']
        ];
    }
}
