<?php

namespace App\GraphQL\Queries;

use App\Models\Order;
use App\Models\DeliveryLocation;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class DeliveryLocationHistoryQuery
{
    /**
     * Get location history for an order
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function __invoke($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $orderId = $args['order_id'];
        $limit = $args['limit'] ?? 100;

        $order = Order::findOrFail($orderId);

        // Verify authorization (customer or delivery worker can view)
        $user = $context->user();
        if ($order->customer_id !== $user->id) {
            // Check if user is the assigned delivery worker
            if (!$order->deliveryWorker || $order->deliveryWorker->user_id !== $user->id) {
                throw new \Exception('Unauthorized to view this order tracking');
            }
        }

        return DeliveryLocation::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
