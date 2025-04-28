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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique(); // ISO alpha-2 code (e.g., "PH")
            $table->string('name'); // Common name
            $table->string('official_name')->nullable(); // Official name
            $table->string('native_name')->nullable(); // Native name
            $table->string('native_official_name')->nullable(); // Native official name
            $table->string('iso_3166_1_alpha2', 2)->nullable(); // Alpha-2 code
            $table->string('iso_3166_1_alpha3', 3)->nullable(); // Alpha-3 code
            $table->string('calling_code')->nullable(); // Calling code
            $table->string('currency')->nullable(); // Currency code
            $table->string('emoji', 10)->nullable(); // Emoji flag
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
