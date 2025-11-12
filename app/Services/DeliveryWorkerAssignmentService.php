<?php

namespace App\Services;

use App\Models\DeliveryWorker;
use App\Models\Order;

class DeliveryWorkerAssignmentService
{
    /**
     * Find an available delivery worker
     */
    public function findAvailableWorker(): ?DeliveryWorker
    {
        return DeliveryWorker::where('status', 'available')
            ->whereNull('current_order_id')
            ->first();
    }

    /**
     * Find the nearest available delivery worker based on location
     * TODO: Implement distance calculation when GPS tracking is active
     */
    public function findNearestWorker(float $latitude, float $longitude): ?DeliveryWorker
    {
        // For now, just return any available worker
        // In the future, calculate distance from worker's last known location
        return $this->findAvailableWorker();
    }

    /**
     * Assign a delivery worker to an order
     */
    public function assignWorkerToOrder(DeliveryWorker $worker, Order $order): void
    {
        $worker->update([
            'status' => 'on_delivery',
            'current_order_id' => $order->id,
        ]);
    }

    /**
     * Unassign worker from order (when delivery is complete)
     */
    public function completeDelivery(DeliveryWorker $worker): void
    {
        $worker->update([
            'status' => 'available',
            'current_order_id' => null,
        ]);
    }

    /**
     * Get all available workers
     */
    public function getAvailableWorkers()
    {
        return DeliveryWorker::where('status', 'available')
            ->with('user')
            ->get();
    }

    /**
     * Assign worker to order by worker ID
     */
    public function assignWorkerById(int $workerId, int $orderId): bool
    {
        $worker = DeliveryWorker::find($workerId);
        $order = Order::find($orderId);

        if (!$worker || !$order) {
            return false;
        }

        if ($worker->status !== 'available') {
            throw new \Exception('Worker is not available for assignment');
        }

        $this->assignWorkerToOrder($worker, $order);

        // Update order
        $order->update(['delivery_worker_id' => $worker->id]);

        return true;
    }
}
