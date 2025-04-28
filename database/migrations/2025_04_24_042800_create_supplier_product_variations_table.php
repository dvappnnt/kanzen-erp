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
        Schema::create('supplier_product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variation_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();                // SKU for this variation
            $table->string('barcode')->nullable();          // Optional barcode
            $table->string('currency')->default('PHP');
            $table->decimal('price', 10, 2)->default(0);    // Supplier-specific price
            $table->decimal('cost', 10, 2)->default(0);     // Internal cost, if tracked
            $table->integer('lead_time_days')->default(0);  // Days for delivery
            $table->boolean('is_default')->default(false);  // UI default flag
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_product_variations');
    }
};
