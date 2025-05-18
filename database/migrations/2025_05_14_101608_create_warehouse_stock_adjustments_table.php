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
        Schema::create('warehouse_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_product_id')->constrained()->onDelete('cascade');

            $table->integer('system_quantity');    // Quantity in the system before adjustment
            $table->integer('actual_quantity');    // Actual counted quantity
            $table->integer('adjustment');         // Difference (actual - system)
            
            $table->enum('reason', ['damaged', 'lost', 'count-correction', 'other'])->nullable();  // Reason for the adjustment (e.g. Damaged, Lost, Count Correction)
            $table->text('remarks')->nullable();   // Optional remarks

            $table->timestamp('adjusted_at')->nullable(); // Optional timestamp for when adjustment occurred
            $table->foreignId('adjusted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_adjustments');
    }
};
