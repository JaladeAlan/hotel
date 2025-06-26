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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Facility name (e.g., AC, TV, Balcony)
            $table->timestamps(); // Created and updated timestamps
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
