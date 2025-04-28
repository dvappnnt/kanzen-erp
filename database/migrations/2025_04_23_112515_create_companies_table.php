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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('country_id')
                ->nullable() // Allow nullable for backward compatibility
                ->constrained('countries') // Set up a foreign key to the `countries` table
                ->nullOnDelete(); // If a country is deleted, set `country_id` to null
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('landline')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();
            $table->longText('description')->nullable();
            $table->string('website')->nullable();
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
        Schema::dropIfExists('companies');
    }
};
