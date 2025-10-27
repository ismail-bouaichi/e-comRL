<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Delivery Tracking Tables Verification ===\n\n";

// Check delivery_workers table
echo "1. delivery_workers table:\n";
$workerCount = DB::table('delivery_workers')->count();
echo "   - Exists: YES\n";
echo "   - Row count: {$workerCount}\n";
$workerColumns = DB::select('SHOW COLUMNS FROM delivery_workers');
echo "   - Columns: " . implode(', ', array_column($workerColumns, 'Field')) . "\n\n";

// Check delivery_locations table
echo "2. delivery_locations table:\n";
$locationCount = DB::table('delivery_locations')->count();
echo "   - Exists: YES\n";
echo "   - Row count: {$locationCount}\n";
$locationColumns = DB::select('SHOW COLUMNS FROM delivery_locations');
echo "   - Columns: " . implode(', ', array_column($locationColumns, 'Field')) . "\n\n";

// Check orders.delivery_worker_id column
echo "3. orders.delivery_worker_id:\n";
$orderCol = collect(DB::select('SHOW COLUMNS FROM orders'))->where('Field', 'delivery_worker_id')->first();
if ($orderCol) {
    echo "   - Exists: YES\n";
    echo "   - Type: {$orderCol->Type}\n";
    echo "   - Null: {$orderCol->Null}\n";
    echo "   - Key: {$orderCol->Key}\n";
} else {
    echo "   - Exists: NO\n";
}

// Check foreign keys
echo "\n4. Foreign Keys:\n";
$foreignKeys = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('delivery_workers', 'delivery_locations', 'orders') AND REFERENCED_TABLE_NAME IS NOT NULL");
foreach ($foreignKeys as $fk) {
    if (in_array($fk->REFERENCED_TABLE_NAME, ['delivery_workers', 'delivery_locations']) || 
        ($fk->COLUMN_NAME === 'delivery_worker_id' && $fk->CONSTRAINT_NAME === 'orders_delivery_worker_id_foreign')) {
        echo "   - {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
    }
}

echo "\n✅ Migration verification complete!\n";
