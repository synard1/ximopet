<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ai_file_changes', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->longText('original_content')->nullable();
            $table->longText('modified_content')->nullable();
            $table->timestamp('changed_at');
            $table->string('change_type');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_file_changes');
    }
}; 