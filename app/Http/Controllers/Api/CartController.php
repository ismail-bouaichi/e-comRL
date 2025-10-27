<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\ProductCart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public function index($id)
    {
        $productCarts = ProductCart::where('user_id', $id)
        ->with('product.images') // Eager load the product and its images
        ->get();

    return response()->json($productCarts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'product_id' => 'required',
            'quantity' => 'required',
        ]);

        $productCart = ProductCart::create($request->all());
        return response()->json($productCart, 201);
    }

    
    public function create(Request $request) {
        // Validate the request data
        $validateData = $request->validate([
            'customer_name' => 'required',
            'customer_id' => 'required',
            'cart_items' => 'required|array',
            'address' => 'required',
            'zip_code' => 'required',
            'city' => 'required',
        ]);
    
        // Find an available delivery worker
        $deliveryWorker = User::whereHas('role', function ($query) {
            $query->where('name', 'delivery_worker');
        })
            ->whereDoesntHave('assignedOrders', function ($query) {
                $query->where('status', 'not_complete');
            })
            ->inRandomOrder()
            ->first();
    
        if (!$deliveryWorker) {
            return response()->json(['message' => 'No available delivery worker. Please try again later.'], 500);
        }
        
        
    $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        // Retrieve cart items from the request
        $cartItems = $validateData['cart_items'];
    
        $lineItems = [];
        foreach ($validateData['cart_items'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            if ($product->stock_quantity < $item['quantity']) {
                return response()->json(['error' => 'Insufficient stock for product ' . $product->name], 400);
            }
            $lineItems[] = $this->formatLineItem($product, $product->price, $item['quantity']);
        }
    
    
    
        // Create a new Stripe Checkout session
        $checkout_session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success', [], true)."?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => route('checkout.cancel', [], true),
        ]);
    
        // Create the order
        $order = Order::create([
            'customer_name' => $validateData['customer_name'],
            'customer_id' => $validateData['customer_id'],
            'delivery_worker_id' => $deliveryWorker->id,
            'session_id' => $checkout_session->id,
        ]);
    
        // Iterate through cart items and create order details
        foreach ($cartItems as $item) {
            $product = Product::findOrFail($item['product_id']);
            $totalPrice = $product->price * $item['quantity'];
    
            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'address' => $validateData['address'],
                'zip_code' => $validateData['zip_code'],
                'city' => $validateData['city'],
                'total_price' => $totalPrice,
                'amount' => $item['quantity'],
            ]);
        }
      
        
    
        return response()->json([
            'message' => 'Your Order has been passed Successfully',
            'stripe_url' => $checkout_session->url,
            'session_id' => $checkout_session->id
        ]);
    }

    private function formatLineItem($product, $unitPrice, $quantity) {
        return [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $product->name,
                    'description' => $product->description,
                ],
                'unit_amount' => (int)($unitPrice * 100), // cents per unit
            ],
            'quantity' => $quantity,
        ];
    }
    

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductCart  $productCart
     * @return \Illuminate\Http\Response
     */
    public function show(ProductCart $productCart)
    {
        return response()->json($productCart);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductCart  $productCart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductCart $productCart)
    {
        $request->validate([
            'quantity' => 'required',
        ]);

        $productCart->update($request->all());
        return response()->json($productCart, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductCart  $productCart
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $productCart = ProductCart::find($id);
    
        if ($productCart) {
            $productCart->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['error' => 'Item not found'], 404);
        }
    }
}
