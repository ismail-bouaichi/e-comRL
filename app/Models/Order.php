<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use MBarlow\Megaphone\HasMegaphone;

class Order extends Model
{
    use HasFactory ;
    use HasMegaphone;

    // Order status constants
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_ON_PROGRESS = 'onProgress';
    const STATUS_COMPLETE = 'complete';
    const STATUS_CANCELLED = 'cancelled';

    protected $table = 'orders'; // Name of the orders table
    protected $fillable = [
        'first_name','last_name', 'email','phone', 'customer_id', 'delivery_worker_id', 'status','is_assigned','session_id','shipping_cost','latitude', 'longitude', 'delivery_started_at', 'delivery_completed_at'

    ];

    protected $casts = [
        'delivery_started_at' => 'datetime',
        'delivery_completed_at' => 'datetime',
    ];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function deliveryWorker()
    {
        return $this->belongsTo(DeliveryWorker::class, 'delivery_worker_id');
    }

    public function deliveryLocations()
    {
        return $this->hasMany(DeliveryLocation::class);
    }

    public function latestDeliveryLocation()
    {
        return $this->deliveryLocations()->latest('created_at')->first();
    }
    public function calculateTotal()
    {
        return $this->orderDetails->sum('total_price');
    }

    public function scopeSearch($query, $value) {
        $query->where('first_name', 'like', "%{$value}%")
              ->orWhereHas('orderDetails', function($query) use ($value) {
                  $query->where('city', 'like', "%{$value}%");
              })
              ->orWhere('status', 'like', "%{$value}%");
    }

}
