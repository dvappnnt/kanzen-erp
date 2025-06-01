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
        Schema::create('employee_attendance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            $table->date('attendance_date');
            $table->time('time_in')->nullable();
            $table->time('break_out')->nullable();
            $table->time('break_in')->nullable();
            $table->time('time_out')->nullable();

            $table->decimal('total_hours_worked', 5, 2)->nullable(); // e.g., 8.00
            $table->decimal('late_minutes', 5, 2)->default(0);
            $table->decimal('undertime_minutes', 5, 2)->default(0);
            $table->boolean('is_absent')->default(false);

            $table->string('shift_type')->nullable(); // Morning, Night, Split, etc.
            $table->boolean('is_overtime')->default(false);
            $table->decimal('overtime_hours', 5, 2)->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_details');
    }
};
