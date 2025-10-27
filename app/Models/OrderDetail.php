<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderDetail extends Model
{
    use HasFactory;


    protected $fillable = [
        'order_id', 'product_id', 'total_price', 'quantity','city', 'address','zip_code',

    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class); 
    }
    // public static function bestSellingProduct()
    // {
        
    //     return Product::select('products.*', DB::raw('SUM(order_details.quantity) as total_quantity'))
    //     ->join('order_details','products.id','=','order_details.product_id')
    //     ->groupBy('products.id')
    //     ->orderBy('total_quantity', 'desc')
    //     ->with('images', 'category', 'discounts')
    //     ->get();
    // }

    
}
