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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete(); // If you have companies
            $table->string('reference_number')->nullable(); // Optional reference no (like invoice/bill number)
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete(); // Optional category like Rent, Utilities
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete(); // Who you paid (vendor/supplier)
            $table->string('payment_method')->nullable(); // Cash, Bank Transfer, Cheque
            $table->decimal('amount', 15, 2)->default(0); // Total expense amount
            $table->string('currency', 5)->default('PHP');
            $table->text('description')->nullable(); // Additional notes
            $table->date('expense_date')->nullable(); // When the expense happened
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // Who recorded it
            $table->text('receipt_attachment')->nullable(); // store path to receipt
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
