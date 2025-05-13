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
        Schema::create('supplier_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_account_id')->nullable()->constrained('company_accounts')->nullOnDelete();
            $table->enum('payment_method', ['cash', 'bank-transfer', 'credit-card', 'gcash', 'check'])->default('cash');
            $table->string('reference_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('supplier_invoice_payments');
    }
};
