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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('slug')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->foreignId('country_id')
                ->nullable() // Allow nullable for backward compatibility
                ->constrained('countries') // Set up a foreign key to the `countries` table
                ->nullOnDelete(); // If a country is deleted, set `country_id` to null
            $table->string('landline')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();
            $table->text('avatar')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
