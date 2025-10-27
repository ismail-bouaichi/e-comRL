<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Order;
use App\Models\DeliveryWorker;
use App\Models\DeliveryLocation;
use Illuminate\Support\Facades\DB;

echo "=== Testing Delivery Tracking Models ===\n\n";

try {
    DB::beginTransaction();

    // 1. Create a test user for delivery worker
    echo "1. Creating test user...\n";
    $user = User::create([
        'name' => 'Test Delivery Driver',
        'email' => 'driver@test.com',
        'password' => bcrypt('password'),
        'role_id' => 2, // delivery_worker role
    ]);
    echo "   ✅ User created: ID {$user->id}\n\n";

    // 2. Create a delivery worker
    echo "2. Creating delivery worker...\n";
    $worker = DeliveryWorker::create([
        'user_id' => $user->id,
        'phone' => '+1234567890',
        'vehicle_type' => 'bike',
        'status' => 'available',
    ]);
    echo "   ✅ DeliveryWorker created: ID {$worker->id}\n";
    echo "   - Phone: {$worker->phone}\n";
    echo "   - Vehicle: {$worker->vehicle_type}\n";
    echo "   - Status: {$worker->status}\n\n";

    // 3. Test worker relationships
    echo "3. Testing DeliveryWorker relationships...\n";
    echo "   - Worker's User: {$worker->user->name}\n";
    echo "   - Is Available: " . ($worker->isAvailable() ? 'YES' : 'NO') . "\n";
    echo "   - Is On Delivery: " . ($worker->isOnDelivery() ? 'YES' : 'NO') . "\n\n";

    // 4. Create a test order (requires a customer user)
    echo "4. Creating test customer and order...\n";
    $customer = User::create([
        'name' => 'Test Customer',
        'email' => 'customer@test.com',
        'password' => bcrypt('password'),
        'role_id' => 3, // customer role
    ]);
    
    $order = Order::create([
        'customer_id' => $customer->id,
        'delivery_worker_id' => $worker->id,
        'total_price' => 100.00,
        'status' => 'processing',
        'delivery_started_at' => now(),
    ]);
    echo "   ✅ Order created: ID {$order->id}\n";
    echo "   - Total: \${$order->total_price}\n";
    echo "   - Status: {$order->status}\n\n";

    // 5. Update worker with current order
    echo "5. Assigning order to worker...\n";
    $worker->update([
        'current_order_id' => $order->id,
        'status' => 'on_delivery',
    ]);
    $worker->refresh();
    echo "   ✅ Worker updated\n";
    echo "   - Current Order ID: {$worker->current_order_id}\n";
    echo "   - Status: {$worker->status}\n";
    echo "   - Is On Delivery: " . ($worker->isOnDelivery() ? 'YES' : 'NO') . "\n\n";

    // 6. Create delivery locations (simulating GPS tracking)
    echo "6. Creating delivery location updates...\n";
    $locations = [
        ['lat' => 40.7128, 'lng' => -74.0060, 'speed' => 15.5],
        ['lat' => 40.7148, 'lng' => -74.0070, 'speed' => 18.2],
        ['lat' => 40.7168, 'lng' => -74.0080, 'speed' => 20.0],
    ];
    
    foreach ($locations as $index => $loc) {
        sleep(1); // Simulate time passing
        DeliveryLocation::create([
            'order_id' => $order->id,
            'delivery_worker_id' => $worker->id,
            'latitude' => $loc['lat'],
            'longitude' => $loc['lng'],
            'accuracy' => 10.5,
            'speed' => $loc['speed'],
            'heading' => 45.0,
            'timestamp' => now(),
        ]);
        $locationNum = $index + 1;
        echo "   ✅ Location #{$locationNum}: ({$loc['lat']}, {$loc['lng']}) - Speed: {$loc['speed']} km/h\n";
    }
    echo "\n";

    // 7. Test location queries
    echo "7. Testing location queries...\n";
    $latestLocation = $worker->latestLocation();
    echo "   - Worker's Latest Location: ({$latestLocation->latitude}, {$latestLocation->longitude})\n";
    
    $orderLatestLocation = $order->latestDeliveryLocation();
    echo "   - Order's Latest Location: ({$orderLatestLocation->latitude}, {$orderLatestLocation->longitude})\n";
    
    $recentLocations = DeliveryLocation::recent(5)->count();
    echo "   - Recent Locations (last 5 min): {$recentLocations}\n";
    
    $orderLocations = DeliveryLocation::forOrder($order->id)->count();
    echo "   - Total Locations for Order: {$orderLocations}\n\n";

    // 8. Test Order → DeliveryWorker relationship
    echo "8. Testing Order relationships...\n";
    $order->refresh();
    echo "   - Order's Delivery Worker: {$order->deliveryWorker->user->name}\n";
    echo "   - Order's Customer: {$order->customer->name}\n";
    echo "   - Order has {$order->deliveryLocations->count()} location updates\n\n";

    // 9. Complete the delivery
    echo "9. Completing delivery...\n";
    $order->update([
        'status' => 'delivered',
        'delivery_completed_at' => now(),
    ]);
    $worker->update([
        'status' => 'available',
        'current_order_id' => null,
    ]);
    echo "   ✅ Delivery completed\n";
    echo "   - Order Status: {$order->status}\n";
    echo "   - Worker Status: {$worker->status}\n\n";

    // Rollback the transaction (clean up test data)
    DB::rollBack();
    echo "✅ All tests passed! (Changes rolled back)\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
