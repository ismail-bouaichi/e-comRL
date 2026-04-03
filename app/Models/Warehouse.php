<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'zip_code',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouseProducts()
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'warehouse_products')
            ->withPivot('quantity', 'min_stock_threshold', 'shelf_location')
            ->withTimestamps();
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockTransfersFrom()
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function stockTransfersTo()
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
