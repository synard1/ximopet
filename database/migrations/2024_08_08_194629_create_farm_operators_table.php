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
        Schema::create('farm_operators', function (Blueprint $table) {
            $table->uuid('farm_id');
            $table->unsignedBigInteger('user_id');
        
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('farm_id')->references('id')->on('farms');
        
            // Define the composite primary key
            $table->primary(['farm_id', 'user_id']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_operators');
    }
};
