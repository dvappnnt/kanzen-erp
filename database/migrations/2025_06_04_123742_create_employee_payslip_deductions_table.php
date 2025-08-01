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
        Schema::create('employee_payslip_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_payslip_id')->constrained()->onDelete('cascade');
            $table->foreignId('deduction_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payslip_deductions');
    }
};
