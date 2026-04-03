<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products'; // Name of the product table
    protected $fillable = ['product_name', 'description', 'price', 'stock_quantity','brand_id','category_id'];
  

    public function images()
    {
     return $this->hasMany(Image::class);
    }
       public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function scopeSearch($query, $value) {
        $query->where('product_name', 'like', "%{$value}%")
              ->orWhereHas('category', function($query) use ($value) {
                  $query->where('name', 'like', "%{$value}%");
              })
              ->orWhereHas('brand', function($query) use ($value) {
                $query->where('name', 'like', "%{$value}%");
            })
              ->orWhere('price', 'like', "%{$value}%");
    }
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_products')
            ->withPivot('quantity', 'min_stock_threshold', 'shelf_location')
            ->withTimestamps();
    }

    public function warehouseProducts()
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
   
    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'discount_product');
    }

    public function currentDiscount()
    {
        return $this->discounts()->current()->first();
    }   
 
   

    //i added this part
   

     public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }


    public function getFavoriteAttribute(): bool
    {
        return (bool) $this->favorites()->where('product_id', $this->id)->where('user_id', auth()->id())->exists();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopeWithAvgRating($query)
    {
        return $query->withAvg('ratings as avg_rating', 'rating');
    }

}
