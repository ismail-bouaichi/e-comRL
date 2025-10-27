<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\DB;

class OrderHistoryQuery
{
    public function __invoke($_, array $args)
    {
        $userId = $args['userId'];
        
        $orders = DB::table('orders')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->select(
                'orders.id as order_id',
                'orders.status',
                'orders.created_at as order_date',
                'products.name',
                'order_details.quantity',
                'order_details.total_price',
                'order_details.city',
                'order_details.address',
                'order_details.zip_code'
            )
            ->where('orders.customer_id', $userId)
            ->get();
        
        $groupedOrders = $orders->groupBy('order_id')->map(function ($order) {
            $firstItem = $order->first();
            return [
                'order_id' => $firstItem->order_id,
                'status' => $firstItem->status,
                'order_date' => $firstItem->order_date,
                'products' => $order->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'total' => $item->total_price
                    ];
                })->toArray(),
                'subtotal' => $order->sum('total_price'),
                'shipping' => 17.00,
                'total' => $order->sum('total_price') + 17.00,
                'shipping_address' => [
                    'city' => $firstItem->city,
                    'address' => $firstItem->address,
                    'zip_code' => $firstItem->zip_code
                ],
                'note' => 'Order placed'
            ];
        })->values()->toArray();
        
        return $groupedOrders;
    }
}
