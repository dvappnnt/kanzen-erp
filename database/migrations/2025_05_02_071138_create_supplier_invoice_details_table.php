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
        Schema::create('supplier_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_product_id')->constrained();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2); // qty * price
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_details');
    }
};
