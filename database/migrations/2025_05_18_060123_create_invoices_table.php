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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete(); // Link to company
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete(); // Link to customer
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete(); // Link to warehouse
            $table->string('number')->unique(); // Unique invoice number
            $table->enum('type', ['sales-invoice', 'pos-invoice']); // Invoice type
            $table->date('invoice_date'); // Invoice date
            $table->date('due_date')->nullable(); // Due date
            $table->date('payment_date')->nullable(); // Payment date
            $table->decimal('discount_rate', 5, 2)->default(0); // Discount rate
            $table->decimal('discount_amount', 15, 2)->default(0); // Discount amount
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0); // Amount before tax
            $table->decimal('total_amount', 15, 2)->default(0); // Total after tax
            $table->string('currency', 5)->default('PHP'); // Currency
            $table->enum('status', ['draft', 'unpaid', 'partially-paid', 'fully-paid', 'cancelled', 'overdue'])->default('draft'); // Invoice status
            $table->text('notes')->nullable(); // Optional notes
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // Who created it
            $table->boolean('is_credit')->default(false); // If true, it is a credit
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
