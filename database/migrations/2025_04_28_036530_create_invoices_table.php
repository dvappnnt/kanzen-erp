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
            $table->string('number')->unique(); // Unique invoice number
            $table->enum('type', ['sales-invoice', 'pos-invoice']); // Invoice type
            $table->date('invoice_date'); // Invoice date
            $table->decimal('subtotal', 15, 2)->default(0); // Amount before tax
            $table->decimal('tax', 15, 2)->default(0); // Tax amount
            $table->decimal('total', 15, 2)->default(0); // Total after tax
            $table->string('currency', 5)->default('PHP'); // Currency
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled'])->default('draft'); // Invoice status
            $table->text('notes')->nullable(); // Optional notes
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // Who created it
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
