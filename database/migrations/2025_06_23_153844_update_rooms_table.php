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
    Schema::table('rooms', function (Blueprint $table) {
    $table->string('status')->default('available'); 
    $table->timestamp('last_occupied_at')->nullable(); 
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['status', 'last_occupied_at']);
        });
    }
};
