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
        Schema::create('invoice_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_detail_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_product_serial_id')->constrained()->onDelete('cascade');
            $table->date('warranty_expiration')->nullable(); // Warranty period
            $table->boolean('is_expired')->default(false);
            $table->foreignId('replacement_warehouse_product_serial_id')->nullable()->constrained('warehouse_product_serials')->onDelete('cascade');
            $table->boolean('is_replaced')->default(false);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_serials');
    }
};
