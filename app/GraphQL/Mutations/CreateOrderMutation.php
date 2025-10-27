<?php

namespace App\GraphQL\Mutations;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Actions\Payment\CreateStripeCheckoutAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Mail\OrderSuccessEmail;

class CreateOrderMutation
{
    protected $createCheckout;
    
    public function __construct(CreateStripeCheckoutAction $createCheckout)
    {
        $this->createCheckout = $createCheckout;
    }
    
    public function __invoke($_, array $args)
    {
        $input = $args['input'];
        
        // Validate stock
        foreach ($input['products'] as $item) {
            $product = Product::lockForUpdate()->findOrFail($item['product_id']);
            if ($product->stock_quantity < $item['quantity']) {
                throw new \Exception('Insufficient stock for product: ' . $product->name);
            }
        }
        
        // Find delivery worker
        $deliveryWorker = User::findAvailableDeliveryWorker();
        if (!$deliveryWorker) {
            throw new \Exception('No available delivery worker. Please try again later.');
        }
        
        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'customer_id' => $input['customer_id'],
                'delivery_worker_id' => $deliveryWorker->id,
                'latitude' => $input['latitude'] ?? null,
                'longitude' => $input['longitude'] ?? null,
            ]);
            
            // Create Stripe checkout
            $checkoutResult = $this->createCheckout->execute(
                $order,
                $input['products'],
                $input['city'],
                $input['country']
            );
            
            // Update order
            $order->update([
                'session_id' => $checkoutResult['session']->id,
                'shipping_cost' => $checkoutResult['shipping_cost'],
            ]);
            
            // Create order details
            foreach ($input['products'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $discountedPrice = $product->price * $item['quantity']; // Add discount logic if needed
                
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'address' => $input['address'],
                    'zip_code' => $input['zip_code'],
                    'city' => $input['city'],
                    'total_price' => $discountedPrice,
                    'quantity' => $item['quantity'],
                ]);
            }
            
            DB::commit();
            
            // Send email with QR code
            $qrCodeData = "order/{$order->id}/customer/{$order->first_name} {$order->last_name}/date/{$order->created_at}";
            $qrCode = QrCode::size(300)->generate($qrCodeData);
            $qrCodeBase64 = base64_encode($qrCode);
            
            Mail::to($input['email'])->send(new OrderSuccessEmail([
                'name' => $input['first_name'],
                'qrCode' => $qrCodeBase64
            ]));
            
            return [
                'message' => 'Your Order has been initiated. Complete payment to confirm.',
                'stripe_url' => $checkoutResult['session']->url,
                'session_id' => $checkoutResult['session']->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
