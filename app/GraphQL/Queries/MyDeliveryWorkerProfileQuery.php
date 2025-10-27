<?php

namespace App\GraphQL\Queries;

use App\Models\DeliveryWorker;
use Illuminate\Support\Facades\Auth;

class MyDeliveryWorkerProfileQuery
{
    /**
     * Get the authenticated user's delivery worker profile
     *
     * @return \App\Models\DeliveryWorker|null
     */
    public function __invoke()
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        return DeliveryWorker::with(['user', 'currentOrder', 'locations' => function ($query) {
            $query->latest()->limit(1);
        }])->where('user_id', $user->id)->first();
    }
}
