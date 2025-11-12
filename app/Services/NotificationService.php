<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\DeliveryWorker;
use MBarlow\Megaphone\Types\Important;

class NotificationService
{
    /**
     * Notify about a new order creation
     */
    public function notifyOrderCreated(Order $order, ?DeliveryWorker $deliveryWorker = null): void
    {
        $this->notifyAdmins($order);
        
        if ($deliveryWorker) {
            $this->notifyDeliveryWorker($deliveryWorker, $order);
        }
    }

    /**
     * Notify all admins about a new order
     */
    private function notifyAdmins(Order $order): void
    {
        $admins = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->get();

        $notification = new Important(
            'New Order Placed',
            "A new order has been placed by {$order->first_name} {$order->last_name}.",
            $this->getOrderUrl($order->id)
        );

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }
    }

    /**
     * Notify delivery worker about new assignment
     */
    private function notifyDeliveryWorker(DeliveryWorker $worker, Order $order): void
    {
        if (!$worker->user) {
            return;
        }

        $notification = new Important(
            'New Delivery Assignment',
            "You have been assigned to deliver order #{$order->id}",
            $this->getOrderUrl($order->id)
        );

        $worker->user->notify($notification);
    }

    /**
     * Notify customer about order status change
     */
    public function notifyOrderStatusChange(Order $order, string $newStatus): void
    {
        $customer = User::find($order->customer_id);
        
        if (!$customer) {
            return;
        }

        $statusMessages = [
            'paid' => 'Your order has been confirmed and is being prepared.',
            'onProgress' => 'Your order is out for delivery!',
            'delivered' => 'Your order has been delivered. Thank you for your purchase!',
            'canceled' => 'Your order has been canceled.',
        ];

        $message = $statusMessages[$newStatus] ?? "Your order status has been updated to: {$newStatus}";

        $notification = new Important(
            'Order Status Update',
            $message,
            $this->getOrderUrl($order->id)
        );

        $customer->notify($notification);
    }

    /**
     * Notify when delivery worker is assigned to an order
     */
    public function notifyWorkerAssignment(DeliveryWorker $worker, Order $order): void
    {
        $this->notifyDeliveryWorker($worker, $order);
    }

    /**
     * Get the order URL for notifications
     */
    private function getOrderUrl(int $orderId): string
    {
        return config('app.url') . '/orders/' . $orderId;
    }
}
