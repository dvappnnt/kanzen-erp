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
        Schema::create('warehouse_stock_transfer_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_stock_transfer_id');
            $table->foreign('warehouse_stock_transfer_id', 'wst_transfer_fk')
                ->references('id')
                ->on('warehouse_stock_transfers')
                ->onDelete('cascade');

            $table->unsignedBigInteger('origin_warehouse_product_id');
            $table->foreign('origin_warehouse_product_id', 'wst_origin_wp_fk')
                ->references('id')
                ->on('warehouse_products')
                ->onDelete('cascade'); // name is too long for foreign key constraint

            $table->unsignedBigInteger('destination_warehouse_product_id');
            $table->foreign('destination_warehouse_product_id', 'wst_dest_wp_fk')
                ->references('id')
                ->on('warehouse_products')
                ->onDelete('cascade'); // name is too long for foreign key constraint

            $table->integer('expected_qty')->default(0);
            $table->integer('transferred_qty')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_transfer_details');
    }
};
