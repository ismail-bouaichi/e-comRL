<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use App\Models\DeliveryWorker;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryLocationController extends Controller
{
    /**
     * Store a new location update (called by Node.js or directly from worker app)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivery_worker_id' => 'required|exists:delivery_workers,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'timestamp' => 'nullable|date',
        ]);

        // Authorization: Verify worker is assigned to this order
        $order = Order::findOrFail($validated['order_id']);
        
        if ($order->delivery_worker_id !== (int)$validated['delivery_worker_id']) {
            return response()->json([
                'error' => 'Unauthorized: Worker not assigned to this order'
            ], 403);
        }

        // If authenticated, verify user is this worker
        if (auth('api')->check()) {
            $deliveryWorker = DeliveryWorker::where('user_id', auth('api')->id())
                ->where('id', $validated['delivery_worker_id'])
                ->first();

            if (!$deliveryWorker) {
                return response()->json([
                    'error' => 'Unauthorized: You are not this delivery worker'
                ], 403);
            }
        }

        // Add timestamp if not provided
        if (!isset($validated['timestamp'])) {
            $validated['timestamp'] = now();
        }

        // Create location record
        $location = DeliveryLocation::create($validated);

        Log::info('Location update stored', [
            'order_id' => $validated['order_id'],
            'worker_id' => $validated['delivery_worker_id'],
            'lat' => $validated['latitude'],
            'lng' => $validated['longitude'],
        ]);

        return response()->json([
            'success' => true,
            'location' => $location,
        ], 201);
    }

    /**
     * Get current location for an order (for customers)
     * 
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentLocation($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Authorization: Only customer or worker can view
        if (auth('api')->check()) {
            $user = auth('api')->user();
            
            if ($order->customer_id !== $user->id && 
                optional($order->deliveryWorker)->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $location = DeliveryLocation::where('order_id', $orderId)
            ->latest('created_at')
            ->first();

        if (!$location) {
            return response()->json([
                'message' => 'No location data available yet'
            ], 404);
        }

        return response()->json($location);
    }

    /**
     * Get location history for an order
     * 
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationHistory($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Authorization: Only customer or worker can view
        if (auth('api')->check()) {
            $user = auth('api')->user();
            
            if ($order->customer_id !== $user->id && 
                optional($order->deliveryWorker)->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $locations = DeliveryLocation::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->limit(100) // Last 100 locations (to prevent overload)
            ->get();

        return response()->json([
            'order_id' => $orderId,
            'location_count' => $locations->count(),
            'locations' => $locations,
        ]);
    }
}
