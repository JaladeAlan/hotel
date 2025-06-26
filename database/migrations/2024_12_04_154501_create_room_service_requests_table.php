<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('room_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('service_type'); // e.g., 'food', 'cleaning', 'laundry'
            $table->string('description'); // Details of the service
            $table->decimal('cost', 10, 2);
            $table->string('status')->default('pending'); // e.g., 'pending', 'in-progress', 'completed'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_service_requests');
    }
};
