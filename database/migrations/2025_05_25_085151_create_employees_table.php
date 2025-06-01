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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');

            // Core Personal Information
            $table->string('number')->unique(); // employee number

            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->string('nickname')->nullable();
            $table->string('suffix')->nullable();
            
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('birthdate')->nullable();
            $table->string('birthplace')->nullable();
            $table->enum('civil_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('citizenship')->nullable();
            $table->string('religion')->nullable();

            $table->string('blood_type')->nullable(); // A, B, AB, O
            $table->string('height')->nullable(); // in cm
            $table->string('weight')->nullable(); // in kg

            $table->string('sss')->nullable();
            $table->string('philhealth')->nullable();
            $table->string('pagibig')->nullable();
            $table->string('tin')->nullable();
            $table->string('umid')->nullable();

            $table->text('avatar')->nullable();

            // Contact Details
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
