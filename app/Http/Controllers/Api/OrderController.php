<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Discount;
use Stripe\StripeClient;
use App\Models\OrderDetail;
use App\Models\DeliveryWorker;
use Illuminate\Http\Request;
use App\Mail\OrderSuccessEmail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Actions\Payment\CreateStripeCheckoutAction;
use App\Actions\Payment\ProcessWebhookAction;
use App\Actions\Payment\RefundPaymentAction;
use App\Actions\Payment\VerifyPaymentAction;
use App\Services\OrderProcessingService;
use App\Services\PricingService;



class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, OrderProcessingService $orderService)
    {
        $validatedData = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'customer_id' => 'required',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'address' => 'required',
            'zip_code' => 'required',
            'city' => 'required',
            'country' => 'required',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            $result = $orderService->processOrder($validatedData);
            return response()->json($result, 201);
        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getDiscountedPrice(Product $product, $quantity, PricingService $pricingService)
    {
        return $pricingService->getDiscountedPrice($product, $quantity);
    }

    public function calculateShipping(Request $request)
            {
                $validatedData = $request->validate([
                    'city' => 'required',
                    'country' => 'required',
                ]);

                $shippingCost = ShippingZone::calculateShipping($validatedData['city'], $validatedData['country']);

                return response()->json(['shippingCost' => $shippingCost]);
            }
                
    public function success(Request $request, VerifyPaymentAction $verifyPayment)
    {
        try {
            $sessionId = $request->query('session_id');
            
            if (!$sessionId) {
                return redirect('http://localhost:3000/failed');
            }
            
            $result = $verifyPayment->execute($sessionId);
            
            if (!$result['success']) {
                Log::error('Payment verification failed: ' . $result['message']);
                return redirect('http://localhost:3000/failed');
            }
            
            return redirect('http://localhost:3000/confirmed?id=' . $sessionId);
        } catch (\Throwable $th) {
            Log::error('Payment success error: ' . $th->getMessage());
            return redirect('http://localhost:3000/failed');
        }
    }
    public function generateQrCode(Request $request)
    {
        $sessionId = $request->query('id');
        $order = Order::where('session_id', $sessionId)->first();
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        $qrCodeData = "order/{$order->id}/customer/{$order->customer_name}/date/{$order->created_at}";
        $qrCode = QrCode::size(300)->generate($qrCodeData);
        $qrCodeBase64 = base64_encode($qrCode);
    
        return response()->json(['qrCodeBase64' => $qrCodeBase64, 'order' => $order], 200);
    }


    public function failed()  {

        return redirect('http://localhost:3000/failed');
    }
    
    public function cancel(Request $request, RefundPaymentAction $refundPayment)
    {
        $validatedData = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);
    
        $order = Order::find($validatedData['order_id']);
        $result = $refundPayment->execute($order);
        
        return response()->json([
            'message' => $result['message']
        ], $result['code']);
    }
    
    // ✅ Webhook: The source of truth for payment status
    public function webhook(Request $request, ProcessWebhookAction $processWebhook) {
        $signature = $request->header('Stripe-Signature');
        
        try {
            // ✅ Verify Stripe signature for security
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $signature,
                config('services.stripe.webhook_secret')
            );
            
            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $result = $processWebhook->execute($session->id);
                
                return response()->json([
                    'message' => $result['message']
                ], $result['code']);
            }
            
            return response()->json(['message' => 'Webhook handled'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    
   
    

    /**
     * Display the specified resource.
     */
    public function orderHistory($userId)
    {
        

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
         }),
         'subtotal' => $order->sum('total_price'),
         'shipping' => 17.00, // You might want to make this dynamic
         'total' => $order->sum('total_price'),
         'shipping_address' => [
             'city' => $firstItem->city,
             'address' => $firstItem->address,
             'zip_code' => $firstItem->zip_code
         ],
         'note' => 'new order' // You might want to store this in the database
     ];
 })->values();

 return response()->json($groupedOrders);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
