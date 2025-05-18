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
        Schema::create('warehouse_product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_product_id')->constrained()->onDelete('cascade');
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
        Schema::dropIfExists('warehouse_product_serials');
    }
};
