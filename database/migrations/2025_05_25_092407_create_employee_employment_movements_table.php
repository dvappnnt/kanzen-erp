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
        Schema::create('employee_employment_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('movement_date');
            $table->enum('movement_type', ['promotion', 'transfer', 'demotion', 'other']); // Promotion, Transfer, Demotion, etc.
            $table->foreignId('from_position_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->foreignId('to_position_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->string('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_employment_movements');
    }
};
