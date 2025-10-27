<?php

namespace App\GraphQL\Queries;

use App\Models\Order;
use App\Models\DeliveryWorker;
use App\Models\DeliveryLocation;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class OrderTrackingQuery
{
    /**
     * Get comprehensive tracking information for an order
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array
     */
    public function __invoke($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $orderId = $args['order_id'];

        $order = Order::with(['deliveryWorker.user', 'deliveryLocations' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(100);
        }])->findOrFail($orderId);

        // Verify authorization (customer or delivery worker can view)
        $user = $context->user();
        if ($order->customer_id !== $user->id) {
            // Check if user is the assigned delivery worker
            if (!$order->deliveryWorker || $order->deliveryWorker->user_id !== $user->id) {
                throw new \Exception('Unauthorized to view this order tracking');
            }
        }

        $latestLocation = $order->deliveryLocations->first();
        $locationHistory = $order->deliveryLocations;

        return [
            'order' => $order,
            'deliveryWorker' => $order->deliveryWorker,
            'latestLocation' => $latestLocation,
            'locationHistory' => $locationHistory,
        ];
    }
}
