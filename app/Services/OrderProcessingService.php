<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderSuccessEmail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Actions\Payment\CreateStripeCheckoutAction;

class OrderProcessingService
{
    public function __construct(
        private DeliveryWorkerAssignmentService $deliveryService,
        private NotificationService $notificationService,
        private PricingService $pricingService,
        private CreateStripeCheckoutAction $createCheckout
    ) {}

    /**
     * Process a new order with payment and delivery assignment
     */
    public function processOrder(array $validatedData): array
    {
        // Validate stock availability first
        $this->validateStockAvailability($validatedData['products']);

        // Try to find an available delivery worker (optional)
        $deliveryWorker = $this->deliveryService->findAvailableWorker();

        DB::beginTransaction();
        try {
            // Create the order
            $order = $this->createOrder($validatedData, $deliveryWorker);

            // Create Stripe checkout session
            $checkoutResult = $this->processPayment($order, $validatedData);

            // Update order with payment session and shipping cost
            $order->update([
                'session_id' => $checkoutResult['session']->id,
                'shipping_cost' => $checkoutResult['shipping_cost'],
            ]);

            // Create order line items
            $this->createOrderDetails($order, $validatedData['products'], $validatedData);

            DB::commit();

            // Post-transaction operations
            $this->handlePostOrderCreation($order, $deliveryWorker, $validatedData);

            return [
                'message' => 'Your Order has been initiated. Complete payment to confirm.',
                'stripe_url' => $checkoutResult['session']->url,
                'session_id' => $checkoutResult['session']->id,
                'order_id' => $order->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate that all products have sufficient stock
     */
    private function validateStockAvailability(array $products): void
    {
        foreach ($products as $item) {
            $product = Product::lockForUpdate()->findOrFail($item['product_id']);
            
            if ($product->stock_quantity < $item['quantity']) {
                throw new \Exception('Insufficient stock for product: ' . $product->product_name);
            }
        }
    }

    /**
     * Create the order record
     */
    private function createOrder(array $validatedData, $deliveryWorker): Order
    {
        return Order::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'customer_id' => $validatedData['customer_id'],
            'delivery_worker_id' => $deliveryWorker?->id,
            'latitude' => $validatedData['latitude'] ?? null,
            'longitude' => $validatedData['longitude'] ?? null,
        ]);
    }

    /**
     * Process payment through Stripe
     */
    private function processPayment(Order $order, array $validatedData): array
    {
        try {
            return $this->createCheckout->execute(
                $order,
                $validatedData['products'],
                $validatedData['city'],
                $validatedData['country']
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \Exception('Stripe API error: ' . $e->getMessage());
        }
    }

    /**
     * Create order detail records for each product
     */
    private function createOrderDetails(Order $order, array $products, array $validatedData): void
    {
        foreach ($products as $item) {
            $product = Product::findOrFail($item['product_id']);
            $discountedPrice = $this->pricingService->getDiscountedPrice($product, $item['quantity']);

            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'address' => $validatedData['address'],
                'zip_code' => $validatedData['zip_code'],
                'city' => $validatedData['city'],
                'total_price' => $discountedPrice,
                'quantity' => $item['quantity'],
            ]);
        }
    }

    /**
     * Handle operations after order creation (notifications, emails, worker assignment)
     */
    private function handlePostOrderCreation(Order $order, $deliveryWorker, array $validatedData): void
    {
        // Assign delivery worker if available
        if ($deliveryWorker) {
            $this->deliveryService->assignWorkerToOrder($deliveryWorker, $order);
        }

        // Send notifications to admins and delivery worker
        $this->notificationService->notifyOrderCreated($order, $deliveryWorker);

        // Send QR code email to customer
        $this->sendOrderConfirmationEmail($order, $validatedData['email']);
    }

    /**
     * Generate QR code and send confirmation email
     */
    private function sendOrderConfirmationEmail(Order $order, string $email): void
    {
        $qrCodeData = "order/{$order->id}/customer/{$order->first_name} {$order->last_name}/date/{$order->created_at}";
        $qrCode = QrCode::size(300)->generate($qrCodeData);
        $qrCodeBase64 = base64_encode($qrCode);

        Mail::to($email)->send(new OrderSuccessEmail([
            'name' => $order->first_name,
            'qrCode' => $qrCodeBase64
        ]));
    }
}
