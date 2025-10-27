<?php

namespace App\GraphQL\Mutations;

use App\Models\Order;
use App\Models\DeliveryWorker;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AssignDeliveryWorkerMutation
{
    /**
     * Assign a delivery worker to an order
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return \App\Models\Order
     */
    public function __invoke($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];
        $user = $context->user();

        // Verify user is admin (role_id = 1)
        if ($user->role_id !== 1) {
            throw new \Exception('Unauthorized. Only admins can assign delivery workers.');
        }

        $order = Order::findOrFail($input['order_id']);
        $worker = DeliveryWorker::findOrFail($input['delivery_worker_id']);

        // Check if worker is available
        if ($worker->status !== 'available') {
            throw new \Exception('Delivery worker is not available');
        }

        // Assign worker to order
        $order->update([
            'delivery_worker_id' => $worker->id,
            'delivery_started_at' => now(),
        ]);

        // Update worker status
        $worker->update([
            'status' => 'on_delivery',
            'current_order_id' => $order->id,
        ]);

        return $order->fresh(['deliveryWorker.user', 'customer']);
    }
}
