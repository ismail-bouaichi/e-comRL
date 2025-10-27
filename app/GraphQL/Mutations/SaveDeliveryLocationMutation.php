<?php

namespace App\GraphQL\Mutations;

use App\Models\Order;
use App\Models\DeliveryWorker;
use App\Models\DeliveryLocation;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SaveDeliveryLocationMutation
{
    /**
     * Save a delivery location update
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return \App\Models\DeliveryLocation
     */
    public function __invoke($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];
        $user = $context->user();

        $order = Order::findOrFail($input['order_id']);
        $worker = DeliveryWorker::findOrFail($input['delivery_worker_id']);

        // Verify the worker is assigned to this order
        if ($order->delivery_worker_id !== $worker->id) {
            throw new \Exception('Worker is not assigned to this order');
        }

        // Verify the authenticated user is the delivery worker
        if ($worker->user_id !== $user->id) {
            throw new \Exception('Unauthorized to save location for this order');
        }

        // Create the location record
        $location = DeliveryLocation::create([
            'order_id' => $input['order_id'],
            'delivery_worker_id' => $input['delivery_worker_id'],
            'latitude' => $input['latitude'],
            'longitude' => $input['longitude'],
            'accuracy' => $input['accuracy'] ?? null,
            'speed' => $input['speed'] ?? null,
            'heading' => $input['heading'] ?? null,
            'timestamp' => now(),
        ]);

        return $location->fresh(['order', 'deliveryWorker']);
    }
}
