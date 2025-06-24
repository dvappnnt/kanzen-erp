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
        Schema::create('warehouse_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('transfer_date');

            $table->foreignId('origin_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->enum('status', ['pending', 'for-transfer', 'approved', 'rejected', 'completed', 'partially-transferred', 'fully-transferred', 'cancelled'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_transfers');
    }
};
