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
        Schema::create('room_amenity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade'); // Foreign key to rooms
            $table->foreignId('amenity_id')->constrained()->onDelete('cascade'); // Foreign key to amenities
            $table->timestamps(); // Created and updated timestamps
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_amenity');
    }
};
