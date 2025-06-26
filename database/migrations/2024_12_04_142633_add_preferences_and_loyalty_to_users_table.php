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
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_room_type')->nullable();
            $table->string('meal_plan')->nullable();
            $table->integer('loyalty_points')->default(0); // Loyalty points
        });
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferred_room_type', 'meal_plan', 'loyalty_points']);
        });
    }
    
};
