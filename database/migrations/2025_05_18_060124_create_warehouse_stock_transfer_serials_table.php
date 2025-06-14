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
        Schema::create('warehouse_stock_transfer_serials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_stock_transfer_id');
            $table->foreign('warehouse_stock_transfer_id', 'wst_fk')
                ->references('id')
                ->on('warehouse_stock_transfers')
                ->onDelete('cascade'); // name is too long for foreign key constraint

            $table->unsignedBigInteger('warehouse_stock_transfer_detail_id');
            $table->foreign('warehouse_stock_transfer_detail_id', 'wst_detail_fk')
                ->references('id')
                ->on('warehouse_stock_transfer_details')
                ->onDelete('cascade'); // name is too long for foreign key constraint
            
            $table->string('serial_number')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('manufactured_at')->nullable();
            $table->date('expired_at')->nullable();
            $table->boolean('is_sold')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_transfer_serials');
    }
};
