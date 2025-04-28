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
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete(); // Which company it belongs to
            $table->string('code')->unique(); // Unique short code like "HR", "ACC", "IT"
            $table->string('name'); // Full name like "Human Resources", "Accounting Department"
            $table->text('description')->nullable(); // Optional notes
            $table->boolean('is_active')->default(true); // Can deactivate a cost center
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
        Schema::dropIfExists('cost_centers');
    }
};
