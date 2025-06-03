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
        Schema::create('employee_disciplinary_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('offense_date');
            $table->foreignId('offense_type_id')->constrained()->onDelete('cascade');
            $table->text('offense_description')->nullable();
            $table->date('action_date');
            $table->enum('action_taken', ['verbal-warning', 'suspension', 'dismissal']);
            $table->text('action_description')->nullable();
            $table->text('file_path')->nullable(); // attached memo or proof
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_disciplinary_actions');
    }
};
