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
        Schema::create('courier_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')
                ->nullable()
                ->constrained('couriers')
                ->nullOnDelete();

            $table->enum('type', ['truck', 'van', 'motorcycle'])->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('plate_number')->unique();
            $table->string('color')->nullable();
            $table->year('year')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_vehicles');
    }
};
