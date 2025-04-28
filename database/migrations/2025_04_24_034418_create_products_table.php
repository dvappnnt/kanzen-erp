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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('token', 64)->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('country_id')
                ->nullable() // Allow nullable for backward compatibility
                ->constrained('countries') // Set up a foreign key to the `countries` table
                ->nullOnDelete(); // If a country is deleted, set `country_id` to null
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit_of_measure')->nullable();  // e.g. pcs, ml, kg
            $table->string('avatar')->nullable();
            $table->boolean('has_variation')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
