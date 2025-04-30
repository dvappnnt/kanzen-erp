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
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->onDelete('cascade');
            $table->foreignId('origin_warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
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
        Schema::dropIfExists('warehouse_transfers');
    }
};
