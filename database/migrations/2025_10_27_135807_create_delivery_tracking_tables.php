<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create delivery_workers table
        Schema::create('delivery_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('phone', 20)->nullable();
            $table->enum('vehicle_type', ['bike', 'car', 'scooter'])->default('bike');
            $table->enum('status', ['available', 'on_delivery', 'offline'])->default('offline');
            $table->foreignId('current_order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status');
        });

        // 2. Create delivery_locations table
        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_worker_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('accuracy')->nullable();
            $table->float('speed')->nullable();
            $table->float('heading')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();
            
            // Indexes for fast queries
            $table->index(['order_id', 'created_at']);
            $table->index(['delivery_worker_id', 'created_at']);
        });

        // 3. Drop old delivery_worker_id column if it exists
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_worker_id')) {
                // Just drop the column (foreign key was already cleaned up)
                $table->dropColumn('delivery_worker_id');
            }
        });

        // 4. Add delivery tracking columns to existing orders table
        Schema::table('orders', function (Blueprint $table) {
            // Add delivery_worker_id with foreign key to delivery_workers table
            $table->foreignId('delivery_worker_id')->nullable()->after('status')->constrained('delivery_workers')->onDelete('set null');
            
            if (!Schema::hasColumn('orders', 'delivery_started_at')) {
                $table->timestamp('delivery_started_at')->nullable()->after('delivery_worker_id');
            }
            
            if (!Schema::hasColumn('orders', 'delivery_completed_at')) {
                $table->timestamp('delivery_completed_at')->nullable()->after('delivery_started_at');
            }
        });
        
        // Add indexes separately (they might not exist)
        Schema::table('orders', function (Blueprint $table) {
            $indexName = 'orders_delivery_worker_id_status_index';
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('orders');
            
            if (!array_key_exists($indexName, $indexes)) {
                $table->index(['delivery_worker_id', 'status']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order (foreign key constraints)
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_worker_id']);
            $table->dropColumn(['delivery_worker_id', 'delivery_started_at', 'delivery_completed_at']);
        });
        
        Schema::dropIfExists('delivery_locations');
        Schema::dropIfExists('delivery_workers');
    }
};
