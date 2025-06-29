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
        Schema::create('uuid_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('old_id');
            $table->uuid('new_uuid');
            $table->string('table_name');
            $table->timestamps();

            $table->index(['old_id', 'table_name']);
            $table->index(['new_uuid', 'table_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uuid_mappings');
    }
};
