<?php

namespace App\GraphQL\Mutations;

use App\Models\DeliveryWorker;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UpdateDeliveryWorkerStatusMutation
{
    /**
     * Update delivery worker status
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return \App\Models\DeliveryWorker
     */
    public function __invoke($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['input'];
        $user = $context->user();

        $worker = DeliveryWorker::findOrFail($input['delivery_worker_id']);

        // Verify the authenticated user is the delivery worker
        if ($worker->user_id !== $user->id) {
            throw new \Exception('Unauthorized to update this delivery worker');
        }

        $worker->update([
            'status' => $input['status'],
        ]);

        return $worker->fresh(['user', 'currentOrder']);
    }
}
