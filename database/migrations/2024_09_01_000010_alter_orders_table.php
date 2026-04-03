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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_worker_id')->nullable()->change();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete();
            $table->decimal('shipping_cost', 8, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->string('payment_method')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_worker_id')->nullable(false)->change();
            $table->dropForeign(['shipping_zone_id']);
            $table->dropColumn('shipping_zone_id');
            $table->dropColumn('total_amount');
            $table->dropForeign(['discount_id']);
            $table->dropColumn('discount_id');
            $table->dropColumn('shipping_cost');
            $table->dropColumn('payment_status');
            $table->dropColumn('payment_method');
        });
    }
};
