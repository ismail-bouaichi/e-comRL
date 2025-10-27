<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    // Drop the foreign key from orders table
    Schema::table('orders', function ($table) {
        $table->dropForeign(['delivery_worker_id']);
    });
    echo "Foreign key dropped\n";
} catch (\Exception $e) {
    echo "Error dropping foreign key: " . $e->getMessage() . "\n";
}

try {
    // Drop the tables
    DB::statement('DROP TABLE IF EXISTS delivery_locations');
    DB::statement('DROP TABLE IF EXISTS delivery_workers');
    echo "Tables dropped successfully\n";
} catch (\Exception $e) {
    echo "Error dropping tables: " . $e->getMessage() . "\n";
}
