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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete(); // Which company it belongs to
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete(); // Which purchase order it belongs to
            $table->string('number')->unique(); // Unique goods receipt number
            $table->date('date'); // Goods receipt date
            $table->text('notes')->nullable(); // Optional notes
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade'); // Who created it
            $table->enum('status', [
                'pending',              // Initial GR created
                'partially-received',   // Optional
                'in-warehouse',              // Stock moved to warehouse_products
            ])->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('goods_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_order_number')->nullable();
            $table->foreignId('goods_receipt_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_detail_id')->constrained()->onDelete('cascade');
            $table->integer('expected_qty')->default(0);
            $table->integer('received_qty')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('has_serials')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('goods_receipt_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_detail_id')->constrained()->onDelete('cascade');
            $table->string('serial_number')->nullable(); // For serialized products
            $table->string('batch_number')->nullable(); // For batch-controlled items
            $table->date('manufactured_at')->nullable();
            $table->date('expired_at')->nullable(); // Optional, useful for perishables
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('goods_receipt_details');
        Schema::dropIfExists('goods_receipt_serials');
    }
};
