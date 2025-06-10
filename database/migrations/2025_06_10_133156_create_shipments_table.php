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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->onDelete('cascade');

            $table->foreignId('courier_id')
                ->nullable()
                ->constrained('couriers')
                ->nullOnDelete();

            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->date('shipment_date')->nullable();
            $table->date('delivered_date')->nullable();

            $table->foreignId('courier_driver_id')
                ->nullable()
                ->constrained('courier_drivers')
                ->nullOnDelete();

            $table->foreignId('courier_vehicle_id')
                ->nullable()
                ->constrained('courier_vehicles')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'for-pickup', 'in-transit', 'delivered'])->default('pending');
            $table->text('file_path')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
