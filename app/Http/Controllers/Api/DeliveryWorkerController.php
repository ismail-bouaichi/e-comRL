<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryWorker;
use App\Models\Order;
use Illuminate\Http\Request;

class DeliveryWorkerController extends Controller
{
    /**
     * Get authenticated delivery worker's profile
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth('api')->user();
        
        $deliveryWorker = DeliveryWorker::with(['currentOrder', 'latestLocation'])
            ->where('user_id', $user->id)
            ->first();

        if (!$deliveryWorker) {
            return response()->json([
                'error' => 'No delivery worker profile found for this user'
            ], 404);
        }

        return response()->json([
            'delivery_worker' => $deliveryWorker,
        ]);
    }

    /**
     * Update delivery worker status
     * 
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus($id, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:available,on_delivery,offline',
        ]);

        $deliveryWorker = DeliveryWorker::findOrFail($id);

        // Authorization: Only the worker themselves can update status
        if ($deliveryWorker->user_id !== auth('api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $deliveryWorker->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'delivery_worker' => $deliveryWorker,
        ]);
    }

    /**
     * Verify worker is assigned to an order (for Node.js to call)
     * 
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOrderAssignment($orderId)
    {
        $user = auth('api')->user();
        
        $order = Order::with('deliveryWorker')->findOrFail($orderId);

        if (!$order->deliveryWorker || $order->deliveryWorker->user_id !== $user->id) {
            return response()->json([
                'authorized' => false,
                'message' => 'You are not assigned to this order'
            ], 403);
        }

        return response()->json([
            'authorized' => true,
            'delivery_worker_id' => $order->delivery_worker_id,
            'order' => $order,
        ]);
    }

    /**
     * Get all available delivery workers
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableWorkers()
    {
        $workers = DeliveryWorker::with('user')
            ->where('status', 'available')
            ->get();

        return response()->json([
            'available_workers' => $workers,
        ]);
    }

    /**
     * Assign delivery worker to an order
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignToOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_worker_id' => 'required|exists:delivery_workers,id',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $worker = DeliveryWorker::findOrFail($validated['delivery_worker_id']);

        // Check if worker is available
        if ($worker->status !== 'available') {
            return response()->json([
                'error' => 'Worker is not available'
            ], 400);
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

        return response()->json([
            'success' => true,
            'order' => $order->load('deliveryWorker'),
        ]);
    }
}
