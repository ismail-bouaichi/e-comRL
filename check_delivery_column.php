<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check if delivery_worker_id column exists
echo "Does delivery_worker_id column exist? " . (Schema::hasColumn('orders', 'delivery_worker_id') ? 'YES' : 'NO') . "\n";

// Check foreign keys on orders table
$foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_worker_id' AND REFERENCED_TABLE_NAME IS NOT NULL");

echo "Foreign keys on delivery_worker_id:\n";
if (empty($foreignKeys)) {
    echo "  None\n";
} else {
    foreach ($foreignKeys as $fk) {
        echo "  - " . $fk->CONSTRAINT_NAME . "\n";
    }
}
