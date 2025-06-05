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
        Schema::create('employee_employment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');

            $table->enum('employment_status', ['regular', 'probationary', 'contractual', 'part-time', 'intern', 'other']); // e.g. Regular, Contractual, Probationary
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->foreignId('position_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->onDelete('set null'); // Supervisor or Manager name or ID

            $table->decimal('basic_salary', 10, 2)->nullable();
            $table->enum('salary_type', ['monthly', 'semi-monthly', 'weekly', 'daily'])->default('monthly'); // Monthly, Semi-Monthly, Weekly
            $table->string('tax_status')->nullable(); // e.g. Z, S, S1, M1, etc.
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
        Schema::dropIfExists('employee_employment_details');
    }
};
