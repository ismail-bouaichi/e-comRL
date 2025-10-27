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
        Schema::table('products', function (Blueprint $table) {
            $table->index('name');
        
            // For stock management
            $table->index('stock_quantity');
            
            // For timestamp based queries (new products, etc)
            $table->index('created_at');
            
            // Composite indexes for common filter combinations
            $table->index(['category_id', 'price']);
            $table->index(['brand_id', 'price']);
            $table->index(['category_id', 'brand_id', 'price']);
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['stock_quantity']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['category_id', 'price']);
            $table->dropIndex(['brand_id', 'price']);
            $table->dropIndex(['category_id', 'brand_id', 'price']);
        });
    }
};
