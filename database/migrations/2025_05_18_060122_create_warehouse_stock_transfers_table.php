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

            $table->foreignId('origin_warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('origin_warehouse_product_id')->constrained('warehouse_products')->onDelete('cascade');

            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->onDelete('cascade');

            $table->unsignedBigInteger('destination_warehouse_product_id');
            $table->foreign('destination_warehouse_product_id', 'wst_dest_wp_fk')
                ->references('id')
                ->on('warehouse_products')
                ->onDelete('cascade'); // name is too long for foreign key constraint

            $table->integer('quantity');
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
