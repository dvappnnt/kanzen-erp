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
        Schema::create('shipment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->foreignId('invoice_detail_id')->constrained('invoice_details')->onDelete('cascade');

            $table->foreignId('courier_id')
                ->nullable()
                ->constrained('couriers')
                ->nullOnDelete();

            $table->foreignId('courier_driver_id')
                ->nullable()
                ->constrained('courier_drivers')
                ->nullOnDelete();

            $table->foreignId('courier_vehicle_id')
                ->nullable()
                ->constrained('courier_vehicles')
                ->nullOnDelete();

            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->date('shipment_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->integer('qty')->default(0);
            $table->string('destination')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'for-pickup', 'in-transit', 'delivered'])->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_details');
    }
};
