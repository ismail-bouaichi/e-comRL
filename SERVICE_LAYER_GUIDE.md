# Service Layer + Actions Architecture Guide

## 📚 Overview

This document explains the **Actions + Services** architecture implemented in the e-commerce platform, following Laravel's IoC (Inversion of Control) container best practices.

**Architecture Pattern:**
- **Actions**: Single-purpose operations (one class = one responsibility)
- **Services**: Multi-step orchestration (coordinate multiple Actions)

---

## 🎯 Why Actions + Services?

### **Before (Problems):**
- ❌ Controllers had 100+ lines of complex logic
- ❌ Difficult to test individual features
- ❌ Code duplication across controllers
- ❌ Hard to modify business logic without touching controllers
- ❌ Tight coupling between layers

### **After (Benefits):**
- ✅ Thin controllers (10-30 lines)
- ✅ Easy to test Actions and Services in isolation
- ✅ Reusable Actions across Services
- ✅ Clear separation of concerns (Actions = operations, Services = orchestration)
- ✅ Automatic dependency injection via IoC container
- ✅ Single source of truth (no duplication)

---

## 🏗️ Architecture

```
┌─────────────────┐
│  HTTP Request   │
└────────┬────────┘
         │
┌────────▼────────┐
│   Controller    │  ← Validates input, calls service
└────────┬────────┘
         │
┌────────▼────────────┐
│  Service Layer      │  ← Business logic orchestration
│  (OrderProcessing)  │
└────────┬────────────┘
         │
    ┌────┴────────┬──────────────┬─────────────┐
    │             │              │             │
┌───▼───────┐  ┌─▼──────┐  ┌────▼────┐  ┌────▼────┐
│  Pricing  │  │Notific.│  │Delivery │  │Shipping │
│  Service  │  │Service │  │ Service │  │ Service │
└───┬───────┘  └────────┘  └─────────┘  └─────────┘
    │
┌───▼────────────────┐
│   Actions Layer    │  ← Single-purpose operations
│                    │
│  • CreateStripeCheckoutAction
│  • CalculateProductDiscountAction
│  • SearchProductsAction
│  • ProcessWebhookAction
│  • etc...
└────────────────────┘
```
│              Models & Database                   │
└──────────────────────────────────────────────────┘
```

---

## 📦 Services Created

### **1. OrderProcessingService**
**Purpose:** Main orchestrator for order creation

**Responsibilities:**
- Validate stock availability
- Create order record
- Process Stripe payment
- Create order details
- Coordinate with other services
- Handle transactions

**Usage:**
```php
// In Controller
public function store(Request $request, OrderProcessingService $orderService)
{
    $validatedData = $request->validate([...]);
    $result = $orderService->processOrder($validatedData);
    return response()->json($result);
}
```

**Methods:**
- `processOrder(array $data)` - Main entry point
- `validateStockAvailability(array $products)` - Check inventory
- `createOrder(array $data, $worker)` - Create order record
- `processPayment(Order $order, array $data)` - Handle Stripe
- `createOrderDetails(...)` - Create line items
- `handlePostOrderCreation(...)` - Notifications & emails
- `sendOrderConfirmationEmail(...)` - QR code email

---

### **2. DeliveryWorkerAssignmentService**
**Purpose:** Manage delivery worker assignments

**Responsibilities:**
- Find available workers
- Assign workers to orders
- Update worker status
- Handle delivery completion

**Usage:**
```php
// Auto-inject in any class
public function __construct(DeliveryWorkerAssignmentService $deliveryService)
{
    $worker = $deliveryService->findAvailableWorker();
}
```

**Methods:**
- `findAvailableWorker()` - Get any available worker
- `findNearestWorker($lat, $lng)` - Get nearest (GPS-based)
- `assignWorkerToOrder($worker, $order)` - Assign & update status
- `completeDelivery($worker)` - Mark delivery complete
- `getAvailableWorkers()` - List all available workers
- `assignWorkerById($workerId, $orderId)` - Manual assignment

**Future Enhancements:**
```php
// Strategy pattern for different assignment algorithms
interface AssignmentStrategy {
    public function assign(Order $order): ?DeliveryWorker;
}

class NearestWorkerStrategy implements AssignmentStrategy {...}
class FastestWorkerStrategy implements AssignmentStrategy {...}
class RoundRobinStrategy implements AssignmentStrategy {...}
```

---

### **3. NotificationService**
**Purpose:** Centralized notification management

**Responsibilities:**
- Notify admins about new orders
- Notify delivery workers about assignments
- Notify customers about order status
- Handle all notification channels

**Usage:**
```php
// Inject in services or controllers
public function __construct(NotificationService $notificationService)
{
    $this->notificationService->notifyOrderCreated($order, $worker);
}
```

**Methods:**
- `notifyOrderCreated($order, $worker)` - New order notifications
- `notifyAdmins($order)` - Notify all admins
- `notifyDeliveryWorker($worker, $order)` - Worker assignment
- `notifyOrderStatusChange($order, $status)` - Status updates
- `notifyWorkerAssignment($worker, $order)` - Manual assignments

**Tagged Bindings (Future):**
```php
// In AppServiceProvider
$this->app->tag([
    EmailNotificationChannel::class,
    PusherNotificationChannel::class,
    SMSNotificationChannel::class,
], 'notification.channels');

// Usage
$channels = app()->tagged('notification.channels');
foreach ($channels as $channel) {
    $channel->send($notification);
}
```

---

### **4. PricingService**
**Purpose:** Calculate prices with discounts

**Responsibilities:**
- Apply product discounts
- Calculate order totals
- Generate price breakdowns

**Usage:**
```php
// In services or controllers
public function __construct(PricingService $pricingService)
{
    $price = $pricingService->getDiscountedPrice($product, $quantity);
}
```

**Methods:**
- `getDiscountedPrice($product, $quantity)` - With discounts applied
- `applyDiscount($price, $discount)` - Discount logic
- `calculateOrderTotal($products)` - Sum all items
- `getPriceBreakdown($products, $shipping)` - Detailed breakdown

**Example Response:**
```php
[
    'items' => [
        ['product_id' => 1, 'discounted_total' => 45.00, ...],
        ['product_id' => 2, 'discounted_total' => 30.00, ...],
    ],
    'subtotal' => 75.00,
    'shipping_cost' => 10.00,
    'total_discount' => 15.00,
    'grand_total' => 85.00,
]
```

---

### **5. ShippingCalculationService**
**Purpose:** Calculate shipping costs

**Responsibilities:**
- Zone-based shipping calculation
- Weight-based calculation (future)
- Free shipping eligibility
- Shipping options (standard/express/overnight)

**Usage:**
```php
public function __construct(ShippingCalculationService $shippingService)
{
    $cost = $shippingService->calculateShippingCost($city, $country);
}
```

**Methods:**
- `calculateShippingCost($city, $country)` - Zone-based cost
- `calculateByWeight($weight, $city, $country)` - Weight-based
- `isFreeShippingEligible($orderTotal)` - Check free shipping
- `getAvailableZones()` - List all zones
- `getShippingOptions($city, $country)` - All options

**Extensibility:**
```php
// Tagged bindings for multiple calculators
$this->app->tag([
    ZoneBasedCalculator::class,
    WeightBasedCalculator::class,
    FlatRateCalculator::class,
], 'shipping.calculators');
```

---

## 🎬 Actions Layer

### **What are Actions?**
Actions are **single-purpose classes** that perform one specific operation. Each Action has one `execute()` method.

**Key difference:**
- **Service**: Multiple related methods (orchestration)
- **Action**: One method per class (operation)

---

### **Existing Actions**

#### **Payment Actions** (`app/Actions/Payment/`)

**CreateStripeCheckoutAction**
```php
// Creates Stripe checkout session
// NOW injects CalculateProductDiscountAction (no duplication!)
public function execute(Order $order, array $products, string $city, string $country)
{
    $stripe = new StripeClient(config('services.stripe.secret'));
    
    // Build line items using injected CalculateProductDiscountAction
    foreach ($products as $item) {
        $product = Product::findOrFail($item['product_id']);
        $productWithDiscount = $this->discountAction->execute($product);
        // ...
    }
    
    return ['session' => $session, 'shipping_cost' => $shippingCost];
}
```

**ProcessWebhookAction**
- Handles Stripe webhook events
- Updates order status to 'paid'
- Decrements stock quantities
- Uses DB transactions

**RefundPaymentAction**
- Processes payment refunds via Stripe
- Updates order status

**VerifyPaymentAction**
- Verifies payment status with Stripe
- Used in payment confirmation flows

---

#### **Product Actions** (`app/Actions/Product/`)

**CalculateProductDiscountAction** ⭐ **MOST IMPORTANT**
```php
// Single source of truth for ALL discount calculations
// Used by: PricingService, CreateStripeCheckoutAction, SearchProductsAction
public function execute(Product $product): Product
{
    $currentDiscount = $product->currentDiscount();
    
    if ($currentDiscount) {
        if ($currentDiscount->discount_type === 'percentage') {
            $product->discounted_price = $product->price * (1 - $discount / 100);
        } else {
            $product->discounted_price = $product->price - $discount;
        }
    }
    
    return $product;
}
```

**SearchProductsAction**
- Searches products with caching
- Injects `CalculateProductDiscountAction` to apply discounts
- Returns products with discounted prices

**GetProductDetailsAction**
- Fetches product with relationships
- Applies discounts automatically

**GetBestSellingProductsAction**
- Returns top-selling products
- Used in analytics/homepage

---

### **Actions vs Services: When to Use What?**

| Use Action when: | Use Service when: |
|-----------------|-------------------|
| Single operation (create payment, send email) | Multi-step workflow (order processing) |
| Clear input → output | Needs to coordinate multiple Actions |
| Reusable across services | Orchestrates business logic |
| No orchestration needed | Manages transactions |
| Example: `CalculateProductDiscountAction` | Example: `OrderProcessingService` |

---

### **Dependency Chain Example**

```
OrderController
  ↓
OrderProcessingService (orchestrates)
  ↓
  ├─→ CreateStripeCheckoutAction (Action)
  │     ↓
  │     └─→ CalculateProductDiscountAction (Action)
  │
  ├─→ PricingService (orchestrates)
  │     ↓
  │     └─→ CalculateProductDiscountAction (Action - REUSED!)
  │
  └─→ NotificationService (orchestrates)
```

**Notice:** `CalculateProductDiscountAction` is used by BOTH `CreateStripeCheckoutAction` and `PricingService`. This is the power of Actions - **single source of truth, zero duplication!**

---

## 🔧 IoC Container Features Used

### **1. Automatic Dependency Injection**
```php
// Laravel automatically resolves all dependencies
public function __construct(
    OrderProcessingService $orderService,
    DeliveryWorkerAssignmentService $deliveryService,
    NotificationService $notificationService
) {
    // All injected automatically!
}
```

### **2. Singleton Registration**
```php
// In AppServiceProvider::register()
$this->app->singleton(OrderProcessingService::class);
$this->app->singleton(DeliveryWorkerAssignmentService::class);

// One instance per request lifecycle
```

### **3. Service Debugging (Development Only)**
```php
// Automatically log when services are resolved
if ($this->app->environment('local')) {
    $this->app->resolving(function ($object) {
        if (str_contains(get_class($object), 'App\\Services')) {
            Log::debug('Service Resolved: ' . get_class($object));
        }
    });
}
```

---

## 🚀 Usage Examples

### **Example 1: Create Order (Before & After)**

**BEFORE (Controller had all logic):**
```php
public function store(Request $request) {
    // 150+ lines of:
    // - Stock validation
    // - Order creation
    // - Stripe payment
    // - Worker assignment
    // - Notifications
    // - Email sending
    // - Error handling
}
```

**AFTER (Clean controller):**
```php
public function store(Request $request, OrderProcessingService $orderService)
{
    $validatedData = $request->validate([...]);
    
    try {
        $result = $orderService->processOrder($validatedData);
        return response()->json($result, 201);
    } catch (\Exception $e) {
        Log::error('Order creation failed', ['error' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

---

### **Example 2: Assign Delivery Worker**

```php
// In your DeliveryWorkerController
public function assignToOrder(
    Request $request,
    DeliveryWorkerAssignmentService $deliveryService,
    NotificationService $notificationService
) {
    $validated = $request->validate([
        'worker_id' => 'required|exists:delivery_workers,id',
        'order_id' => 'required|exists:orders,id',
    ]);
    
    try {
        $deliveryService->assignWorkerById(
            $validated['worker_id'],
            $validated['order_id']
        );
        
        $worker = DeliveryWorker::find($validated['worker_id']);
        $order = Order::find($validated['order_id']);
        
        $notificationService->notifyWorkerAssignment($worker, $order);
        
        return response()->json(['message' => 'Worker assigned successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

---

### **Example 3: Calculate Order Total with Shipping**

```php
// In CheckoutController
public function calculateTotal(
    Request $request,
    PricingService $pricingService,
    ShippingCalculationService $shippingService
) {
    $products = $request->input('products');
    $city = $request->input('city');
    $country = $request->input('country');
    
    $subtotal = $pricingService->calculateOrderTotal($products);
    $shippingCost = $shippingService->calculateShippingCost($city, $country);
    
    $isFreeShipping = $shippingService->isFreeShippingEligible($subtotal);
    
    return response()->json([
        'subtotal' => $subtotal,
        'shipping_cost' => $isFreeShipping ? 0 : $shippingCost,
        'total' => $subtotal + ($isFreeShipping ? 0 : $shippingCost),
        'free_shipping' => $isFreeShipping,
    ]);
}
```

---

## 🧪 Testing Benefits

### **Service Testing (Unit Tests)**
```php
// tests/Unit/Services/OrderProcessingServiceTest.php
class OrderProcessingServiceTest extends TestCase
{
    public function test_process_order_creates_order_successfully()
    {
        // Mock dependencies
        $deliveryService = $this->mock(DeliveryWorkerAssignmentService::class);
        $notificationService = $this->mock(NotificationService::class);
        $pricingService = $this->mock(PricingService::class);
        
        // Test the service in isolation
        $orderService = new OrderProcessingService(
            $deliveryService,
            $notificationService,
            $pricingService,
            $createCheckout
        );
        
        $result = $orderService->processOrder($testData);
        
        $this->assertArrayHasKey('order_id', $result);
        $this->assertDatabaseHas('orders', ['id' => $result['order_id']]);
    }
}
```

---

## 📋 Next Steps & Future Enhancements

### **Phase 2: Interface-Based Architecture**
```php
// Create interfaces for flexibility
interface PaymentGatewayInterface {
    public function createCheckout(Order $order): array;
}

class StripePaymentGateway implements PaymentGatewayInterface {...}
class PayPalPaymentGateway implements PaymentGatewayInterface {...}

// Bind in service provider
$this->app->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);

// Switch gateways via config
$this->app->bind(PaymentGatewayInterface::class, function ($app) {
    return match(config('payment.default_gateway')) {
        'stripe' => new StripePaymentGateway(),
        'paypal' => new PayPalPaymentGateway(),
        default => new StripePaymentGateway(),
    };
});
```

### **Phase 3: Event-Driven Architecture**
```php
// Instead of direct notification calls, dispatch events
event(new OrderCreated($order, $worker));

// Listeners handle notifications
class SendOrderNotifications implements ShouldQueue
{
    public function handle(OrderCreated $event)
    {
        // Notifications sent asynchronously
    }
}
```

### **Phase 4: Repository Pattern**
```php
// Abstract database layer
interface OrderRepositoryInterface {
    public function create(array $data): Order;
    public function findById(int $id): ?Order;
}

class EloquentOrderRepository implements OrderRepositoryInterface {...}
class CachedOrderRepository implements OrderRepositoryInterface {...}
```

---

## ⚠️ Common Issues & Solutions

### **Issue 1: "Target class is not instantiable"**
**Cause:** Interface not bound in service provider

**Solution:**
```php
// In AppServiceProvider::register()
$this->app->bind(PaymentGatewayInterface::class, StripeGateway::class);
```

### **Issue 2: Circular Dependency**
**Cause:** Service A depends on Service B, and Service B depends on Service A

**Solution:** Use events or restructure dependencies
```php
// Instead of injecting services into each other
event(new OrderCreated($order));
// Let event listeners handle the rest
```

### **Issue 3: Service not found**
**Cause:** Service not registered in service provider

**Solution:**
```php
// Register all services in AppServiceProvider::register()
$this->app->singleton(YourService::class);
```

---

## 📊 Performance Considerations

### **Singleton vs Bind**
```php
// Singleton - One instance per request (recommended for most services)
$this->app->singleton(OrderProcessingService::class);

// Bind - New instance every time (for stateful services)
$this->app->bind(SessionBasedService::class);
```

### **Lazy Loading**
Services are only instantiated when needed, not on every request.

### **Caching in Services**
```php
public function getAvailableWorkers()
{
    return Cache::remember('available_workers', 60, function () {
        return DeliveryWorker::where('status', 'available')->get();
    });
}
```

---

## ✅ Benefits Summary

1. **Cleaner Controllers** - 10-30 lines instead of 100+
2. **Testable** - Easy to unit test services
3. **Reusable** - Share logic across controllers, Livewire, GraphQL
4. **Maintainable** - Change business logic without touching controllers
5. **Flexible** - Easy to swap implementations (Stripe → PayPal)
6. **Type-Safe** - Full IDE autocomplete and type checking
7. **Auto-Wired** - Laravel IoC handles dependency injection
8. **Debuggable** - Service resolution logging in development

---

**Your controllers are now thin, your services are tested, and your architecture is production-ready! 🚀**
