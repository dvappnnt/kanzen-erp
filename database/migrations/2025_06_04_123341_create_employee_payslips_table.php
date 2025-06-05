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
        Schema::create('employee_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');

            $table->date('period_start');
            $table->date('period_end');

            // Attendance summary
            $table->decimal('total_hours_worked', 8, 2)->default(0);
            $table->decimal('total_overtime_hours', 8, 2)->default(0);
            $table->decimal('total_late_minutes', 8, 2)->default(0);
            $table->decimal('total_undertime_minutes', 8, 2)->default(0);
            $table->integer('total_days_absent')->default(0);

            // Earnings
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('overtime_pay', 10, 2)->default(0);
            $table->decimal('other_earnings', 10, 2)->default(0);

            // Deductions
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2)->default(0);

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
        Schema::dropIfExists('employee_payslips');
    }
};
