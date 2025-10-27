<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'vehicle_type',
        'status',
        'current_order_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this delivery worker account
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the current order being delivered
     */
    public function currentOrder()
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    /**
     * Get all orders assigned to this worker
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'delivery_worker_id');
    }

    /**
     * Get all location history for this worker
     */
    public function locations()
    {
        return $this->hasMany(DeliveryLocation::class);
    }

    /**
     * Get the most recent location
     */
    public function latestLocation()
    {
        return $this->locations()->latest('created_at')->first();
    }

    /**
     * Check if worker is currently on a delivery
     */
    public function isOnDelivery(): bool
    {
        return $this->status === 'on_delivery';
    }

    /**
     * Check if worker is available for new orders
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
