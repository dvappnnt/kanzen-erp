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
        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('token', 64)->unique();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_product_detail_id')->constrained()->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('critical_level_qty')->default(0);
            $table->integer('qty')->default(0);
            $table->decimal('price', 15, 2)->default(0)->nullable();
            $table->decimal('last_cost', 15, 2)->default(0)->nullable(); // Last cost from the latest GR
            $table->decimal('average_cost', 15, 2)->default(0)->nullable(); // Weighted average
            $table->boolean('has_serials')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_products');
    }
};
