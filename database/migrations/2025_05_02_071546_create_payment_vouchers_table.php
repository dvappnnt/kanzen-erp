<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique(); // PV-00001
            $table->date('voucher_date');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade'); // or nullable if paying other entities
            $table->foreignId('invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'cheque', 'bank-transfer', 'e-wallet'])->default('cash');
            
            // Cheque-specific fields (nullable)
            $table->string('cheque_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('cheque_date')->nullable();
            
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'approved', 'released', 'cancelled'])->default('pending');
            
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};
